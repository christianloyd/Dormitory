<?php
include 'db.php';

// Get current SMS setting from database
$sms_enabled = true; // default
$stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'sms_enabled'");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $sms_enabled = ($row['setting_value'] == '1');
    }
    $stmt->close();
}

// Handle SMS settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_sms_settings'])) {
    $new_sms_enabled = isset($_POST['sms_enabled']) ? 1 : 0;

    // Update or insert setting
    $stmt = $conn->prepare("
        INSERT INTO settings (setting_key, setting_value, description)
        VALUES ('sms_enabled', ?, 'Enable or disable SMS notifications')
        ON DUPLICATE KEY UPDATE setting_value = ?
    ");

    $stmt->bind_param("ss", $new_sms_enabled, $new_sms_enabled);

    if ($stmt->execute()) {
        $sms_enabled = ($new_sms_enabled == 1);
        $success_message = "SMS settings updated successfully!";
    } else {
        $error_message = "Failed to update SMS settings: " . $stmt->error;
    }
    $stmt->close();
}

// Get SMS statistics
$smsHelper = new SMSHelper($conn);
$stats = $smsHelper->getSMSStatistics();
?>

<h2><i class="fas fa-sms"></i> SMS Notification Settings</h2>

<?php if (isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?= $success_message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> <?= $error_message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-cog"></i> SMS Configuration</h5>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="sms_enabled" name="sms_enabled"
                               <?= $sms_enabled ? 'checked' : '' ?> style="transform: scale(1.5);">
                        <label class="form-check-label ms-2" for="sms_enabled" style="font-size: 1.1rem;">
                            <strong>Enable SMS Notifications</strong>
                        </label>
                    </div>
                    <small class="text-muted ms-5">
                        When enabled, SMS will be sent to tenants and guardians for payment reminders and confirmations.
                    </small>
                </div>
            </div>

            <hr>

            <div class="row mb-3">
                <div class="col-md-12">
                    <h6><i class="fas fa-info-circle"></i> API Configuration</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <tr>
                                <th width="200">API Provider:</th>
                                <td><span class="badge bg-info">IPROG SMS Gateway</span></td>
                            </tr>
                            <tr>
                                <th>API URL:</th>
                                <td><code><?= SMS_API_URL ?></code></td>
                            </tr>
                            <tr>
                                <th>Sender Name:</th>
                                <td><strong><?= SMS_SENDER ?></strong></td>
                            </tr>
                            <tr>
                                <th>API Token:</th>
                                <td>
                                    <code><?= substr(SMS_API_TOKEN, 0, 10) ?>...<?= substr(SMS_API_TOKEN, -10) ?></code>
                                    <small class="text-muted">(configured in db.php)</small>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2 d-md-flex">
                <button type="submit" name="update_sms_settings" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Settings
                </button>
                <a href="sms_logs.php" class="btn btn-outline-secondary">
                    <i class="fas fa-list"></i> View SMS Logs
                </a>
            </div>
        </form>
    </div>
</div>

<!-- SMS Statistics Card -->
<div class="card mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> SMS Statistics</h5>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-3">
                <div class="p-3 border rounded">
                    <h2 class="text-primary"><?= number_format($stats['total']) ?></h2>
                    <p class="text-muted mb-0">Total SMS</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 border rounded">
                    <h2 class="text-success"><?= number_format($stats['sent']) ?></h2>
                    <p class="text-muted mb-0">Successfully Sent</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 border rounded">
                    <h2 class="text-danger"><?= number_format($stats['failed']) ?></h2>
                    <p class="text-muted mb-0">Failed</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 border rounded">
                    <h2 class="text-warning"><?= number_format($stats['today']) ?></h2>
                    <p class="text-muted mb-0">Today</p>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <a href="sms_logs.php" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-external-link-alt"></i> View Detailed Logs
            </a>
        </div>
    </div>
</div>

<!-- SMS Features Info -->
<div class="card">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-info-circle"></i> SMS Notification Features</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6><i class="fas fa-bell"></i> Payment Reminders</h6>
                <ul>
                    <li>Sent manually from billing page</li>
                    <li>Includes billing details and due dates</li>
                    <li>Sent to both tenant and guardian</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6><i class="fas fa-check-circle"></i> Payment Confirmations</h6>
                <ul>
                    <li>Automatically sent after payment processing</li>
                    <li>Confirms payment amount and method</li>
                    <li>Shows remaining balance (if partial)</li>
                </ul>
            </div>
        </div>

        <hr>

        <div class="alert alert-warning mb-0">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Note:</strong> Make sure tenant and guardian phone numbers are in valid format
            (09XXXXXXXXX or 639XXXXXXXXX) for SMS to be sent successfully.
        </div>
    </div>
</div>

<style>
.form-check-input:checked {
    background-color: #28a745;
    border-color: #28a745;
}
</style>
