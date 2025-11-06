<!-- ✅ Include SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- 💳 Payment Modal (Bootstrap) -->
<div class="modal fade" id="paymentModal<?php echo $row['bill_id']; ?>" tabindex="-1" aria-labelledby="paymentModalLabel<?php echo $row['bill_id']; ?>" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <!-- 👇 Updated form to trigger SweetAlert confirmation -->
      <form method="POST" action="process_payment.php" onsubmit="return confirmPayment<?php echo $row['bill_id']; ?>(event)">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="paymentModalLabel<?php echo $row['bill_id']; ?>">
            Payment for <?php echo htmlspecialchars($row['tenant_name']); ?>
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="bill_id" value="<?php echo $row['bill_id']; ?>">
          <input type="hidden" name="tenant_id" value="<?php echo $tenant_id; ?>">

          <div class="mb-3">
            <label><b>Payment Date</b></label>
            <input type="text" class="form-control" name="payment_date" value="<?php echo date('Y-m-d H:i:s'); ?>" readonly>
          </div>

          <div class="mb-3">
            <label><b>Total Amount</b></label>
            <input type="text" class="form-control" name="total_amount" value="<?php echo number_format($total_display, 2); ?>" readonly>
          </div>

          <div class="mb-3">
            <label><b>Payment Amount</b></label>
            <input type="number" step="0.01" name="payment_amount" class="form-control" required>
          </div>

          <div class="mb-3">
            <label><b>Payment Method</b></label>
            <select name="payment_method" class="form-control" required>
              <option value="">-- Select Method --</option>
              <option value="Cash">Cash</option>
              <option value="GCash">GCash</option>
              <option value="Bank Transfer">Bank Transfer</option>
            </select>
          </div>
        </div>

        <div class="modal-footer justify-content-center">
          <button type="submit" class="btn btn-success">Submit Payment</button>
          <button type="button" class="btn btn-secondary ms-2" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ✅ SweetAlert2 Confirmation Script -->
<script>
function confirmPayment<?php echo $row['bill_id']; ?>(event) {
  event.preventDefault(); // Stop the normal submission first
  const form = event.target.closest('form');
  const tenantName = "<?php echo addslashes($row['tenant_name']); ?>";

  Swal.fire({
    title: 'Confirm Payment',
    html: `Are you sure you want to save this payment for <b>${tenantName}</b>?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Yes, confirm',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#198754', // Bootstrap green
    cancelButtonColor: '#6c757d'   // Bootstrap gray
  }).then((result) => {
    if (result.isConfirmed) {
      form.submit(); // Proceed only if confirmed
    }
  });

  return false; // Block default form submit until confirmed
}
</script>
