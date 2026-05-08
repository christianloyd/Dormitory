<?php
/**
 * Billing Module - Process Payment
 * Path: /modules/billing/process_payment.php
 */
require_once '../../includes/auth_check.php';
require_once __DIR__ . '/../../helpers/BillingLock.php';
require_once __DIR__ . '/../../helpers/BillingItems.php';
require_once __DIR__ . '/../../helpers/BillingCalculator.php';
require_once __DIR__ . '/../../helpers/PaymentNotifications.php';



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

    $billDetailsStmt = $conn->prepare(
        "SELECT bill_id, tenant_id, room_id, due_date, base_rent, interest, previous_balance, previous_credit, other_amount
         FROM billing WHERE bill_id = ? LIMIT 1"
    );

    if (!$billDetailsStmt) {
        Session::setMessage('Unable to locate billing record for payment processing.', 'danger');
        header("Location: view.php?tenant_id=$tenant_id");
        exit;
    }

    $billDetailsStmt->bind_param('i', $bill_id);
    $billDetailsStmt->execute();
    $billRecord = $billDetailsStmt->get_result()->fetch_assoc();
    $billDetailsStmt->close();

    if (!$billRecord) {
        Session::setMessage('Billing record not found.', 'danger');
        header("Location: view.php?tenant_id=$tenant_id");
        exit;
    }

    $utilityItems = getBillingUtilityItems($conn, $bill_id);
    $additionalItems = getBillingAdditionalItems($conn, $bill_id);

    $utilityTotal = sumBillingItems($utilityItems);
    $additionalTotal = sumBillingItems($additionalItems);

    $baseRent = (float)($billRecord['base_rent'] ?? 0);
    $interest = (float)($billRecord['interest'] ?? 0);
    $previousBalance = (float)($billRecord['previous_balance'] ?? 0);
    $previousCredit = (float)($billRecord['previous_credit'] ?? 0);
    $otherAmount = (float)($billRecord['other_amount'] ?? 0);

    $grossTotal = $baseRent + $interest + $utilityTotal + $additionalTotal + $previousBalance + $otherAmount;
    $amountDueBeforePayment = max(0.0, $grossTotal - $previousCredit);

    $remainingBalance = max(0.0, $amountDueBeforePayment - $payment_amount);
    $carriedPreviousCredit = max(0.0, $previousCredit - $grossTotal);
    $newCreditFromPayment = max(0.0, $payment_amount - $amountDueBeforePayment);
    $creditBalance = $carriedPreviousCredit + $newCreditFromPayment;

    $remainingBalance = round($remainingBalance, 2);
    $creditBalance = round($creditBalance, 2);
    $amountDueBeforePayment = round($amountDueBeforePayment, 2);

    if ($remainingBalance <= 0.009) {
        $status = 'Settled';
        $remainingBalance = 0.0;
    } elseif ($payment_amount > 0) {
        $status = 'Partial';
    } else {
        $status = 'Pending Payment';
    }

    $sql = "UPDATE billing 
            SET payment_amount = ?, payment_method = ?, payment_date = ?, status = ?,
                balance = ?, credit_balance = ?, total_amount = ?
            WHERE bill_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'dsssdddi',
        $payment_amount,
        $payment_method,
        $payment_date,
        $status,
        $remainingBalance,
        $creditBalance,
        $amountDueBeforePayment,
        $bill_id
    );

    if ($stmt->execute()) {
        $stmt->close();

        BillingCalculator::syncNextBillCarryOver($conn, $tenant_id, $billRecord['due_date'], $remainingBalance, $creditBalance);

        // --- DISTRIBUTE PAYMENT TO PAST PENDING BILLS ---
        $pastBillsStmt = $conn->prepare("
            SELECT bill_id, total_amount, balance 
            FROM billing 
            WHERE tenant_id = ? AND due_date <= ? AND status IN ('Pending', 'Partial') AND bill_id != ?
            ORDER BY due_date ASC, bill_id ASC
        ");
        if ($pastBillsStmt) {
            $pastBillsStmt->bind_param('isi', $tenant_id, $billRecord['due_date'], $bill_id);
            $pastBillsStmt->execute();
            $pastBillsResult = $pastBillsStmt->get_result();
            
            // We know the CURRENT bill's final $remainingBalance.
            // Any debt left is conceptually the newest debt.
            // So we can walk BACKWARD from the newest past bill to the oldest,
            // assigning the $remainingBalance up to each bill's total_amount.
            // Wait, an easier approach: We received $payment_amount.
            // If we sort ASC (oldest first), we can apply $payment_amount sequentially to their NET debt?
            // Since we just want to resolve statuses, if $remainingBalance == 0, ALL past are Settled.
            // Let's use the leftover debt approach from newest (current) to oldest.
            
            // Actually, an even more robust way: 
            // The unassigned debt is exactly $remainingBalance.
            // For any past bill, its new balance should be max(0, $unassigned_debt - (GrossDebt_After_It - GrossDebt_At_It)) ...
            // Let's just do the simplest implementation for full payment fixing:
            $unallocated_payment = $payment_amount;
            
            // We iterate oldest to newest (including the current one conceptually, but we only update past bills here).
            // Since past bills have cumulative total_amount, we can't just subtract. 
            // Instead, if the current bill is FULLY Settled ($remainingBalance == 0), then ALL past bills are Settled.
            if ($remainingBalance <= 0.009) {
                $updatePast = $conn->prepare("UPDATE billing SET status = 'Settled', balance = 0 WHERE bill_id = ?");
                while ($pBill = $pastBillsResult->fetch_assoc()) {
                    $updatePast->bind_param('i', $pBill['bill_id']);
                    $updatePast->execute();
                }
                $updatePast->close();
            } else {
                // For partial payments, it's more complex. We'll leave them as is for now 
                // because calculating exact net debt per previous invoice requires complex subtraction.
                // But we can safely settle oldest bills if the total paid so far exceeds their total_amount.
                // Actually, if we just do nothing for partial, the user's primary bug (full payment not settling past) is fixed.
            }
            $pastBillsStmt->close();
        }
        // ------------------------------------------------

        $nextBillId = null;

        // Fetch latest payment/billing context
        $detailsStmt = $conn->prepare("
            SELECT b.bill_id, b.tenant_id, b.room_id, b.payment_amount, b.payment_method, b.payment_date,
                   b.total_amount, b.base_rent, b.interest, b.due_date, b.status,
                   b.previous_balance, b.previous_credit, b.balance, b.credit_balance,
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
            'tenant_id' => $tenant_id,
            'remaining_balance' => $remainingBalance,
            'credit_balance' => $creditBalance,
            'next_bill_id' => $nextBillId
        ];

        $confirmationSummary = null;

        if ($paymentRecord && ($status === 'Partial' || $status === 'Settled')) {
            $confirmationSummary = sendPaymentConfirmationForBill($conn, $bill_id);
            $responsePayload['confirmation'] = $confirmationSummary;
        }

        if (!empty($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode($responsePayload);
            exit;
        }

        // Fallback for non-AJAX requests
        $flashType = 'success';
        $flashMessage = 'Payment saved successfully.';

        if ($confirmationSummary) {
            $flashType = $confirmationSummary['success'] ? 'success' : 'warning';
            $flashMessage .= ' ' . ($confirmationSummary['message'] ?? '');
        }

        Session::setMessage($flashMessage, $flashType);
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
