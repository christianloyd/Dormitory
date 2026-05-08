<?php

declare(strict_types=1);

require_once __DIR__ . '/BillingItems.php';

class BillingCalculator
{
    public const ELECTRICITY_LABEL = 'Electricity';

    public static function splitAmountAcrossTenants(float $amount, int $tenantCount): array
    {
        if ($tenantCount <= 0) {
            return [];
        }

        $shares = array_fill(0, $tenantCount, 0.0);
        $totalCents = (int)round($amount * 100);
        if ($totalCents === 0) {
            return $shares;
        }

        $baseShare = intdiv($totalCents, $tenantCount);
        $remainder = $totalCents - ($baseShare * $tenantCount);

        for ($i = 0; $i < $tenantCount; $i++) {
            $shares[$i] = ($baseShare + ($i < $remainder ? 1 : 0)) / 100;
        }

        return $shares;
    }

    public static function getTenantCarryOver(mysqli $conn, int $tenantId, string $dueDate): array
    {
        $carry = [
            'previous_balance' => 0.0,
            'previous_credit' => 0.0,
        ];

        $stmt = $conn->prepare(
            'SELECT balance, credit_balance
             FROM billing
             WHERE tenant_id = ? AND due_date < ?
             ORDER BY due_date DESC, bill_id DESC
             LIMIT 1'
        );

        if (!$stmt) {
            return $carry;
        }

        $stmt->bind_param('is', $tenantId, $dueDate);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $carry['previous_balance'] = (float)($row['balance'] ?? 0);
            $carry['previous_credit'] = (float)($row['credit_balance'] ?? 0);
        }

