<!-- Edit Bill Modal (per bill) -->
<div class="modal fade" id="editBillModal<?php echo $row['bill_id']; ?>" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post" action="update_bill.php">
        <div class="modal-header">
          <h5 class="modal-title">Edit Bill — <?php echo htmlspecialchars($tenant['tenant_name']); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">

          <!-- Hidden Fields -->
          <input type="hidden" name="bill_id" value="<?php echo $row['bill_id']; ?>">
          <input type="hidden" name="tenant_id" value="<?php echo $tenant_id; ?>">
          <input type="hidden" name="room_id" value="<?php echo $row['room_id']; ?>">
          <input type="hidden" name="base_rent" value="<?php echo $row['base_rent']; ?>">

          <!-- Due Date (readonly) -->
          <div class="mb-3">
            <label>Due Date</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($row['due_date']); ?>" readonly>
            <input type="hidden" name="due_date" value="<?php echo htmlspecialchars($row['due_date']); ?>">
          </div>

          

          <!-- Utility Fees -->
          <div id="edit-utility-container">
            <label>Utility Fees</label>
            <?php
              $utilityFees = json_decode($row['utility_fee'], true) ?? [];
              $utilityAmounts = json_decode($row['utility_amount'], true) ?? [];
              foreach ($utilityFees as $key => $fee):
                  $amount = $utilityAmounts[$key] ?? 0;
            ?>
            <div class="row mb-2 utility-row">
              <div class="col-md-6">
                <input type="text" name="utility_fee[]" class="form-control" placeholder="Utility Fee" value="<?php echo htmlspecialchars($fee); ?>">
              </div>
              <div class="col-md-5">
                <div class="input-group">
                  <span class="input-group-text">₱</span>
                  <input type="number" step="0.01" name="utility_amount[]" class="form-control" value="<?php echo number_format($amount,2,'.',''); ?>">
                </div>
              </div>
              <div class="col-md-1 d-flex align-items-center">
                <button type="button" class="btn btn-danger btn-sm remove-utility">&times;</button>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <button type="button" class="btn btn-sm btn-success mb-3" id="editAddUtility">+ Add Utility</button>

          <!-- Additional Charges -->
          <div id="edit-charge-container">
            <label>Additional Charges</label>
            <?php
              $addCharges = json_decode($row['add_charges'], true) ?? [];
              $addAmounts = json_decode($row['add_amount'], true) ?? [];
              foreach ($addCharges as $key => $charge):
                  $amount = $addAmounts[$key] ?? 0;
            ?>
            <div class="row mb-2 charge-row">
              <div class="col-md-6">
                <input type="text" name="add_charges[]" class="form-control" placeholder="Additional Charge" value="<?php echo htmlspecialchars($charge); ?>">
              </div>
              <div class="col-md-5">
                <div class="input-group">
                  <span class="input-group-text">₱</span>
                  <input type="number" step="0.01" name="add_amount[]" class="form-control" value="<?php echo number_format($amount,2,'.',''); ?>">
                </div>
              </div>
              <div class="col-md-1 d-flex align-items-center">
                <button type="button" class="btn btn-danger btn-sm remove-charge">&times;</button>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <button type="button" class="btn btn-sm btn-success mb-3" id="editAddCharge">+ Add Charge</button>

          <!-- Interest -->
          <div class="mb-3">
            <label>Interest</label>
            <input type="number" step="0.01" name="interest" class="form-control" value="<?php echo number_format(floatval($row['interest']),2,'.',''); ?>">
          </div>
        </div>
        <div class="modal-footer justify-content-center">
    <button type="submit" class="btn btn-primary">Update Bill</button>
    <button type="button" class="btn btn-secondary ms-2" data-bs-dismiss="modal">Close</button>
</div>
      </form>
    </div>
  </div>
</div>

<!-- JS for dynamic add/remove -->
<script>
document.addEventListener("DOMContentLoaded", function() {
  // Add Utility
  document.getElementById('editAddUtility').addEventListener('click', function() {
    let container = document.getElementById('edit-utility-container');
    let row = document.createElement('div');
    row.className = 'row mb-2 utility-row';
    row.innerHTML = `
      <div class="col-md-6"><input type="text" name="utility_fee[]" class="form-control" placeholder="Utility Fee"></div>
      <div class="col-md-5"><div class="input-group"><span class="input-group-text">₱</span><input type="number" step="0.01" name="utility_amount[]" class="form-control" value="0"></div></div>
      <div class="col-md-1 d-flex align-items-center"><button type="button" class="btn btn-danger btn-sm remove-utility">&times;</button></div>
    `;
    container.appendChild(row);
  });

  // Remove Utility
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-utility')) {
      e.target.closest('.utility-row').remove();
    }
  });

  // Add Charge
  document.getElementById('editAddCharge').addEventListener('click', function() {
    let container = document.getElementById('edit-charge-container');
    let row = document.createElement('div');
    row.className = 'row mb-2 charge-row';
    row.innerHTML = `
      <div class="col-md-6"><input type="text" name="add_charges[]" class="form-control" placeholder="Additional Charge"></div>
      <div class="col-md-5"><div class="input-group"><span class="input-group-text">₱</span><input type="number" step="0.01" name="add_amount[]" class="form-control" value="0"></div></div>
      <div class="col-md-1 d-flex align-items-center"><button type="button" class="btn btn-danger btn-sm remove-charge">&times;</button></div>
    `;
    container.appendChild(row);
  });

  // Remove Charge
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-charge')) {
      e.target.closest('.charge-row').remove();
    }
  });
});
</script>
