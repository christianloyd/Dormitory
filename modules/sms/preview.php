<?php
/**
 * SMS Module - SMS Message Preview & Character Counter
 * Test your SMS messages and see how many credits they will cost
 * Path: /modules/sms/preview.php
 */
require_once '../../includes/auth_check.php';

// Sample data for preview
$sample_tenant = [
    'tenant_name' => 'Juan Dela Cruz',
    'room_number' => '101',
    'contact_number' => '09123456789',
    'guardian_contact' => '09987654321',
    'due_date' => '2025-11-15',
    'payment_date' => null,
    'base_rent' => 1000.00,
    'interest' => 0.00,
    'payment_amount' => 0.00,
    'payment_method' => null
];

$utilityFees = ['Water', 'Electricity'];
$utilityAmounts = [100.00, 150.00];
$addCharges = [];
$addAmounts = [];

// Build CURRENT Payment Reminder Message
$current_reminder = "Ben and Sof Dormitory\n";
$current_reminder .= "Purok 1A, Mati, San Miguel, ZDS\n\n";
$current_reminder .= "Good day, {$sample_tenant['tenant_name']}!\n";
$current_reminder .= "This is a friendly reminder regarding your billing for your room.\n\n";
$current_reminder .= "Room Number: {$sample_tenant['room_number']}\n";
$current_reminder .= "Due Date: {$sample_tenant['due_date']}\n";
$current_reminder .= "Payment Date: " . ($sample_tenant['payment_date'] ?: "(Not yet paid)") . "\n\n";
$current_reminder .= "Charges:\n";
$current_reminder .= "- Base Rent: ₱" . number_format($sample_tenant['base_rent'],2) . "\n";
$current_reminder .= "- Interest: ₱" . number_format($sample_tenant['interest'],2) . "\n";

if (!empty($utilityFees)) {
    foreach ($utilityFees as $i => $fee) {
        $amt = number_format($utilityAmounts[$i] ?? 0,2);
        $current_reminder .= "- Utility Fees: {$fee} – ₱{$amt}\n";
    }
} else {
    $current_reminder .= "- Utility Fees: – ₱0.00\n";
}

if (!empty($addCharges)) {
    foreach ($addCharges as $i => $charge) {
        $amt = number_format($addAmounts[$i] ?? 0,2);
        $current_reminder .= "- Additional Charges: {$charge} – ₱{$amt}\n";
    }
} else {
    $current_reminder .= "- Additional Charges: – ₱0.00\n";
}

$current_reminder .= "\nPayment Details:\n";
$current_reminder .= "- Payment Amount: ₱" . number_format($sample_tenant['payment_amount'],2) . "\n";
$current_reminder .= "- Payment Method: " . ($sample_tenant['payment_method'] ?: "-") . "\n";
$current_reminder .= "\nPlease settle your payment within 3 days to avoid penalties. Thank you.";

// Build CURRENT Payment Confirmation Message
$payment_amount = 500.00;
$total_amount = 1250.00;
$remaining = $total_amount - $payment_amount;

$current_confirmation = "Ben and Sof Dormitory\n";
$current_confirmation .= "Purok 1A, Mati, San Miguel, ZDS\n\n";
$current_confirmation .= "Dear {$sample_tenant['tenant_name']},\n\n";
$current_confirmation .= "PAYMENT CONFIRMATION\n";
$current_confirmation .= str_repeat("-", 30) . "\n";
$current_confirmation .= "Room Number: {$sample_tenant['room_number']}\n";
$current_confirmation .= "Payment Date: Nov 06, 2025\n";
$current_confirmation .= "Amount Paid: ₱" . number_format($payment_amount, 2) . "\n";
$current_confirmation .= "Payment Method: GCash\n";
$current_confirmation .= "Status: Partial\n";
$current_confirmation .= "\nRemaining Balance: ₱" . number_format($remaining, 2) . "\n";
$current_confirmation .= "Due Date: Nov 15, 2025\n";
$current_confirmation .= "\nThank you for your payment!\n";
$current_confirmation .= "For inquiries, please contact us.";

