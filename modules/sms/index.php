<?php
/**
 * SMS Module - SMS Logs
 * Path: /modules/sms/index.php
 */
require_once '../../includes/auth_check.php';

// Initialize SMS Helper
$smsHelper = new SMSHelper($conn);

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Build filters array
$filters = [];
if (!empty($status_filter)) {
    $filters['status'] = $status_filter;
}
if (!empty($date_from)) {
    $filters['date_from'] = $date_from;
}
if (!empty($date_to)) {
    $filters['date_to'] = $date_to;
}

// Get SMS logs
$logs = $smsHelper->getAllSMSLogs($filters, $limit, $offset);

// Get SMS statistics
$stats = $smsHelper->getSMSStatistics();

// Calculate total credits used (1 credit = 150 characters = ₱1.00)
$credits_sql = "SELECT message FROM sms_logs WHERE status = 'sent'";
$credits_result = $conn->query($credits_sql);
$total_credits = 0;

while ($row = $credits_result->fetch_assoc()) {
    $message_length = strlen($row['message']);
    $credits = ceil($message_length / 150); // 1 credit per 150 characters
    $total_credits += $credits;
}
$total_cost = $total_credits; // 1 credit = ₱1.00

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM sms_logs WHERE 1=1";
if (!empty($status_filter)) {
    $count_sql .= " AND status = '$status_filter'";
}
if (!empty($date_from)) {
    $count_sql .= " AND DATE(date_sent) >= '$date_from'";
}
if (!empty($date_to)) {
    $count_sql .= " AND DATE(date_sent) <= '$date_to'";
}
$total_records = $conn->query($count_sql)->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Logs - Ben & Sof Dormitory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!--<link href="css/style.css" rel="stylesheet">-->
    <style>
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 0.25rem;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-sent {
            background-color: #d4edda;
            color: #155724;
        }
        .status-failed {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-disabled {
            background-color: #e2e3e5;
            color: #383d41;
        }
        .message-preview {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
        }
        .stat-card {
            border-left: 4px solid;
        }
        .stat-card.total {
            border-left-color: #007bff;
        }
        .stat-card.sent {
            border-left-color: #28a745;
        }
        .stat-card.failed {
            border-left-color: #dc3545;
        }
        .stat-card.today {
            border-left-color: #ffc107;
        }
    </style>
</head>
<body>
    <?php include '../../includes/sidebar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="bi bi-chat-dots"></i> SMS Logs</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="location.reload()">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="card stat-card total">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Total SMS</h6>
                                <h3 class="mb-0"><?= number_format($stats['total']) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card sent">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Sent</h6>
                                <h3 class="mb-0 text-success"><?= number_format($stats['sent']) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card failed">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Failed</h6>
                                <h3 class="mb-0 text-danger"><?= number_format($stats['failed']) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card today">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Today</h6>
                                <h3 class="mb-0 text-warning"><?= number_format($stats['today']) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card" style="border-left-color: #17a2b8;">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Credits Used</h6>
                                <h3 class="mb-0 text-info"><?= number_format($total_credits) ?></h3>
                                <small class="text-muted">150 chars/credit</small>
                            </div>
                        </div>
                    </div>
                    <!--<div class="col-md-2">
                        <div class="card stat-card" style="border-left-color: #6f42c1;">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Total Cost</h6>
                                <h3 class="mb-0 text-purple">₱<?= number_format($total_cost, 2) ?></h3>
                                <small class="text-muted">1 credit = ₱1.00</small>
                            </div>
                        </div>
                    </div>-->
                </div>

                <!-- Filters -->
                <div class="card mb-3">
                    <div class="card-body">
                        <form method="GET" action="index.php" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="sent" <?= $status_filter === 'sent' ? 'selected' : '' ?>>Sent</option>
                                    <option value="failed" <?= $status_filter === 'failed' ? 'selected' : '' ?>>Failed</option>
                                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="disabled" <?= $status_filter === 'disabled' ? 'selected' : '' ?>>Disabled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date From</label>
                                <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date To</label>
                                <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i> Filter
                                    </button>
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="bi bi-x"></i> Clear
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- SMS Logs Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">SMS Messages (<?= number_format($total_records) ?> records)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date/Time</th>
                                        <th>Tenant</th>
                                        <th>Recipient</th>
                                        <th>Type</th>
                                        <th>Contact Number</th>
                                        <th>Message Preview</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($logs)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">
                                                <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                                <p class="mb-0 mt-2">No SMS logs found</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($logs as $log): ?>
                                            <tr>
                                                <td>
                                                    <small><?= date('M d, Y', strtotime($log['date_sent'])) ?></small><br>
                                                    <small class="text-muted"><?= date('h:i A', strtotime($log['date_sent'])) ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($log['tenant_name'] ?? 'N/A') ?></td>
                                                <td>
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($log['recipient_type']) ?></span>
                                                </td>
                                                <td><?= htmlspecialchars($log['recipient_type']) ?></td>
                                                <td><small><?= htmlspecialchars($log['contact_number']) ?></small></td>
                                                <td>
                                                    <div class="message-preview" title="Click to view full message"
                                                         onclick="showMessage(<?= htmlspecialchars(json_encode($log['message']), ENT_QUOTES) ?>)">
                                                        <?= htmlspecialchars(substr($log['message'], 0, 50)) ?>...
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?= strtolower($log['status']) ?>">
                                                        <?= ucfirst($log['status']) ?>
                                                    </span>
                                                    <?php if (!empty($log['http_code'])): ?>
                                                        <br><small class="text-muted">HTTP: <?= $log['http_code'] ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary"
                                                            onclick="viewDetails(<?= htmlspecialchars(json_encode($log), ENT_QUOTES) ?>)">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="card-footer">
                            <nav>
                                <ul class="pagination mb-0 justify-content-center">
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&status=<?= $status_filter ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Message Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">SMS Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <pre id="messageContent" style="white-space: pre-wrap; font-family: inherit;"></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">SMS Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailsContent">
                    <!-- Details will be populated by JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showMessage(message) {
            document.getElementById('messageContent').textContent = message;
            new bootstrap.Modal(document.getElementById('messageModal')).show();
        }

        function viewDetails(log) {
            // Calculate credits used
            const messageLength = log.message.length;
            const credits = Math.ceil(messageLength / 150);

            const html = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Basic Information</h6>
                        <table class="table table-sm">
                            <tr><th>Date Sent:</th><td>${log.date_sent}</td></tr>
                            <tr><th>Tenant:</th><td>${log.tenant_name || 'N/A'}</td></tr>
                            <tr><th>Recipient Type:</th><td>${log.recipient_type}</td></tr>
                            <tr><th>Contact Number:</th><td>${log.contact_number}</td></tr>
                            <tr><th>Status:</th><td><span class="status-badge status-${log.status.toLowerCase()}">${log.status}</span></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Technical Details</h6>
                        <table class="table table-sm">
                            <tr><th>Message ID:</th><td>${log.message_id || 'N/A'}</td></tr>
                            <tr><th>HTTP Code:</th><td>${log.http_code || 'N/A'}</td></tr>
                            <tr><th>Error Message:</th><td>${log.error_message || 'None'}</td></tr>
                        </table>
                        <h6 class="mt-3">Credit Information</h6>
                        <table class="table table-sm">
                            <tr><th>Message Length:</th><td>${messageLength} characters</td></tr>
                            <tr><th>Credits Used:</th><td><strong class="text-primary">${credits}</strong> credit${credits > 1 ? 's' : ''}</td></tr>
                        </table>
                    </div>
                </div>
                <div class="mt-3">
                    <h6>Message Content</h6>
                    <div class="alert alert-light">
                        <pre style="white-space: pre-wrap; margin: 0; font-family: inherit;">${log.message}</pre>
                    </div>
                </div>
            `;
            document.getElementById('detailsContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('detailsModal')).show();
        }
    </script>
</body>
</html>
