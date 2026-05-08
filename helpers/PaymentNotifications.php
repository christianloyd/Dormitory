<?php
require_once __DIR__ . '/sms_helper.php';
require_once __DIR__ . '/BillingItems.php';

if (!function_exists('sendPaymentConfirmationForBill')) {
    /**
     * Send payment confirmation SMS for a given bill and return detailed results.
     */
    function sendPaymentConfirmationForBill(mysqli $conn, int $billId): array
    {
        $result = [
            'success' => false,
            'message' => 'Unable to prepare payment confirmation.',
            'sent_numbers' => [],
            'sms_results' => [],
            'sms_preview' => null,
            'character_count' => 0,
            'segments' => 0,
            'remaining_balance' => null,
            'totals' => null,
            'tenant_name' => null,
            'has_numbers' => false,
        ];

        $stmt = $conn->prepare(
            "
            SELECT t.tenant_id, t.tenant_name, t.contact_number, t.guardian_contact,
                   r.room_number,
                   b.bill_id, b.bill_date, b.due_date, b.payment_date, b.payment_amount,
                   b.payment_method, b.total_amount, b.base_rent, b.interest, b.status
            FROM billing b
            INNER JOIN tenants t ON b.tenant_id = t.tenant_id
            INNER JOIN rooms r ON b.room_id = r.room_id
            WHERE b.bill_id = ?
        "
        );

        if (!$stmt) {
            $result['message'] = 'Unable to prepare payment lookup.';
            return $result;
        }

        $stmt->bind_param('i', $billId);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$payment) {
            $result['message'] = 'Payment record not found.';
            return $result;
        }

        $result['tenant_name'] = $payment['tenant_name'] ?? null;

        if ($payment['status'] !== 'Partial' && $payment['status'] !== 'Settled') {
            $result['message'] = 'No payment confirmation needed for status: ' . ($payment['status'] ?? 'Unknown');
            return $result;
        }

        $utilityItems = getBillingUtilityItems($conn, $billId);
        $additionalItems = getBillingAdditionalItems($conn, $billId);

        $confirmationMessage = composePaymentConfirmationSMSMessage(
            $payment,
            $utilityItems,
            $additionalItems
        );

        $message = $confirmationMessage['message'];
        $characterCount = mb_strlen($message);
        $segments = max(1, (int)ceil($characterCount / 157));

        $smsHelper = new SMSHelper($conn);
        if (defined('SMS_ENABLED')) {
            $smsHelper->setSMSEnabled(SMS_ENABLED);
        }

        $numbers = [
            ['number' => $payment['contact_number'], 'type' => 'Tenant'],
            ['number' => $payment['guardian_contact'], 'type' => 'Guardian'],
        ];

        $sentNumbers = [];
        $smsResults = [];
        $hasNumbers = false;

        foreach ($numbers as $recipient) {
            if (empty($recipient['number'])) {
                continue;
            }

            $hasNumbers = true;
            $sendResult = $smsHelper->sendSMS(
                $recipient['number'],
                $message,
                (int)$payment['tenant_id'],
                $recipient['type']
            );

            if (!empty($sendResult['success'])) {
                $sentNumbers[] = $recipient['number'];
            }

            $smsResults[] = [
                'number' => $recipient['number'],
                'type' => $recipient['type'],
                'status' => !empty($sendResult['success']) ? 'sent' : ($sendResult['status'] ?? 'failed'),
                'message' => $sendResult['message'] ?? '',
            ];
        }

        $result['has_numbers'] = $hasNumbers;
        $result['sent_numbers'] = $sentNumbers;
        $result['sms_results'] = $smsResults;
        $result['sms_preview'] = $message;
        $result['character_count'] = $characterCount;
        $result['segments'] = $segments;
        $result['remaining_balance'] = $confirmationMessage['remaining_balance'];
        $result['totals'] = [
            'utilities' => $confirmationMessage['total_utilities'],
            'additional' => $confirmationMessage['total_additional'],
            'overall' => $confirmationMessage['total_amount'],
        ];

        if (!$hasNumbers) {
            $result['message'] = 'No contact numbers available for payment confirmation.';
            return $result;
        }

        if (!empty($sentNumbers)) {
            $result['success'] = true;
            $result['message'] = 'Payment confirmation sent to ' . count($sentNumbers) . ' recipient(s).';
        } else {
            $result['message'] = 'Payment confirmation attempted but no messages were sent.';
        }

        $notifMessage = sprintf(
            'Payment confirmation sent for Room %s. Paid: ₱%s, Total Bill: ₱%s',
            $payment['room_number'] ?? '-',
            number_format((float)($payment['payment_amount'] ?? 0), 2),
            number_format((float)($payment['total_amount'] ?? 0), 2)
        );

        $stmtNotif = $conn->prepare(
            'INSERT INTO notifications (tenant_id, type, message, is_read, created_at)
             VALUES (?, ?, ?, 0, NOW())'
        );

        if ($stmtNotif) {
            $type = 'confirmation';
            $stmtNotif->bind_param(
                'iss',
                $payment['tenant_id'],
                $type,
                $notifMessage
            );
            $stmtNotif->execute();
            $stmtNotif->close();
        }

        return $result;
    }
}
