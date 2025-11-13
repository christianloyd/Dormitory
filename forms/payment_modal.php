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

      if (data.sms_preview) {
        showPaymentPreview<?php echo $row['bill_id']; ?>(data);
      } else {
        Swal.fire({
          title: 'Payment Saved',
          text: data.message || 'Payment recorded successfully.',
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

function showPaymentPreview<?php echo $row['bill_id']; ?>(data) {
  const previewHtml = `
    <div class="text-start">
      <p class="mb-2">SMS preview for <strong>${escapeHtml<?php echo $row['bill_id']; ?>(data.tenant_name || '')}</strong>:</p>
      <pre class="bg-light p-3 border" style="white-space: pre-wrap;">${escapeHtml<?php echo $row['bill_id']; ?>(data.sms_preview)}</pre>
      <p class="small text-muted mb-0">Characters: ${data.character_count || 0} · Segments: ${data.segments || 1}</p>
    </div>
  `;

  Swal.fire({
    title: 'Send Payment Confirmation?',
    html: previewHtml,
    icon: 'info',
    showCancelButton: true,
    confirmButtonText: 'Send SMS',
    cancelButtonText: 'Skip SMS',
    confirmButtonColor: '#198754',
    cancelButtonColor: '#6c757d',
    allowOutsideClick: false,
    width: 600
  }).then(result => {
    if (result.isConfirmed) {
      sendPaymentConfirmation<?php echo $row['bill_id']; ?>(data.bill_id);
    } else {
      Swal.fire({
        title: 'Payment Saved',
        text: 'Payment recorded without sending SMS.',
        icon: 'success'
      }).then(() => window.location.reload());
    }
  });
}

function sendPaymentConfirmation<?php echo $row['bill_id']; ?>(billId) {
  Swal.fire({
    title: 'Sending SMS...',
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });

  fetch('send_payment_confirm.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ bill_id: billId })
  })
    .then(response => response.json())
    .then(data => {
      if (!data.success) {
        throw new Error(data.message || 'Failed to send confirmation SMS.');
      }

      Swal.fire({
        title: 'SMS Sent',
        html: `
          <div class="text-start">
            <p class="mb-2"><strong>${escapeHtml<?php echo $row['bill_id']; ?>(data.message)}</strong></p>
            <pre class="bg-light p-3 border" style="white-space: pre-wrap;">${escapeHtml<?php echo $row['bill_id']; ?>(data.sms_preview)}</pre>
            <p class="small text-muted mb-0">Characters: ${data.character_count || 0} · Segments: ${Math.max(1, Math.ceil((data.character_count || 0) / 157))}</p>
          </div>
        `,
        icon: 'success',
        width: 600
      }).then(() => window.location.reload());
    })
    .catch(error => {
      Swal.fire({
        title: 'SMS Error',
        html: 'Failed to send confirmation:<br>' + escapeHtml<?php echo $row['bill_id']; ?>(error.message || error),
        icon: 'error'
      }).then(() => window.location.reload());
    });
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
