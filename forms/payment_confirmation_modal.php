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
        ?>
        <div style="word-wrap: break-word;"><?php echo $Payment_Confirmation; ?></div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-success" id="sendPaymentConfirmBtn">Send Notification</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- JS: Send Notification -->
<script>
document.getElementById('sendPaymentConfirmBtn').addEventListener('click', function(){
    window.location.href = "send_payment_confirm.php?tenant_id=<?php echo $tenant['tenant_id']; ?>";
});
</script>
