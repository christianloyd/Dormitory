<?php
// Tenant start day
$start_day = intval(date('d', strtotime($tenant['date_started'])));

// Get last bill for this tenant
$last_bill_stmt = $conn->prepare("
    SELECT due_date FROM billing 
    WHERE tenant_id = ? 
    ORDER BY due_date DESC 
    LIMIT 1
");
$last_bill_stmt->bind_param("i", $tenant_id);
$last_bill_stmt->execute();
$last_bill_result = $last_bill_stmt->get_result();
$last_due_date = null;
if ($last_bill_result && $last_bill_result->num_rows > 0) {
    $last_due_date = $last_bill_result->fetch_assoc()['due_date'];
}

// Determine default due date
if ($last_due_date) {
    $default_due_date = date('Y-m-d', strtotime("+1 month", strtotime($last_due_date)));
    $billing_exists = true; // last month already exists
} else {
    $default_due_date = date('Y-m-d', strtotime('+1 month', strtotime($tenant['date_started'])));
    $billing_exists = false;
}
?>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Add Bill Modal -->
<div class="modal fade" id="addBillModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="addBillForm" method="post">
        <div class="modal-header">
          <h5 class="modal-title">Add Bill for <?php echo htmlspecialchars($tenant['tenant_name']); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="container-fluid">

            <?php if($billing_exists): ?>
              <div class="alert alert-warning">
                Last billing month exists. Default due date set to next month: 
                <?php echo date('F d, Y', strtotime($default_due_date)); ?>
              </div>
            <?php endif; ?>

            <input type="hidden" name="tenant_id" value="<?php echo $tenant_id; ?>">
            <input type="hidden" name="room_id" value="<?php echo $tenant['room_id']; ?>">
            <input type="hidden" name="base_rent" value="<?php echo $tenant['base_rent']; ?>">

            <!-- Due Date -->
            <div class="row mb-3">
              <div class="col-md-6">
                <label><b>Due Date</b></label>
                <input type="text" id="due_date_picker" name="due_date" 
                       class="form-control" required
                       value="<?php echo $default_due_date; ?>">
              </div>
            </div>

            <!-- Utility Fees & Amount -->
            <div id="utility-container">
              <div class="row mb-2 utility-row">
                <div class="col-md-6">
                  <label><b>Utility Fees</b></label>
                  <input type="text" name="utility_fee[]" class="form-control" placeholder="e.g. Water">
                </div>
                <div class="col-md-6">
                  <label><b>Utility Amount</b></label>
                  <div class="input-group">
                    <span class="input-group-text">₱</span>
                    <input type="number" step="0.01" name="utility_amount[]" class="form-control" value="0">
                  </div>
                </div>
              </div>
            </div>
            <button type="button" class="btn btn-sm btn-success mb-3" id="addUtility">+ Add Utility</button>
            <hr>

            <!-- Additional Charges & Amount -->
            <div id="charge-container">
              <div class="row mb-2 charge-row">
                <div class="col-md-6">
                  <label><b>Additional Charges</b></label>
                  <input type="text" name="add_charges[]" class="form-control" placeholder="e.g. Damages">
                </div>
                <div class="col-md-6">
                  <label><b>Charges Amount</b></label>
                  <div class="input-group">
                    <span class="input-group-text">₱</span>
                    <input type="number" step="0.01" name="add_amount[]" class="form-control" value="0">
                  </div>
                </div>
              </div>
            </div>
            <button type="button" class="btn btn-sm btn-success mb-3" id="addCharge">+ Add Charge</button>
            <hr>

            <!-- Payment Amount & Method -->
            <div class="row mb-3">
              <div class="col-md-6">
                <label><b>Payment Amount</b></label>
                <div class="input-group">
                  <span class="input-group-text">₱</span>
                  <input type="number" step="0.01" name="payment_amount" class="form-control" value="0">
                </div>
              </div>
              <div class="col-md-6">
                <label><b>Payment Method</b></label>
                <select name="payment_method" class="form-control">
                  <option value=""></option>
                  <option value="Cash">Cash</option>
                  <option value="GCash">GCash</option>
                  <option value="Bank Transfer">Bank Transfer</option>
                </select>
              </div>
            </div>

          </div>
        </div>

        <div class="modal-footer d-flex justify-content-center">
          <button type="submit" class="btn btn-primary">Save Bill</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Flatpickr -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    let startDay = <?php echo $start_day; ?>;
    let expectedNextMonth = "<?php echo $default_due_date; ?>";

    flatpickr("#due_date_picker", {
        dateFormat: "Y-m-d",
        defaultDate: expectedNextMonth,
        disable: [date => date.getDate() !== startDay]
    });
});

