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
        $utilityFees = json_decode($row['utility_fee'], true) ?? [];
        $utilityAmounts = json_decode($row['utility_amount'], true) ?? [];
        if(!empty($utilityFees)){
            foreach($utilityFees as $i => $fee){
                $amt = number_format($utilityAmounts[$i] ?? 0, 2);
                $Reminder_Message .= "<li>Utility Fees: {$fee} – ₱{$amt}</li>";
            }
        } else {
            $Reminder_Message .= "<li>Utility Fees: – ₱0.00</li>";
        }

        // Additional Charges
        $addCharges = json_decode($row['add_charges'], true) ?? [];
        $addAmounts = json_decode($row['add_amount'], true) ?? [];
        if(!empty($addCharges)){
            foreach($addCharges as $i => $charge){
                $amt = number_format($addAmounts[$i] ?? 0, 2);
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
      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="sendReminderBtn">Send Notification</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>

<script>
document.getElementById('sendReminderBtn').addEventListener('click', function() {
    // Disable the button to prevent multiple clicks
    this.disabled = true;
    this.innerText = "Sending...";

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
            alert("Reminder sent successfully!");
        } else {
            alert("Failed to send reminder: " + data.message);
        }
        // Re-enable button
        document.getElementById('sendReminderBtn').disabled = false;
        document.getElementById('sendReminderBtn').innerText = "Send Notification";
    })
    .catch(err => {
        alert("Error sending reminder: " + err);
        document.getElementById('sendReminderBtn').disabled = false;
        document.getElementById('sendReminderBtn').innerText = "Send Notification";
    });
});