// OPTIMIZED Messages
$optimized_reminder = "Ben & Sof Dorm\nPayment Reminder\n\n";
$optimized_reminder .= "Room: {$sample_tenant['room_number']}\n";
$optimized_reminder .= "Due: Nov 15\n";
$optimized_reminder .= "Rent: ₱1,000\n";
$optimized_reminder .= "Utilities: ₱250\n";
$optimized_reminder .= "Total: ₱1,250\n\n";
$optimized_reminder .= "Pay within 3 days.\nThank you!";

$optimized_confirmation = "Ben & Sof Dorm\nPayment Received!\n\n";
$optimized_confirmation .= "Room: {$sample_tenant['room_number']}\n";
$optimized_confirmation .= "Paid: ₱500\n";
$optimized_confirmation .= "Method: GCash\n";
$optimized_confirmation .= "Status: Partial\n";
$optimized_confirmation .= "Balance: ₱750\n\n";
$optimized_confirmation .= "Thank you!";

// Calculate costs
function calculate_cost($message) {
    $length = mb_strlen($message);
    $credits = ceil($length / 157);
    $recipients = 2; // tenant + guardian
    $total_credits = $credits * $recipients;
    return [
        'length' => $length,
        'credits_per_sms' => $credits,
        'total_credits' => $total_credits
    ];
}