// Dynamic add/remove rows
document.addEventListener("DOMContentLoaded", function() {
  // Utility
  document.getElementById('addUtility').addEventListener('click', function() {
    let container = document.getElementById('utility-container');
    let row = document.createElement('div');
    row.className = "row mb-2 utility-row";
    row.innerHTML = `
      <div class="col-6"><input type="text" name="utility_fee[]" class="form-control" placeholder="e.g. Electricity"></div>
      <div class="col-5"><div class="input-group"><span class="input-group-text">₱</span><input type="number" step="0.01" name="utility_amount[]" class="form-control" value="0"></div></div>
      <div class="col-1 d-flex align-items-center"><button type="button" class="btn btn-danger btn-sm remove-utility">&times;</button></div>
    `;
    container.appendChild(row);
  });
  document.addEventListener('click', e => { if(e.target.classList.contains('remove-utility')) e.target.closest('.utility-row').remove(); });

  // Charges
  document.getElementById('addCharge').addEventListener('click', function() {
    let container = document.getElementById('charge-container');
    let row = document.createElement('div');
    row.className = "row mb-2 charge-row";
    row.innerHTML = `
      <div class="col-6"><input type="text" name="add_charges[]" class="form-control" placeholder="e.g. Repair"></div>
      <div class="col-5"><div class="input-group"><span class="input-group-text">₱</span><input type="number" step="0.01" name="add_amount[]" class="form-control" value="0"></div></div>
      <div class="col-1 d-flex align-items-center"><button type="button" class="btn btn-danger btn-sm remove-charge">&times;</button></div>
    `;
    container.appendChild(row);
  });
  document.addEventListener('click', e => { if(e.target.classList.contains('remove-charge')) e.target.closest('.charge-row').remove(); });
});

// Step 11–14: Confirmation + AJAX
const escapeHtml = (text) => {
    if (!text) return '';
    return text
        .toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
};

document.getElementById('addBillForm').addEventListener('submit', function(e){
    e.preventDefault();

    Swal.fire({
        title: 'Confirm',
        text: 'Are you sure you want to save this billing?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, save it',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if(result.isConfirmed) {
            const form = this;
            let formData = new FormData(form);
            fetch('save.php', { method:'POST', body:formData })
            .then(res => res.json())
            .then(data => {
                if(data.success){
                    const notice = data.billing_notice || {};

                    const details = [];
                    details.push('<p class="mb-2">Billing added successfully.</p>');

                    if (notice.message) {
                        details.push('<p class="mb-2"><strong>Status:</strong> ' + escapeHtml(notice.message) + '</p>');
                    }

                    if (Array.isArray(notice.sms_results) && notice.sms_results.length) {
                        const items = notice.sms_results.map(result => {
                            const type = escapeHtml(result.type || 'Recipient');
                            const number = escapeHtml(result.number || 'N/A');
                            const statusMsg = escapeHtml(result.message || result.status || '');
                            const statusClass = (result.status === 'sent') ? 'text-success' : 'text-danger';
                            const icon = (result.status === 'sent') ? 'fa-check-circle' : 'fa-times-circle';
                            return `<li><i class="fas ${icon} ${statusClass}"></i> ${type} (${number}): ${statusMsg}</li>`;
                        }).join('');
                        details.push('<div class="text-start"><p class="mb-1"><strong>SMS Delivery</strong></p><ul class="mb-0 ps-3">' + items + '</ul></div>');
                    }

                    if (notice.sms_preview) {
                        details.push('<div class="text-start mt-3"><p class="mb-1"><strong>SMS Preview</strong></p><pre class="bg-light p-3 border" style="white-space: pre-wrap;">' + escapeHtml(notice.sms_preview) + '</pre></div>');
                    }

                    const noticeIcon = notice.success ? 'success' : 'info';
                    const noticeTitle = notice.success ? 'Billing Notice Sent' : 'Billing Added';

                    Swal.fire({
                        title: noticeTitle,
                        html: details.join(''),
                        icon: noticeIcon,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#198754',
                        width: 600
                    }).then(() => {
                        const modalEl = document.getElementById('addBillModal');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) {
                            modal.hide();
                        }
                        window.location.href = `view.php?tenant_id=${data.tenant_id}`;
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message,
                        icon: 'error',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#d33'
                    });
                }
            }).catch(err => {
                Swal.fire({
                    title: 'Error',
                    text: 'Error: ' + err,
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#d33'
                });
            });
        }
    });
});
</script>
