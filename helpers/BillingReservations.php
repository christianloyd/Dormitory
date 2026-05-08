<?php

declare(strict_types=1);

/**
 * Helper functions for reserving and applying shared utility amounts
 * when a roommate is not yet ready to receive an automatically-created bill.
 */

if (!function_exists('reserveUtilityShare')) {
    function reserveUtilityShare(
        mysqli $conn,
        int $tenantId,
        int $roomId,
        string $dueDate,
        string $label,
        float $amount,
        int $sourceBillId
    ): void {
        $amount = round($amount, 2);
        $label = trim($label);
        if ($amount <= 0.0 || $label === '') {
            return;
        }

        $selectStmt = $conn->prepare(
            'SELECT id FROM billing_utility_reservations
             WHERE tenant_id = ? AND room_id = ? AND due_date = ? AND label = ? AND consumed_bill_id IS NULL
             LIMIT 1'
        );

        if (!$selectStmt) {
            throw new Exception('Failed to prepare utility reservation lookup: ' . $conn->error);
        }

        $selectStmt->bind_param('iiss', $tenantId, $roomId, $dueDate, $label);
        $selectStmt->execute();
        $selectResult = $selectStmt->get_result();
        $existing = $selectResult ? $selectResult->fetch_assoc() : null;
        $selectStmt->close();

        if ($existing) {
            $updateStmt = $conn->prepare(
                'UPDATE billing_utility_reservations
                 SET amount = amount + ?, source_bill_id = ?
                 WHERE id = ?'
            );

            if (!$updateStmt) {
                throw new Exception('Failed to prepare utility reservation update: ' . $conn->error);
            }

            $reservationId = (int)$existing['id'];
            $updateStmt->bind_param('dii', $amount, $sourceBillId, $reservationId);
            $updateStmt->execute();
            $updateStmt->close();
            return;
        }

        $insertStmt = $conn->prepare(
            'INSERT INTO billing_utility_reservations (tenant_id, room_id, due_date, label, amount, source_bill_id)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        if (!$insertStmt) {
            throw new Exception('Failed to prepare utility reservation insert: ' . $conn->error);
        }

        $insertStmt->bind_param('iissdi', $tenantId, $roomId, $dueDate, $label, $amount, $sourceBillId);
        $insertStmt->execute();
        $insertStmt->close();
    }
}

if (!function_exists('getPendingUtilityReservations')) {
    function getPendingUtilityReservations(
        mysqli $conn,
        int $tenantId,
        int $roomId,
        string $dueDate
    ): array {
        $stmt = $conn->prepare(
            'SELECT id, label, amount
             FROM billing_utility_reservations
             WHERE tenant_id = ?
               AND room_id = ?
               AND due_date = ?
               AND consumed_bill_id IS NULL'
        );

        if (!$stmt) {
            throw new Exception('Failed to prepare pending reservation lookup: ' . $conn->error);
        }

        $stmt->bind_param('iis', $tenantId, $roomId, $dueDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $reservations = [];
        while ($row = $result->fetch_assoc()) {
            $reservations[] = [
                'id' => (int)$row['id'],
                'label' => (string)$row['label'],
                'amount' => (float)$row['amount'],
            ];
        }
        $stmt->close();

        return $reservations;
    }
}

if (!function_exists('consumeUtilityReservations')) {
    function consumeUtilityReservations(
        mysqli $conn,
        array $reservationIds,
        int $billId
    ): void {
        $reservationIds = array_map(function ($entry) {
            if (is_array($entry)) {
                return isset($entry['id']) ? (int)$entry['id'] : 0;
            }
            return (int)$entry;
        }, $reservationIds);

        $reservationIds = array_values(array_unique(array_filter($reservationIds)));
        if (empty($reservationIds)) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($reservationIds), '?'));
        $types = str_repeat('i', count($reservationIds) + 1);
        $params = array_merge([$billId], $reservationIds);

        $sql = "UPDATE billing_utility_reservations
                SET consumed_bill_id = ?, consumed_at = NOW()
                WHERE id IN ($placeholders)
                  AND consumed_bill_id IS NULL";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Failed to prepare reservation consume statement: ' . $conn->error);
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();
    }
}

if (!function_exists('applyUtilityReservationsToItems')) {
    function applyUtilityReservationsToItems(
        array &$utilityItems,
        array $reservations,
        int $billId
    ): array {
        if (empty($reservations)) {
            return [];
        }

        $consumed = [];
        foreach ($reservations as $reservation) {
            $label = (string)($reservation['label'] ?? '');
            $amount = round((float)($reservation['amount'] ?? 0.0), 2);
            if ($label === '' || $amount <= 0.0) {
                continue;
            }

            $matched = false;
            foreach ($utilityItems as &$item) {
                if (strcasecmp((string)($item['label'] ?? ''), $label) === 0) {
                    $item['amount'] = round((float)($item['amount'] ?? 0.0) + $amount, 2);
                    $matched = true;
                    break;
                }
            }
            unset($item);

            if (!$matched) {
                $utilityItems[] = [
                    'label' => $label,
                    'amount' => $amount,
                    'bill_id' => $billId,
                ];
            }

            if (isset($reservation['id'])) {
                $consumed[] = [
                    'id' => (int)$reservation['id'],
                    'label' => $label,
                    'amount' => $amount,
                ];
            }
        }

        return $consumed;
    }
}
