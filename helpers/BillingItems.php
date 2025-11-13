<?php
/**
 * Helper functions for managing billing utility and additional charge line items.
 */

if (!function_exists('getBillingUtilityItems')) {
    function getBillingUtilityItems(mysqli $conn, int $billId): array
    {
        $stmt = $conn->prepare("SELECT label, amount FROM billing_utility_items WHERE bill_id = ? ORDER BY id ASC");
        $stmt->bind_param("i", $billId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return array_map(function ($row) {
            return [
                'label' => $row['label'],
                'amount' => (float)$row['amount']
            ];
        }, $result);
    }
}

if (!function_exists('getBillingAdditionalItems')) {
    function getBillingAdditionalItems(mysqli $conn, int $billId): array
    {
        $stmt = $conn->prepare("SELECT label, amount FROM billing_additional_items WHERE bill_id = ? ORDER BY id ASC");
        $stmt->bind_param("i", $billId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return array_map(function ($row) {
            return [
                'label' => $row['label'],
                'amount' => (float)$row['amount']
            ];
        }, $result);
    }
}

if (!function_exists('getBillingUtilityItemsMap')) {
    function getBillingUtilityItemsMap(mysqli $conn, array $billIds): array
    {
        if (empty($billIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($billIds), '?'));
        $types = str_repeat('i', count($billIds));
        $stmt = $conn->prepare("SELECT bill_id, label, amount FROM billing_utility_items WHERE bill_id IN ($placeholders) ORDER BY id ASC");
        $stmt->bind_param($types, ...$billIds);
        $stmt->execute();
        $result = $stmt->get_result();
        $map = [];
        while ($row = $result->fetch_assoc()) {
            $billId = (int)$row['bill_id'];
            if (!isset($map[$billId])) {
                $map[$billId] = [];
            }
            $map[$billId][] = [
                'label' => $row['label'],
                'amount' => (float)$row['amount']
            ];
        }
        $stmt->close();
        return $map;
    }
}

if (!function_exists('getBillingAdditionalItemsMap')) {
    function getBillingAdditionalItemsMap(mysqli $conn, array $billIds): array
    {
        if (empty($billIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($billIds), '?'));
        $types = str_repeat('i', count($billIds));
        $stmt = $conn->prepare("SELECT bill_id, label, amount FROM billing_additional_items WHERE bill_id IN ($placeholders) ORDER BY id ASC");
        $stmt->bind_param($types, ...$billIds);
        $stmt->execute();
        $result = $stmt->get_result();
        $map = [];
        while ($row = $result->fetch_assoc()) {
            $billId = (int)$row['bill_id'];
            if (!isset($map[$billId])) {
                $map[$billId] = [];
            }
            $map[$billId][] = [
                'label' => $row['label'],
                'amount' => (float)$row['amount']
            ];
        }
        $stmt->close();
        return $map;
    }
}

if (!function_exists('replaceBillingUtilityItems')) {
    function replaceBillingUtilityItems(mysqli $conn, int $billId, array $items): void
    {
        $conn->query("DELETE FROM billing_utility_items WHERE bill_id = " . intval($billId));
        if (empty($items)) {
            return;
        }
        $stmt = $conn->prepare("INSERT INTO billing_utility_items (bill_id, label, amount) VALUES (?, ?, ?)");
        foreach ($items as $item) {
            $label = trim((string)($item['label'] ?? ''));
            $amount = (float)($item['amount'] ?? 0);
            $stmt->bind_param("isd", $billId, $label, $amount);
            $stmt->execute();
        }
        $stmt->close();
    }
}

if (!function_exists('replaceBillingAdditionalItems')) {
    function replaceBillingAdditionalItems(mysqli $conn, int $billId, array $items): void
    {
        $conn->query("DELETE FROM billing_additional_items WHERE bill_id = " . intval($billId));
        if (empty($items)) {
            return;
        }
        $stmt = $conn->prepare("INSERT INTO billing_additional_items (bill_id, label, amount) VALUES (?, ?, ?)");
        foreach ($items as $item) {
            $label = trim((string)($item['label'] ?? ''));
            $amount = (float)($item['amount'] ?? 0);
            $stmt->bind_param("isd", $billId, $label, $amount);
            $stmt->execute();
        }
        $stmt->close();
    }
}

if (!function_exists('sumBillingItems')) {
    function sumBillingItems(array $items): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            $total += (float)($item['amount'] ?? 0);
        }
        return $total;
    }
}

