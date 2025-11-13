<?php
/**
 * Billing Module - Process Payment
 * Path: /modules/billing/process_payment.php
 */
require_once '../../includes/auth_check.php';
require_once __DIR__ . '/../../helpers/BillingLock.php';
require_once __DIR__ . '/../../helpers/BillingItems.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bill_id = intval($_POST['bill_id']);
    $tenant_id = intval($_POST['tenant_id']);
    $payment_amount = floatval($_POST['payment_amount']);
    $payment_method = $_POST['payment_method'];
    $payment_date = date('Y-m-d H:i:s');
    $total_amount = floatval(str_replace(',', '', $_POST['total_amount']));

    if (isBillingRecordLocked($conn, $bill_id)) {
        Session::setMessage('This billing record is locked and cannot accept new payments.', 'danger');
        header("Location: view.php?tenant_id=$tenant_id");
        exit;
    }

    // Determine status
    if ($payment_amount >= $total_amount) {
        $status = "Settled";
    } elseif ($payment_amount > 0) {
        $status = "Partial";
    } else {
        $status = "Pending Payment";
    }

    $sql = "UPDATE billing 
            SET payment_amount = ?, payment_method = ?, payment_date = ?, status = ?
            WHERE bill_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dsssi", $payment_amount, $payment_method, $payment_date, $status, $bill_id);

    if ($stmt->execute()) {
        $stmt->close();

        // Fetch latest payment/billing context
        $detailsStmt = $conn->prepare("
            SELECT b.bill_id, b.tenant_id, b.room_id, b.payment_amount, b.payment_method, b.payment_date,
                   b.total_amount, b.base_rent, b.interest, b.due_date, b.status,
                   t.tenant_name, t.contact_number, t.guardian_contact,
                   r.room_number
            FROM billing b
            INNER JOIN tenants t ON b.tenant_id = t.tenant_id
            INNER JOIN rooms r ON b.room_id = r.room_id
            WHERE b.bill_id = ?
        ");
        $detailsStmt->bind_param("i", $bill_id);
        $detailsStmt->execute();
        $paymentRecord = $detailsStmt->get_result()->fetch_assoc();
        $detailsStmt->close();

        $responsePayload = [
            'success' => true,
            'message' => 'Payment saved successfully.',
            'status' => $status,
            'bill_id' => $bill_id,
            'tenant_id' => $tenant_id
        ];

        if ($paymentRecord && ($status === 'Partial' || $status === 'Settled')) {
            $utilityItems = getBillingUtilityItems($conn, $bill_id);
            $additionalItems = getBillingAdditionalItems($conn, $bill_id);

            $previewData = composePaymentConfirmationSMSMessage(
                [
                    'tenant_name' => $paymentRecord['tenant_name'],
                    'room_number' => $paymentRecord['room_number'],
                    'payment_date' => $paymentRecord['payment_date'],
                    'payment_amount' => $paymentRecord['payment_amount'],
                    'payment_method' => $paymentRecord['payment_method'],
                    'status' => $paymentRecord['status'],
                    'base_rent' => $paymentRecord['base_rent'],
                    'interest' => $paymentRecord['interest'],
                    'total_amount' => $paymentRecord['total_amount'],
                    'due_date' => $paymentRecord['due_date']
                ],
                $utilityItems,
                $additionalItems
            );

            $previewMessage = $previewData['message'];
            $charCount = mb_strlen($previewMessage);
            $segments = max(1, ceil($charCount / 157));

            $responsePayload['sms_preview'] = $previewMessage;
            $responsePayload['character_count'] = $charCount;
            $responsePayload['segments'] = $segments;
            $responsePayload['totals'] = [
                'utilities' => $previewData['total_utilities'],
                'additional' => $previewData['total_additional'],
                'overall' => $previewData['total_amount']
            ];
            $responsePayload['remaining_balance'] = $previewData['remaining_balance'];
            $responsePayload['tenant_name'] = $paymentRecord['tenant_name'];
        }

        if (!empty($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode($responsePayload);
            exit;
        }

        // Fallback for non-AJAX requests
        Session::setMessage('Payment saved. You can send the confirmation SMS from the payment confirmation modal.', 'success');
        header("Location: view.php?tenant_id=$tenant_id");
        exit;
    } else {
        $errorMessage = $stmt->error;
        $stmt->close();

        if (!empty($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error updating payment: ' . $errorMessage
            ]);
            exit;
        }

        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
            Swal.fire({
                title: 'Error',
                text: 'Error updating payment.',
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#d33'
            }).then(() => {
                window.history.back();
            });
        </script>";
    }
} 
?>
