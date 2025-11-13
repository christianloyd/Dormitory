<?php
require_once __DIR__ . '/../helpers/BillingItems.php';

$utilityItems = getBillingUtilityItems($conn, $row['bill_id']);
$additionalItems = getBillingAdditionalItems($conn, $row['bill_id']);
$smsPreviewData = composeReminderSMSMessage(
    [
        'tenant_name' => $tenant['tenant_name'],
        'room_number' => $tenant['room_number'],
        'due_date' => $row['due_date'],
        'base_rent' => $row['base_rent'],
        'interest' => $row['interest']
    ],
    $utilityItems,
    $additionalItems
);
$smsPreview = $smsPreviewData['message'];
$smsCharacters = mb_strlen($smsPreview);
$smsSegments = max(1, ceil($smsCharacters / 157));
?>

<!-- Reminder Message Modal -->
<div class="modal fade" id="reminderMessageModal" tabindex="-1" aria-labelledby="reminderMessageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="reminderMessageModalLabel">Payment Reminder</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body">
        <?php
        // --- Reminder Message Layout ---
        $Reminder_Message = "
        <div class='mb-3'>
          <h4 class='fw-bold'>Ben and Sof Dormitory</h4>
          <p class='mb-0'>Purok 1A, Mati, San Miguel, Zamboanga del Sur</p>
        </div>
        <div class='mb-3'>
          <p>Good day, <strong>{$tenant['tenant_name']}</strong>!</p>
          <p>This is a friendly reminder regarding your billing for your room. Kindly see the details below:</p>
        </div>
        <table class='table table-sm table-bordered'>
          <tbody>
            <tr><th>Room Number</th><td>{$tenant['room_number']}</td></tr>
            <tr><th>Due Date</th><td>{$row['due_date']}</td></tr>
            <tr><th>Payment Date</th><td>" . ($row['payment_date'] ?: "(Not yet paid)") . "</td></tr>
          </tbody>
        </table>

        <h6 class='mt-3'>Charges</h6>
        <ul>";
        $Reminder_Message .= "<li>Base Rent: ₱" . number_format($row['base_rent'],2) . "</li>";
        $Reminder_Message .= "<li>Interest: ₱" . number_format($row['interest'],2) . "</li>";

        // Utility Fees
        if(!empty($utilityItems)){
            foreach($utilityItems as $item){
                $fee = htmlspecialchars($item['label']);
                $amt = number_format($item['amount'] ?? 0, 2);
                $Reminder_Message .= "<li>Utility Fees: {$fee} – ₱{$amt}</li>";
            }
        } else {
            $Reminder_Message .= "<li>Utility Fees: – ₱0.00</li>";
        }
        
        // Additional Charges
        if(!empty($additionalItems)){
            foreach($additionalItems as $item){
                $charge = htmlspecialchars($item['label']);
                $amt = number_format($item['amount'] ?? 0, 2);
                $Reminder_Message .= "<li>Additional Charges: {$charge} – ₱{$amt}</li>";
            }
        } else {
            $Reminder_Message .= "<li>Additional Charges: – ₱0.00</li>";
        }

        $Reminder_Message .= "</ul>";

        $Reminder_Message .= "
        <h6 class='mt-3'>Balances</h6>
        <ul>
          <li>Current Balance: ₱" . number_format($balance,2) . "</li>
          <li>Previous Balance: ₱" . number_format($prev_balance,2) . "</li>
          <li>Credit Balance: ₱" . number_format($credit,2) . "</li>
          <li>Previous Credit Balance: ₱" . number_format($prev_credit,2) . "</li>
        </ul>

        <h6 class='mt-3'>Payment Details</h6>
        <ul>
          <li>Payment Amount: ₱" . number_format($row['payment_amount'],2) . "</li>
          <li>Payment Method: " . ($row['payment_method'] ?: "-") . "</li>
        </ul>

        <h5 class='mt-3'>Total Amount Due: ₱" . number_format($total_display,2) . "</h5>
        <p><strong>Status:</strong> {$status}</p>

        <p class='mt-3'>Please settle your payment within <strong>3 days</strong> from today to avoid penalties. Thank you for your prompt attention.</p>
        ";

        echo $Reminder_Message;
        ?>
        <hr>
        <div class="mb-3">
          <h6 class="fw-bold">SMS Preview</h6>
          <pre class="bg-light p-3 border" style="white-space: pre-wrap;"><?php echo htmlspecialchars($smsPreview); ?></pre>
          <p class="small text-muted mb-0">Characters: <?php echo $smsCharacters; ?> &middot; Estimated Segments: <?php echo $smsSegments; ?></p>
        </div>
      </div>
      
      <div class="modal-footer">
        <div id="smsStatusDiv" class="flex-grow-1 text-start" style="display:none;">
            <!-- SMS status will be displayed here -->
        </div>
        <button type="button" class="btn btn-primary" id="sendReminderBtn">
            <i class="fas fa-paper-plane"></i> Send SMS Notification
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>

