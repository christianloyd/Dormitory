<!-- Payment Confirmation Modal -->
<div class="modal fade" id="paymentConfirmationModal" tabindex="-1" aria-labelledby="paymentConfirmationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="paymentConfirmationModalLabel">Payment Confirmation</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <?php
        // --- Payment confirmation message ---
        $Payment_Confirmation = "<strong>Ben and Sof Dormitory</strong><br>";
        $Payment_Confirmation .= "Purok 1A, Mati, San Miguel, ZDS<br><br>";
        $Payment_Confirmation .= "Good Day, <strong>{$tenant['tenant_name']}</strong>!<br><br>";
        $Payment_Confirmation .= "This is to confirm that your payment has been successfully recorded.<br><br>";

        $Payment_Confirmation .= "<strong>Tenant Name:</strong> {$tenant['tenant_name']}<br>";
        $Payment_Confirmation .= "<strong>Room Number:</strong> {$tenant['room_number']}<br><br>";

        $Payment_Confirmation .= "<strong>Balance:</strong> ₱" . number_format($balance, 2) . "<br>";
        $Payment_Confirmation .= "<strong>Credit Balance:</strong> ₱" . number_format($credit, 2) . "<br>";
        $Payment_Confirmation .= "<strong>Total Amount:</strong> ₱" . number_format($total_display, 2) . "<br>";
        $Payment_Confirmation .= "<strong>Payment Amount:</strong> ₱" . number_format($row['payment_amount'], 2) . "<br>";
        $Payment_Confirmation .= "<strong>Status:</strong> {$status}<br><br>";

        $Payment_Confirmation .= "Thank you for your prompt payment!";

        $paymentSmsPreviewData = composePaymentConfirmationSMSMessage(
            [
                'tenant_name' => $tenant['tenant_name'],
                'room_number' => $tenant['room_number'],
                'payment_date' => $row['payment_date'],
                'payment_amount' => $row['payment_amount'],
                'payment_method' => $row['payment_method'],
                'status' => $status,
                'base_rent' => $row['base_rent'],
                'interest' => $row['interest'],
                'total_amount' => $total_display,
                'due_date' => $row['due_date']
            ],
            $utilityItems,
            $additionalItems
        );
        $paymentSmsPreview = $paymentSmsPreviewData['message'];
        $paymentSmsChars = mb_strlen($paymentSmsPreview);
        $paymentSmsSegments = max(1, ceil($paymentSmsChars / 157));
        ?>
        <div style="word-wrap: break-word;"><?php echo $Payment_Confirmation; ?></div>
        <hr>
        <div class="mb-3">
          <h6 class="fw-bold">SMS Preview</h6>
          <pre class="bg-light p-3 border" style="white-space: pre-wrap;"><?php echo htmlspecialchars($paymentSmsPreview); ?></pre>
          <p class="small text-muted mb-0">Characters: <?php echo $paymentSmsChars; ?> &middot; estimated Segments: <?php echo $paymentSmsSegments; ?></p>
        </div>
      </div>

      <div class="modal-footer">
        <div id="paymentSmsStatusDiv" class="flex-grow-1 text-start" style="display:none;"></div>
        <button type="button" class="btn btn-success" id="sendPaymentConfirmBtn">
          <i class="fas fa-paper-plane"></i> Send Notification
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- JS: Send Notification -->
<script>
document.getElementById('sendPaymentConfirmBtn').addEventListener('click', function(){
    const btn = this;
    const statusDiv = document.getElementById('paymentSmsStatusDiv');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    statusDiv.style.display = 'block';
    statusDiv.innerHTML = '<div class="alert alert-info mb-0"><i class="fas fa-info-circle"></i> Sending SMS confirmation...</div>';

    fetch("send_payment_confirm.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ bill_id: "<?php echo $row['bill_id']; ?>" })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let html = '<div class="alert alert-success mb-0">';
            html += '<i class="fas fa-check-circle"></i> <strong>' + data.message + '</strong>';

            if (data.sms_results && data.sms_results.length) {
                html += '<ul class="mt-2 mb-0" style="font-size:0.9rem;">';
                data.sms_results.forEach(result => {
                    const icon = result.status === 'sent'
                        ? '<i class="fas fa-check-circle text-success"></i>'
                        : '<i class="fas fa-times-circle text-danger"></i>';
                    html += '<li>' + icon + ' ' + result.type + ' (' + result.number + '): ' + result.message + '</li>';
                });
                html += '</ul>';
            }

            html += '</div>';
            statusDiv.innerHTML = html;

            Swal.fire({
                title: 'Success!',
                text: data.message,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });

            const modal = bootstrap.Modal.getInstance(document.getElementById('paymentConfirmationModal'));
            if (modal) {
                modal.hide();
            }
        } else {
            statusDiv.innerHTML = '<div class="alert alert-danger mb-0"><i class="fas fa-exclamation-circle"></i> <strong>Failed:</strong> ' + data.message + '</div>';
            Swal.fire({
                title: 'Failed',
                text: 'Failed to send confirmation: ' + data.message,
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#d33'
            });
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Notification';
        }
    })
    .catch(err => {
        statusDiv.innerHTML = '<div class="alert alert-danger mb-0"><i class="fas fa-exclamation-circle"></i> <strong>Error:</strong> ' + err + '</div>';
        Swal.fire({
            title: 'Error',
            text: 'Error sending confirmation: ' + err,
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#d33'
        });
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Notification';
    });
});
</script>