if (!function_exists('composeReminderSMSMessage')) {
    function composeReminderSMSMessage(array $record, array $utilityItems, array $additionalItems): array
    {
        $tenantName = $record['tenant_name'] ?? 'Tenant';
        $roomNumber = $record['room_number'] ?? '-';
        $dueDateRaw = $record['due_date'] ?? '';
        $dueDate = $dueDateRaw ? date('M d, Y', strtotime($dueDateRaw)) : 'N/A';

        $baseRent = (float)($record['base_rent'] ?? 0);
        $interest = (float)($record['interest'] ?? 0);

        $utilityLines = [];
        $utilityTotal = 0.0;
        foreach ($utilityItems as $item) {
            $label = trim((string)($item['label'] ?? 'Utility'));
            $amount = (float)($item['amount'] ?? 0);
            $utilityTotal += $amount;
            $utilityLines[] = "- Utility ({$label}): PHP " . number_format($amount, 2);
        }
        if (empty($utilityLines)) {
            $utilityLines[] = "- Utilities: None";
        }

        $additionalLines = [];
        $additionalTotal = 0.0;
        foreach ($additionalItems as $item) {
            $label = trim((string)($item['label'] ?? 'Charge'));
            $amount = (float)($item['amount'] ?? 0);
            $additionalTotal += $amount;
            $additionalLines[] = "- Additional ({$label}): PHP " . number_format($amount, 2);
        }
        if (empty($additionalLines)) {
            $additionalLines[] = "- Additional Charges: None";
        }

        $totalAmount = $baseRent + $interest + $utilityTotal + $additionalTotal;

        $lines = [
            'Ben and Sof Dormitory',
            'Purok 1A, Mati, San Miguel, ZDS',
            '',
            'Payment Reminder',
            '',
            "Hi {$tenantName},",
            "Room: {$roomNumber}",
            "Due Date: {$dueDate}",
            '',
            'Charges:',
            '- Base Rent: PHP ' . number_format($baseRent, 2),
            '- Interest: PHP ' . number_format($interest, 2),
        ];

        $lines = array_merge($lines, $utilityLines, $additionalLines);

        $lines[] = '';
        $lines[] = 'Total Amount Due: PHP ' . number_format($totalAmount, 2);
        $lines[] = 'Please settle within 3 days to avoid penalties.';
        $lines[] = 'Thank you!';

        return [
            'message' => implode("\n", $lines),
            'total_utilities' => $utilityTotal,
            'total_additional' => $additionalTotal,
            'total_amount' => $totalAmount
        ];
    }
}

if (!function_exists('composePaymentConfirmationSMSMessage')) {
    function composePaymentConfirmationSMSMessage(array $record, array $utilityItems, array $additionalItems): array
    {
        $tenantName = $record['tenant_name'] ?? 'Tenant';
        $roomNumber = $record['room_number'] ?? '-';
        $paymentDateRaw = $record['payment_date'] ?? '';
        $paymentDate = $paymentDateRaw ? date('M d, Y', strtotime($paymentDateRaw)) : date('M d, Y');
        $paymentMethod = $record['payment_method'] ?? '-';
        $status = $record['status'] ?? '-';

        $baseRent = (float)($record['base_rent'] ?? 0);
        $interest = (float)($record['interest'] ?? 0);
        $paymentAmount = (float)($record['payment_amount'] ?? 0);
        $totalAmount = (float)($record['total_amount'] ?? ($baseRent + $interest));
        $dueDateRaw = $record['due_date'] ?? '';
        $dueDate = $dueDateRaw ? date('M d, Y', strtotime($dueDateRaw)) : 'N/A';

        $utilityLines = [];
        $utilityTotal = 0.0;
        foreach ($utilityItems as $item) {
            $label = trim((string)($item['label'] ?? 'Utility'));
            $amount = (float)($item['amount'] ?? 0);
            $utilityTotal += $amount;
            $utilityLines[] = "- Utility ({$label}): PHP " . number_format($amount, 2);
        }
        if (empty($utilityLines)) {
            $utilityLines[] = "- Utilities: None";
        }

        $additionalLines = [];
        $additionalTotal = 0.0;
        foreach ($additionalItems as $item) {
            $label = trim((string)($item['label'] ?? 'Charge'));
            $amount = (float)($item['amount'] ?? 0);
            $additionalTotal += $amount;
            $additionalLines[] = "- Additional ({$label}): PHP " . number_format($amount, 2);
        }
        if (empty($additionalLines)) {
            $additionalLines[] = "- Additional Charges: None";
        }

        $remainingBalance = max(0, $totalAmount - $paymentAmount);

        $lines = [
            'Ben and Sof Dormitory',
            'Purok 1A, Mati, San Miguel, ZDS',
            '',
            'Payment Confirmation',
            '',
            "Hi {$tenantName},",
            "Payment received for Room {$roomNumber}.",
            'Payment Date: ' . $paymentDate,
            'Amount Paid: PHP ' . number_format($paymentAmount, 2),
            'Method: ' . $paymentMethod,
            'Status: ' . $status,
            '',
            'Breakdown:',
            '- Base Rent: PHP ' . number_format($baseRent, 2),
            '- Interest: PHP ' . number_format($interest, 2),
        ];

        $lines = array_merge($lines, $utilityLines, $additionalLines);

        $lines[] = '';
        $lines[] = 'Total Bill: PHP ' . number_format($totalAmount, 2);

        if ($remainingBalance > 0.009) {
            $lines[] = 'Remaining Balance: PHP ' . number_format($remainingBalance, 2);
            $lines[] = 'Balance Due Date: ' . $dueDate;
        } else {
            $lines[] = 'Remaining Balance: PHP 0.00';
        }

        $lines[] = 'Thank you for your payment!';

        return [
            'message' => implode("\n", $lines),
            'total_utilities' => $utilityTotal,
            'total_additional' => $additionalTotal,
            'remaining_balance' => $remainingBalance,
            'total_amount' => $totalAmount
        ];
    }
}