        return $carry;
    }

    public static function initialiseBillFinancials(
        mysqli $conn,
        int $billId,
        int $tenantId,
        string $dueDate,
        float $baseRent,
        float $interest = 0.0,
        float $otherAmount = 0.0,
        ?array $carryOver = null
    ): array {
        $utilityItems = getBillingUtilityItems($conn, $billId);
        $additionalItems = getBillingAdditionalItems($conn, $billId);

        $utilityTotal = sumBillingItems($utilityItems);
        $additionalTotal = sumBillingItems($additionalItems);

        if ($carryOver === null) {
            $carryOver = self::getTenantCarryOver($conn, $tenantId, $dueDate);
        }

        $previousBalance = round((float)($carryOver['previous_balance'] ?? 0), 2);
        $previousCredit = round((float)($carryOver['previous_credit'] ?? 0), 2);

        $grossTotal = $baseRent + $interest + $utilityTotal + $additionalTotal + $previousBalance + $otherAmount;
        $appliedCredit = min($previousCredit, $grossTotal);
        $netDue = round($grossTotal - $appliedCredit, 2);
        if ($netDue < 0.0) {
            $netDue = 0.0;
        }
        $remainingCredit = round(max(0.0, $previousCredit - $grossTotal), 2);

        $updateStmt = $conn->prepare(
            'UPDATE billing
             SET previous_balance = ?, previous_credit = ?, total_amount = ?, balance = ?, credit_balance = ?
             WHERE bill_id = ?'
        );

        if ($updateStmt) {
            $updateStmt->bind_param('dddddi', $previousBalance, $previousCredit, $netDue, $netDue, $remainingCredit, $billId);
            $updateStmt->execute();
            $updateStmt->close();
        }

        return [
            'previous_balance' => $previousBalance,
            'previous_credit' => $previousCredit,
            'gross_total' => round($grossTotal, 2),
            'net_total' => $netDue,
            'remaining_credit' => $remainingCredit,
            'utility_total' => $utilityTotal,
            'additional_total' => $additionalTotal,
        ];
    }

    public static function upsertSharedElectricBill(
        mysqli $conn,
        int $tenantId,
        int $roomId,
        string $dueDate,
        float $baseRent,
        string $utilityLabel,
        float $shareAmount
    ): ?array {
        if ($shareAmount <= 0) {
            return null;
        }

        $billId = null;
        $created = false;

        $selectStmt = $conn->prepare('SELECT bill_id FROM billing WHERE tenant_id = ? AND due_date = ? LIMIT 1');
        if (!$selectStmt) {
            throw new Exception('Failed to prepare shared billing lookup.');
        }
        $selectStmt->bind_param('is', $tenantId, $dueDate);
        $selectStmt->execute();
        $result = $selectStmt->get_result();
        if ($result && ($row = $result->fetch_assoc())) {
            $billId = (int)$row['bill_id'];
        }
        $selectStmt->close();

        if ($billId === null) {
            $insertStmt = $conn->prepare(
                'INSERT INTO billing (tenant_id, room_id, due_date, base_rent, status, payment_amount, interest, payment_method)
                 VALUES (?, ?, ?, ?, "Pending", 0, 0, "")'
            );
            if (!$insertStmt) {
                throw new Exception('Failed to prepare shared billing insert.');
            }
            $insertStmt->bind_param('iisd', $tenantId, $roomId, $dueDate, $baseRent);
            if (!$insertStmt->execute()) {
                $error = $insertStmt->error;
                $insertStmt->close();
                throw new Exception('Failed to insert shared billing: ' . $error);
            }
            $billId = $insertStmt->insert_id;
            $created = true;
            $insertStmt->close();

            replaceBillingAdditionalItems($conn, $billId, []);
        }

        $existingUtilityItems = getBillingUtilityItems($conn, $billId);
        $updatedUtilityItems = [];
        foreach ($existingUtilityItems as $item) {
            if (strcasecmp((string)($item['label'] ?? ''), $utilityLabel) === 0) {
                continue;
            }
            $updatedUtilityItems[] = $item;
        }
        $updatedUtilityItems[] = [
            'label' => $utilityLabel,
            'amount' => $shareAmount,
        ];

        replaceBillingUtilityItems($conn, $billId, $updatedUtilityItems);

        return [
            'bill_id' => (int)$billId,
            'created' => $created,
        ];
    }

    public static function ensureSharedBills(
        mysqli $conn,
        int $roomId,
        string $dueDate,
        ?float $baseRentOverride = null
    ): array {
        $roomId = (int)$roomId;
        if ($roomId <= 0) {
            return [
                'updated' => false,
                'reason' => 'invalid_room',
            ];
        }

        $dueDateTs = strtotime($dueDate);
        if ($dueDateTs === false) {
            return [
                'updated' => false,
                'reason' => 'invalid_date',
            ];
        }

        $normalizedDueDate = date('Y-m-d', $dueDateTs);
        $roommates = self::getActiveRoommatesForCycle($conn, $roomId, $normalizedDueDate);
        if (empty($roommates)) {
            return [
                'updated' => false,
                'reason' => 'no_roommates',
            ];
        }

        $totalElectricity = self::getElectricityTotal($conn, $roomId, $normalizedDueDate);
        if ($totalElectricity <= 0) {
            return [
                'updated' => false,
                'reason' => 'no_electricity_recorded',
                'roommates' => count($roommates),
            ];
        }

        $shares = self::splitAmountAcrossTenants($totalElectricity, count($roommates));
        $existingBills = self::fetchRoomBills($conn, $roomId, $normalizedDueDate);
        $roomPrice = self::fetchRoomPrice($conn, $roomId);

        $updatedBills = [];
        foreach ($roommates as $index => $roommate) {
            $tenantId = (int)$roommate['tenant_id'];
            $shareAmount = $shares[$index] ?? 0.0;

            $existing = $existingBills[$tenantId] ?? null;
            $baseRent = $existing['base_rent'] ?? $baseRentOverride ?? ($roommate['room_price'] ?? $roomPrice ?? 0.0);
            $interest = $existing['interest'] ?? 0.0;

            $result = self::upsertSharedElectricBill(
                $conn,
                $tenantId,
                $roomId,
                $normalizedDueDate,
                $baseRent,
                self::ELECTRICITY_LABEL,
                $shareAmount
            );

            $billId = $result['bill_id'] ?? ($existing['bill_id'] ?? null);
            if ($billId === null) {
                continue;
            }

            $financials = self::initialiseBillFinancials(
                $conn,
                $billId,
                $tenantId,
                $normalizedDueDate,
                $baseRent,
                $interest
            );

            $updatedBills[] = [
                'bill_id' => $billId,
                'tenant_id' => $tenantId,
                'share' => $shareAmount,
                'created' => !empty($result['created']),
                'financials' => $financials,
            ];
        }

        return [
            'updated' => !empty($updatedBills),
            'roommates' => count($roommates),
            'total_electricity' => $totalElectricity,
            'bills' => $updatedBills,
        ];
    }

    private static function getActiveRoommatesForCycle(mysqli $conn, int $roomId, string $dueDate): array
    {
        $stmt = $conn->prepare(
            'SELECT t.tenant_id, t.date_started, r.price AS room_price
             FROM tenant_rooms tr
             INNER JOIN tenants t ON t.tenant_id = tr.tenant_id
             LEFT JOIN rooms r ON r.room_id = tr.room_id
             WHERE tr.room_id = ?
               AND tr.released_at IS NULL
               AND t.status = "Active"
               AND t.date_started IS NOT NULL
               AND t.date_started <= ?
             ORDER BY t.tenant_id ASC'
        );

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('is', $roomId, $dueDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $records = [];
        $dueDay = (int)date('d', strtotime($dueDate));

        while ($row = $result->fetch_assoc()) {
            $startTs = strtotime((string)$row['date_started']);
            if ($startTs === false) {
                continue;
            }
            if ((int)date('d', $startTs) !== $dueDay) {
                continue;
            }

            $records[] = [
                'tenant_id' => (int)$row['tenant_id'],
                'date_started' => $row['date_started'],
                'room_price' => isset($row['room_price']) ? (float)$row['room_price'] : null,
            ];
        }

        $stmt->close();
        return $records;
    }

    private static function fetchRoomBills(mysqli $conn, int $roomId, string $dueDate): array
    {
        $stmt = $conn->prepare(
            'SELECT bill_id, tenant_id, base_rent, interest
             FROM billing
             WHERE room_id = ? AND due_date = ?'
        );
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('is', $roomId, $dueDate);
        $stmt->execute();
        $result = $stmt->get_result();

        $records = [];
        while ($row = $result->fetch_assoc()) {
            $tenantId = (int)$row['tenant_id'];
            $records[$tenantId] = [
                'bill_id' => (int)$row['bill_id'],
                'base_rent' => isset($row['base_rent']) ? (float)$row['base_rent'] : 0.0,
                'interest' => isset($row['interest']) ? (float)$row['interest'] : 0.0,
            ];
        }

        $stmt->close();
        return $records;
    }

    private static function getElectricityTotal(mysqli $conn, int $roomId, string $dueDate): float
    {
        $stmt = $conn->prepare(
            'SELECT SUM(bui.amount) AS total_amount
             FROM billing_utility_items bui
             INNER JOIN billing b ON b.bill_id = bui.bill_id
             WHERE b.room_id = ?
               AND b.due_date = ?
               AND LOWER(bui.label) = LOWER(?)'
        );

        if (!$stmt) {
            return 0.0;
        }

        $label = self::ELECTRICITY_LABEL;
        $stmt->bind_param('iss', $roomId, $dueDate, $label);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return isset($row['total_amount']) ? (float)$row['total_amount'] : 0.0;
    }

    private static function fetchRoomPrice(mysqli $conn, int $roomId): ?float
    {
        $stmt = $conn->prepare('SELECT price FROM rooms WHERE room_id = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $roomId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return null;
        }

        return isset($row['price']) ? (float)$row['price'] : null;
    }

    public static function syncNextBillCarryOver(mysqli $conn, int $tenantId, ?string $currentDueDate, float $carryBalance, float $carryCredit): void
    {
        $comparisonDate = $currentDueDate ?: '0000-00-00';

        $stmt = $conn->prepare("
            SELECT bill_id, base_rent, interest, other_amount, payment_amount 
            FROM billing 
            WHERE tenant_id = ? AND due_date > ? 
            ORDER BY due_date ASC, bill_id ASC
        ");
        if (!$stmt) return;
        $stmt->bind_param('is', $tenantId, $comparisonDate);
        $stmt->execute();
        $bills = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($bills as $bill) {
            $billId = (int)$bill['bill_id'];
            $baseRent = (float)$bill['base_rent'];
            $interest = (float)$bill['interest'];
            $otherAmount = (float)$bill['other_amount'];
            $paymentAmount = (float)$bill['payment_amount'];

            // Calculate utility and additional totals
            $utilityItems = getBillingUtilityItems($conn, $billId);
            $additionalItems = getBillingAdditionalItems($conn, $billId);
            $utilityTotal = sumBillingItems($utilityItems);
            $additionalTotal = sumBillingItems($additionalItems);

            $grossTotal = $baseRent + $interest + $utilityTotal + $additionalTotal + $carryBalance + $otherAmount;
            $appliedCredit = min($carryCredit, $grossTotal);
            $amountDueBeforePayment = round($grossTotal - $appliedCredit, 2);
            if ($amountDueBeforePayment < 0.0) $amountDueBeforePayment = 0.0;
            
            $carriedPreviousCredit = round(max(0.0, $carryCredit - $grossTotal), 2);
            
            // Apply payment
            $remainingBalance = max(0.0, $amountDueBeforePayment - $paymentAmount);
            $newCreditFromPayment = max(0.0, $paymentAmount - $amountDueBeforePayment);
            $creditBalance = $carriedPreviousCredit + $newCreditFromPayment;

            $remainingBalance = round($remainingBalance, 2);
            $creditBalance = round($creditBalance, 2);

            if ($remainingBalance <= 0.009) {
                $status = 'Settled';
                $remainingBalance = 0.0;
            } elseif ($paymentAmount > 0) {
                $status = 'Partial';
            } else {
                $status = 'Pending';
            }

            // Update this bill
            $updateStmt = $conn->prepare("
                UPDATE billing 
                SET previous_balance = ?, previous_credit = ?, total_amount = ?, balance = ?, credit_balance = ?, status = ?
                WHERE bill_id = ?
            ");
            if ($updateStmt) {
                $updateStmt->bind_param('dddddsi', $carryBalance, $carryCredit, $amountDueBeforePayment, $remainingBalance, $creditBalance, $status, $billId);
                $updateStmt->execute();
                $updateStmt->close();
            }

            // The carried balance and credit for the NEXT bill are exactly this bill's remaining balance and credit
            $carryBalance = $remainingBalance;
            $carryCredit = $creditBalance;
        }
    }
}