<script>
document.getElementById('sendReminderBtn').addEventListener('click', function() {
    // Disable the button to prevent multiple clicks
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending SMS...';

    // Show status div
    const statusDiv = document.getElementById('smsStatusDiv');
    statusDiv.style.display = 'block';
    statusDiv.innerHTML = '<div class="alert alert-info mb-0"><i class="fas fa-info-circle"></i> Sending SMS notification...</div>';

    // Prepare data
    const tenant_id = "<?php echo $tenant['tenant_id']; ?>";

    fetch("send_reminder.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ tenant_id: tenant_id })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success){
            // Build success message with SMS results
            let statusHtml = '<div class="alert alert-success mb-0">';
            statusHtml += '<i class="fas fa-check-circle"></i> <strong>' + data.message + '</strong><br>';

            if(data.sms_results && data.sms_results.length > 0) {
                statusHtml += '<small class="mt-2 d-block">Delivery Status:</small>';
                statusHtml += '<ul class="mb-0" style="font-size: 0.9rem;">';
                data.sms_results.forEach(function(result) {
                    const icon = result.status === 'sent' ?
                        '<i class="fas fa-check-circle text-success"></i>' :
                        '<i class="fas fa-times-circle text-danger"></i>';
                    statusHtml += '<li>' + icon + ' ' + result.type + ' (' + result.number + '): ' + result.message + '</li>';
                });
                statusHtml += '</ul>';
            }

            if(data.sent_to && data.sent_to.length > 0) {
                statusHtml += '<small class="mt-2 d-block">Sent to: ' + data.sent_to.join(', ') + '</small>';
            }

            statusHtml += '</div>';
            statusDiv.innerHTML = statusHtml;

            // Show success alert with SweetAlert2
            Swal.fire({
                title: 'Success!',
                text: "SMS Reminder sent successfully to " + (data.sent_to ? data.sent_to.length : 0) + " recipient(s)!",
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });

            // Close modal after successful send
            const modal = bootstrap.Modal.getInstance(document.getElementById('reminderMessageModal'));
            if (modal) {
                modal.hide();
            }
        } else {
            statusDiv.innerHTML = '<div class="alert alert-danger mb-0"><i class="fas fa-exclamation-circle"></i> <strong>Failed:</strong> ' + data.message + '</div>';
            Swal.fire({
                title: 'Failed',
                text: "Failed to send reminder: " + data.message,
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#d33'
            });

            // Re-enable button on failure
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send SMS Notification';
        }
    })
    .catch(err => {
        statusDiv.innerHTML = '<div class="alert alert-danger mb-0"><i class="fas fa-exclamation-circle"></i> <strong>Error:</strong> ' + err + '</div>';
        Swal.fire({
            title: 'Error',
            text: "Error sending reminder: " + err,
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#d33'
        });

        // Re-enable button
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send SMS Notification';
    });
});
</script>