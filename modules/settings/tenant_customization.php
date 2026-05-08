<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tenant Customization - Ben and Sof Dormitory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Edit Tenant Information</h2>
    <p>Manage and update tenant records here.</p>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">Tenant updated successfully!</div>
    <?php endif; ?>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Tenant Name</th>
                <th>Room</th>
                <th>Deck Type</th>
                <th>Contact</th>
                <th>Guardian Contact</th>
                <th>Status</th>
                <th>Date Started</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $tenants = $conn->query("SELECT t.*, r.room_number FROM tenants t LEFT JOIN rooms r ON t.room_id = r.room_id ORDER BY t.tenant_id DESC");
            while($row = $tenants->fetch_assoc()):
            ?>
            <tr>
                <td><?= htmlspecialchars($row['tenant_name']) ?></td>
                <td><?= htmlspecialchars($row['room_number']) ?></td>
                <td><?= htmlspecialchars($row['deck_type']) ?></td>
                <td><?= htmlspecialchars($row['contact_number']) ?></td>
                <td><?= htmlspecialchars($row['guardian_contact']) ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td><?= htmlspecialchars($row['date_started']) ?></td>
                <td>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#updateTenantModal<?= $row['tenant_id'] ?>">Update</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Modals for each tenant -->
<?php
$tenants = $conn->query("SELECT t.*, r.room_number FROM tenants t LEFT JOIN rooms r ON t.room_id = r.room_id ORDER BY t.tenant_id DESC");
while($row = $tenants->fetch_assoc()):
?>
<div class="modal fade" id="updateTenantModal<?= $row['tenant_id'] ?>" tabindex="-1" aria-labelledby="updateTenantLabel<?= $row['tenant_id'] ?>" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <!-- ADD confirmation prompt here -->
      <form action="update_tenant.php" method="POST" enctype="multipart/form-data"
            onsubmit="return confirm('Are you sure you want to update tenant: <?= htmlspecialchars($row['tenant_name']) ?>?')">
        <div class="modal-header">
          <h5 class="modal-title" id="updateTenantLabel<?= $row['tenant_id'] ?>">Update Tenant: <?= htmlspecialchars($row['tenant_name']) ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="tenant_id" value="<?= $row['tenant_id'] ?>">

            <div class="mb-3">
                <label class="form-label">Tenant Name</label>
                <input type="text" name="tenant_name" class="form-control" value="<?= htmlspecialchars($row['tenant_name']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Room</label>
                <select name="room_id" class="form-control" required>
                    <?php
                    $rooms = $conn->query("SELECT * FROM rooms ORDER BY room_number ASC");
                    while($r = $rooms->fetch_assoc()):
                        $selected = ($r['room_id'] == $row['room_id']) ? "selected" : "";
                        echo "<option value='{$r['room_id']}' $selected>{$r['room_number']}</option>";
                    endwhile;
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Deck Type</label>
                <select name="deck_type" class="form-control">
                    <option value="">-- Select --</option>
                    <option value="Lower Deck" <?= $row['deck_type'] == 'Lower Deck' ? 'selected' : '' ?>>Lower Deck</option>
                    <option value="Upper Deck" <?= $row['deck_type'] == 'Upper Deck' ? 'selected' : '' ?>>Upper Deck</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($row['address']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Contact Number</label>
                <input type="text" name="contact_number" class="form-control" value="<?= htmlspecialchars($row['contact_number']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Guardian Contact</label>
                <input type="text" name="guardian_contact" class="form-control" value="<?= htmlspecialchars($row['guardian_contact']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-control" required>
                    <option value="Active" <?= $row['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= $row['status'] == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Date Started</label>
                <input type="date" name="date_started" class="form-control" value="<?= $row['date_started'] ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Profile Picture</label>
                <input type="file" name="profile_pic" class="form-control">
                <?php if($row['profile_pic']): ?>
                    <img src="<?= $row['profile_pic'] ?>" width="80" class="mt-2">
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Proof Picture</label>
                <input type="file" name="proof_pic" class="form-control">
                <?php if($row['proof_pic']): ?>
                    <img src="<?= $row['proof_pic'] ?>" width="80" class="mt-2">
                <?php endif; ?>
            </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Update Tenant</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endwhile; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
