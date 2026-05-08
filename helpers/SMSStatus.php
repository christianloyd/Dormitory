<?php

class SMSStatusRepository
{
    private const TABLE_NAME = 'billing_sms_status';

    public static function ensureTable(mysqli $conn): void
    {
        $sql = sprintf(
            'CREATE TABLE IF NOT EXISTS %s (
                bill_id INT NOT NULL PRIMARY KEY,
                tenant_id INT NOT NULL,
                last_status VARCHAR(20) NOT NULL,
                last_message VARCHAR(255) NOT NULL,
                success_count INT NOT NULL DEFAULT 0,
                failure_count INT NOT NULL DEFAULT 0,
                last_attempt_at DATETIME NOT NULL,
                last_error TEXT NULL,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            self::TABLE_NAME
        );

        $conn->query($sql);
    }

    public static function recordBillingNotice(mysqli $conn, int $billId, int $tenantId, array $summary): void
    {
        self::ensureTable($conn);

        $status = $summary['success'] ? 'sent' : ((($summary['has_numbers'] ?? false) && !empty($summary['sms_results'])) ? 'failed' : 'skipped');
        $successCount = isset($summary['sent_numbers']) ? count($summary['sent_numbers']) : 0;
        $failureCount = max((isset($summary['sms_results']) ? count($summary['sms_results']) : 0) - $successCount, 0);

        $errors = [];
        if (!empty($summary['sms_results'])) {
            foreach ($summary['sms_results'] as $result) {
                if (($result['status'] ?? '') !== 'sent') {
                    $errors[] = sprintf('%s (%s): %s', $result['type'] ?? 'Recipient', $result['number'] ?? '-', $result['message'] ?? '');
                }
            }
        }

        $lastError = $errors ? implode("; ", $errors) : null;
        $lastMessage = $summary['message'] ?? '';

        $sql = sprintf(
            'INSERT INTO %s (bill_id, tenant_id, last_status, last_message, success_count, failure_count, last_attempt_at, last_error)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
             ON DUPLICATE KEY UPDATE
                tenant_id = VALUES(tenant_id),
                last_status = VALUES(last_status),
                last_message = VALUES(last_message),
                success_count = VALUES(success_count),
                failure_count = VALUES(failure_count),
                last_attempt_at = VALUES(last_attempt_at),
                last_error = VALUES(last_error)',
            self::TABLE_NAME
        );

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return;
        }

        $stmt->bind_param(
            'isssiis',
            $billId,
            $tenantId,
            $status,
            $lastMessage,
            $successCount,
            $failureCount,
            $lastError
        );
        $stmt->execute();
        $stmt->close();
    }

    public static function fetchStatuses(mysqli $conn, array $billIds): array
    {
        if (empty($billIds)) {
            return [];
        }

        self::ensureTable($conn);

        $placeholders = implode(',', array_fill(0, count($billIds), '?'));
        $sql = sprintf(
            'SELECT bill_id, last_status, last_message, success_count, failure_count, last_attempt_at, last_error
             FROM %s
             WHERE bill_id IN (%s)',
            self::TABLE_NAME,
            $placeholders
        );

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $types = str_repeat('i', count($billIds));
        $stmt->bind_param($types, ...$billIds);
        $stmt->execute();
        $result = $stmt->get_result();

        $statuses = [];
        while ($row = $result->fetch_assoc()) {
            $statuses[(int)$row['bill_id']] = $row;
        }

        $stmt->close();

        return $statuses;
    }
}
