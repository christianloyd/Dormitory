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

<!-- ✅ SweetAlert2 Confirmation & SMS Preview Script -->
<script>
function confirmPayment<?php echo $row['bill_id']; ?>(event) {
  event.preventDefault();
  const form = event.target.closest('form');

  if (!form.reportValidity()) {
    return false;
  }

  const formData = new FormData(form);
  formData.append('ajax', '1');

  Swal.fire({
    title: 'Saving payment...',
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });

  fetch('process_payment.php', {
    method: 'POST',
    body: formData
  })
    .then(response => response.json())
    .then(data => {
      Swal.close();

      if (!data.success) {
        throw new Error(data.message || 'Failed to save payment.');
      }

      const paymentModalEl = document.getElementById('paymentModal<?php echo $row['bill_id']; ?>');
      const paymentModalInstance = bootstrap.Modal.getInstance(paymentModalEl) || new bootstrap.Modal(paymentModalEl);
      paymentModalInstance.hide();

      const confirmation = data.confirmation || null;
      const baseMessage = data.message || 'Payment recorded successfully.';

      if (confirmation) {
        const icon = confirmation.success ? 'success' : 'warning';
        let html = '<div class="text-start">';
        const headline = confirmation.message || baseMessage;
        html += '<p class="mb-2"><strong>' + escapeHtml<?php echo $row['bill_id']; ?>(headline) + '</strong></p>';

        if (confirmation.sms_preview) {
          html += '<pre class="bg-light p-3 border" style="white-space: pre-wrap;">' +
            escapeHtml<?php echo $row['bill_id']; ?>(confirmation.sms_preview) + '</pre>';
          const charCount = confirmation.character_count || confirmation.characterCount || 0;
          const segments = confirmation.segments || Math.max(1, Math.ceil(charCount / 157));
          html += '<p class="small text-muted mb-0">Characters: ' + charCount + ' · Segments: ' + segments + '</p>';
        }

        if (confirmation.sms_results && confirmation.sms_results.length) {
          html += '<div class="mt-3"><h6>Delivery Status</h6><ul class="mb-0" style="font-size:0.9rem;">';
          confirmation.sms_results.forEach(result => {
            const statusIcon = result.status === 'sent'
              ? '<i class="fas fa-check-circle text-success"></i>'
              : '<i class="fas fa-times-circle text-danger"></i>';
            const line = statusIcon + ' ' +
              escapeHtml<?php echo $row['bill_id']; ?>(result.type || 'Recipient') +
              ' (' + escapeHtml<?php echo $row['bill_id']; ?>(result.number || '-') + '): ' +
              escapeHtml<?php echo $row['bill_id']; ?>(result.message || '');
            html += '<li>' + line + '</li>';
          });
          html += '</ul></div>';
        }

        html += '</div>';

        Swal.fire({
          title: confirmation.success ? 'Payment & SMS Sent' : 'Payment Saved',
          html: html,
          icon: icon,
          width: 600
        }).then(() => window.location.reload());
      } else {
        Swal.fire({
          title: 'Payment Saved',
          text: baseMessage,
          icon: 'success'
        }).then(() => window.location.reload());
      }
    })
    .catch(error => {
      Swal.close();
      Swal.fire({
        title: 'Error',
        html: 'Failed to save payment:<br>' + escapeHtml<?php echo $row['bill_id']; ?>(error.message || error),
        icon: 'error',
        confirmButtonText: 'OK'
      });
    });

  return false;
}
function escapeHtml<?php echo $row['bill_id']; ?>(text) {
  if (!text) return '';
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
</script>