$current_reminder_cost = calculate_cost($current_reminder);
$current_confirmation_cost = calculate_cost($current_confirmation);
$optimized_reminder_cost = calculate_cost($optimized_reminder);
$optimized_confirmation_cost = calculate_cost($optimized_confirmation);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Preview & Cost Calculator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f5f5f5; }
        .sms-preview {
            background: white;
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
        }
        .cost-badge {
            font-size: 1.2rem;
            padding: 10px 20px;
        }
        .comparison-table th {
            background: #007bff;
            color: white;
        }
        .savings {
            background: #d4edda;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-sms"></i> SMS Message Preview & Cost Calculator</h1>
            <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>

        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> <strong>IPROG Pricing:</strong> 1 Credit = 157 Characters |
            Each SMS is sent to 2 recipients (Tenant + Guardian)
        </div>

        <!-- Payment Reminder Comparison -->
        <div class="row mb-4">
            <div class="col-md-12">
                <h2>Payment Reminder Messages</h2>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">CURRENT Version (Detailed)</h5>
                    </div>
                    <div class="card-body">
                        <div class="sms-preview mb-3"><?= htmlspecialchars($current_reminder) ?></div>
                        <div class="text-center">
                            <span class="badge bg-warning cost-badge">
                                <?= $current_reminder_cost['length'] ?> chars
                            </span>
                            <span class="badge bg-danger cost-badge">
                                <?= $current_reminder_cost['credits_per_sms'] ?> credits/SMS
                            </span>
                            <span class="badge bg-dark cost-badge">
                                <?= $current_reminder_cost['total_credits'] ?> total credits
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">OPTIMIZED Version (Short)</h5>
                    </div>
                    <div class="card-body">
                        <div class="sms-preview mb-3"><?= htmlspecialchars($optimized_reminder) ?></div>
                        <div class="text-center">
                            <span class="badge bg-warning cost-badge">
                                <?= $optimized_reminder_cost['length'] ?> chars
                            </span>
                            <span class="badge bg-success cost-badge">
                                <?= $optimized_reminder_cost['credits_per_sms'] ?> credits/SMS
                            </span>
                            <span class="badge bg-dark cost-badge">
                                <?= $optimized_reminder_cost['total_credits'] ?> total credits
                            </span>
                        </div>
                        <div class="alert alert-success mt-3 mb-0">
                            <strong>Savings:</strong>
                            <?= $current_reminder_cost['total_credits'] - $optimized_reminder_cost['total_credits'] ?> credits
                            (<?= round((1 - $optimized_reminder_cost['total_credits'] / $current_reminder_cost['total_credits']) * 100) ?>% reduction)
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Confirmation Comparison -->
        <div class="row mb-4">
            <div class="col-md-12">
                <h2>Payment Confirmation Messages</h2>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">CURRENT Version (Detailed)</h5>
                    </div>
                    <div class="card-body">
                        <div class="sms-preview mb-3"><?= htmlspecialchars($current_confirmation) ?></div>
                        <div class="text-center">
                            <span class="badge bg-warning cost-badge">
                                <?= $current_confirmation_cost['length'] ?> chars
                            </span>
                            <span class="badge bg-danger cost-badge">
                                <?= $current_confirmation_cost['credits_per_sms'] ?> credits/SMS
                            </span>
                            <span class="badge bg-dark cost-badge">
                                <?= $current_confirmation_cost['total_credits'] ?> total credits
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">OPTIMIZED Version (Short)</h5>
                    </div>
                    <div class="card-body">
                        <div class="sms-preview mb-3"><?= htmlspecialchars($optimized_confirmation) ?></div>
                        <div class="text-center">
                            <span class="badge bg-warning cost-badge">
                                <?= $optimized_confirmation_cost['length'] ?> chars
                            </span>
                            <span class="badge bg-success cost-badge">
                                <?= $optimized_confirmation_cost['credits_per_sms'] ?> credits/SMS
                            </span>
                            <span class="badge bg-dark cost-badge">
                                <?= $optimized_confirmation_cost['total_credits'] ?> total credits
                            </span>
                        </div>
                        <div class="alert alert-success mt-3 mb-0">
                            <strong>Savings:</strong>
                            <?= $current_confirmation_cost['total_credits'] - $optimized_confirmation_cost['total_credits'] ?> credits
                            (<?= round((1 - $optimized_confirmation_cost['total_credits'] / $current_confirmation_cost['total_credits']) * 100) ?>% reduction)
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Cost Estimate -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Monthly Cost Estimate</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered comparison-table">
                    <thead>
                        <tr>
                            <th>Tenants</th>
                            <th>Current (Reminders)</th>
                            <th>Current (Confirmations)</th>
                            <th>Current Total</th>
                            <th>Optimized Total</th>
                            <th>Savings</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $tenant_counts = [10, 20, 30, 50, 100];
                        foreach ($tenant_counts as $count):
                            $current_total = ($count * $current_reminder_cost['total_credits']) +
                                           ($count * $current_confirmation_cost['total_credits']);
                            $optimized_total = ($count * $optimized_reminder_cost['total_credits']) +
                                             ($count * $optimized_confirmation_cost['total_credits']);
                            $savings = $current_total - $optimized_total;
                            $savings_pct = round(($savings / $current_total) * 100);
                        ?>
                        <tr>
                            <td><?= $count ?></td>
                            <td><?= $count * $current_reminder_cost['total_credits'] ?> credits</td>
                            <td><?= $count * $current_confirmation_cost['total_credits'] ?> credits</td>
                            <td><strong><?= $current_total ?> credits</strong></td>
                            <td><strong><?= $optimized_total ?> credits</strong></td>
                            <td class="savings"><?= $savings ?> credits (<?= $savings_pct ?>%)</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="card">
            <div class="card-body text-center">
                <h5>Want to use the optimized (shorter) messages?</h5>
                <p class="text-muted">I can update your system to use the optimized messages to reduce SMS costs.</p>
                <div class="btn-group" role="group">
                    <a href="sms_settings.php" class="btn btn-primary">
                        <i class="fas fa-cog"></i> Keep Current Messages
                    </a>
                    <button class="btn btn-success" onclick="alert('Please let your developer know you want to switch to optimized messages. They will update the code.')">
                        <i class="fas fa-save"></i> Switch to Optimized Messages
                    </button>
                </div>
                <p class="mt-3"><small class="text-muted">See <strong>SMS_MESSAGE_ANALYSIS.md</strong> for more details</small></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
