<?php
/**
 * Tenants Module - Main Tenants Page
 * Path: /modules/tenants/index.php
 */
require_once '../../includes/auth_check.php';

// --- Handle Edit Tenant Form Submission ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['edit_tenant_id'])) {
    $tenant_id = intval($_POST['edit_tenant_id']);

    // Fetch existing tenant info
    $sql = "SELECT * FROM tenants WHERE tenant_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $tenant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $tenant = $result->fetch_assoc();

    // Use POST if available, else use existing value
    $tenant_name     = !empty($_POST['tenant_name']) ? trim($_POST['tenant_name']) : $tenant['tenant_name'];
    $room_id         = !empty($_POST['room_id']) ? intval($_POST['room_id']) : $tenant['room_id'];
    $deck_type       = !empty($_POST['deck_type']) ? trim($_POST['deck_type']) : $tenant['deck_type'];
    $address         = !empty($_POST['address']) ? trim($_POST['address']) : $tenant['address'];
    $contact_number  = !empty($_POST['contact_number']) ? trim($_POST['contact_number']) : $tenant['contact_number'];
    $guardian_contact= !empty($_POST['guardian_contact']) ? trim($_POST['guardian_contact']) : $tenant['guardian_contact'];
    $status          = !empty($_POST['status']) ? $_POST['status'] : $tenant['status'];
    $date_started    = !empty($_POST['date_started']) ? $_POST['date_started'] : $tenant['date_started'];

    // validate contact numbers
    if (!preg_match('/^09[0-9]{9}$/', $contact_number) ||
        !preg_match('/^09[0-9]{9}$/', $guardian_contact)) {
        $_SESSION['swal_error'] = "Invalid Contact Number! Must start with 09 and be 11 digits.";
        header("Location: index.php");
        exit;
    }

    // update tenant
    $stmt = $conn->prepare("UPDATE tenants 
        SET tenant_name=?, room_id=?, deck_type=?, address=?, contact_number=?, guardian_contact=?, status=?, date_started=? 
        WHERE tenant_id=?");
    $stmt->bind_param("sissssssi", $tenant_name, $room_id, $deck_type, $address, $contact_number, $guardian_contact, $status, $date_started, $tenant_id);

    if ($stmt->execute()) {
        $_SESSION['swal_success'] = "Tenant '{$tenant_name}' has been updated successfully!";
    } else {
        $_SESSION['swal_error'] = "Error updating tenant: " . $stmt->error;
    }

    header("Location: index.php");
    exit;
}

// --- Handle Add Tenant Form Submission ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tenant_name'])) {
    $tenant_name = trim($_POST['tenant_name']);
    $room_id = intval($_POST['room_id']);
    $deck_type = trim($_POST['deck_type']);
    $address = trim($_POST['address']);
    $contact_number = trim($_POST['contact_number']);
    $guardian_contact = trim($_POST['guardian_contact']);
    $status = 'Active'; // default every newly added tenant to Active
    $date_started = $_POST['date_started'];

    // Validate contact numbers
    if (!preg_match('/^09[0-9]{9}$/', $contact_number)) {
        $_SESSION['swal_error'] = "Invalid Contact Number! It must start with 09 and be 11 digits.";
        header("Location: index.php");
        exit;
    }

    if (!preg_match('/^09[0-9]{9}$/', $guardian_contact)) {
        $_SESSION['swal_error'] = "Invalid Guardian Contact! It must start with 09 and be 11 digits.";
        header("Location: index.php");
        exit;
    }

    $profile_pic = "";
    if (!empty($_FILES['profile_pic']['name'])) {
        $profile_pic = "uploads/" . basename($_FILES['profile_pic']['name']);
        move_uploaded_file($_FILES['profile_pic']['tmp_name'], $profile_pic);
    }

    $proof_pic = "";
    if (!empty($_FILES['proof_pic']['name'])) {
        $proof_pic = "uploads/" . basename($_FILES['proof_pic']['name']);
        move_uploaded_file($_FILES['proof_pic']['tmp_name'], $proof_pic);
    }

    // Check room type
    $roomQuery = $conn->prepare("SELECT room_type FROM rooms WHERE room_id=?");
    $roomQuery->bind_param("i", $room_id);
    $roomQuery->execute();
    $roomType = $roomQuery->get_result()->fetch_assoc()['room_type'];
    if ($roomType == "Whole Room") $deck_type = NULL;

    // Insert tenant
    $stmt = $conn->prepare("INSERT INTO tenants (tenant_name, profile_pic, proof_pic, room_id, deck_type, address, contact_number, guardian_contact, status, date_started) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssissssss", $tenant_name, $profile_pic, $proof_pic, $room_id, $deck_type, $address, $contact_number, $guardian_contact, $status, $date_started);

    if ($stmt->execute()) {
        // Update room availability
        $roomQuery = $conn->prepare("SELECT capacity FROM rooms WHERE room_id=?");
        $roomQuery->bind_param("i", $room_id);
        $roomQuery->execute();
        $capacity = $roomQuery->get_result()->fetch_assoc()['capacity'];

        $countQuery = $conn->prepare("SELECT COUNT(*) as tenant_count FROM tenants WHERE room_id=?");
        $countQuery->bind_param("i", $room_id);
        $countQuery->execute();
        $tenantCount = $countQuery->get_result()->fetch_assoc()['tenant_count'];

        $status_room = ($tenantCount >= $capacity) ? 'Full' : 'Available';
        $updateRoom = $conn->prepare("UPDATE rooms SET available=?, status=? WHERE room_id=?");
        $updateRoom->bind_param("isi", $tenantCount, $status_room, $room_id);
        $updateRoom->execute();

        $_SESSION['swal_success'] = "Tenant '{$tenant_name}' has been added successfully!";
        header("Location: index.php");
        exit;
    } else {
        $_SESSION['swal_error'] = "Error: " . $stmt->error;
        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Tenant Info</title>
<link rel="stylesheet" href="../../css/tenants.css">
<link rel="stylesheet" href="../../css/newtenant.css">
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>
<div class="main-content">

<?php
// fetch header image from DB
$header = $conn->query("SELECT setting_value FROM settings WHERE setting_name='header_image'")->fetch_assoc();
$header_pic = $header ? BASE_PATH . '/' . $header['setting_value'] : BASE_PATH . "/uploads/default_header.png";

// fetch profile image from DB
$profile = $conn->query("SELECT setting_value FROM settings WHERE setting_name='profile_image'")->fetch_assoc();
$profile_pic = $profile ? BASE_PATH . '/' . $profile['setting_value'] : BASE_PATH . "/uploads/default_profile.png";
?>

<div class="d-flex justify-content-between align-items-center mt-0">
    <h2>Tenant Information</h2>
    <div class="profile-box d-flex align-items-center">
        <img src="<?php echo $header_pic; ?>" 
             alt="Header Picture" 
             class="rounded-circle" 
             width="50" height="50">
        <span class="ms-2">Admin</span> <!-- Always display "admin" -->
    </div>
</div>
        <!-- Custom HR -->
        <hr style="width: 100%; margin: 10px auto; border: 1px solid #140d0dff;">


   <div class="search-container">
    <button class="top-btn" onclick="openModal()">Add New Tenant</button>
    
    <form class="search-form" onsubmit="return false;">
          <button type="submit" class="btn btn-login">Search</button>
        <input type="text" id="searchInput" placeholder="Search tenant name...">
      
    </form>
</div>
    <div style="clear: both;"></div> <!-- para mo-break ang float -->


<?php
$activeQuery = $conn->query("SELECT COUNT(*) as active_count FROM tenants WHERE status='Active'");
$inactiveQuery = $conn->query("SELECT COUNT(*) as inactive_count FROM tenants WHERE status='Inactive'");

$activeCount = $activeQuery->fetch_assoc()['active_count'];
$inactiveCount = $inactiveQuery->fetch_assoc()['inactive_count'];
?>

<div style="margin-top:10px; font-weight:bold;">
    <span style="color:#5A7D7C;">Active Tenants: <?= $activeCount; ?></span><br>
    <span style="color:#dc3545;">Inactive Tenants: <?= $inactiveCount; ?></span>
</div>

<!-- FILTER TABS -->
<ul class="nav nav-tabs mb-3" id="tenantTabs">
  <li class="nav-item">
    <a class="nav-link active" data-bs-toggle="tab" href="#activeTenants">Active Tenants</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" data-bs-toggle="tab" href="#inactiveTenants">Inactive Tenants</a>
  </li>
</ul>

<div class="tab-content">

    <!-- ACTIVE TENANTS -->
  <div class="tab-pane fade show active" id="activeTenants">
    <div class="table-container" style="overflow-x:auto; overflow-y:auto; max-height:500px; border:1px solid #ddd; border-radius:8px; padding:5px;">
      <table id="tenantTable" style="width:100%; min-width:1000px; border-collapse:collapse;">
        <thead style="background-color:#f5f5f5; position:sticky; top:0; z-index:1;">
          <tr>
            <th>Profile</th>
            <th>Proof</th>
            <th>Name</th>
            <th>Room</th>
            <th>Room Type</th>
            <th>Deck</th>
            <th>Address</th>
            <th>Contact</th>
            <th>Guardian</th>
            <th>Date Started</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $sql = "SELECT t.*, r.room_number, r.room_type
                  FROM tenants t
                  LEFT JOIN rooms r ON t.room_id = r.room_id
                  WHERE t.status='Active'
                  ORDER BY t.created_at DESC";
          $result = $conn->query($sql);
          if ($result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                  echo '<tr>
                          <td><img src="'.BASE_PATH.'/'.htmlspecialchars($row['profile_pic']).'" class="profile-pic"></td>
                          <td><img src="'.BASE_PATH.'/'.htmlspecialchars($row['proof_pic']).'" class="proof-pic"></td>
                          <td>'.htmlspecialchars($row['tenant_name']).'</td>
                          <td>'.htmlspecialchars($row['room_number']).'</td>
                          <td>'.htmlspecialchars($row['room_type']).'</td>
                          <td>'.htmlspecialchars($row['deck_type']).'</td>
                          <td>'.htmlspecialchars($row['address']).'</td>
                          <td>'.htmlspecialchars($row['contact_number']).'</td>
                          <td>'.htmlspecialchars($row['guardian_contact']).'</td>
                          <td>'.htmlspecialchars($row['date_started']).'</td>
                          <td><span style="color:green;">Active</span></td>
                          <td>
                            <a href="javascript:void(0);" class="btn btn-edit" onclick="openEditModal(
                              '.$row['tenant_id'].', 
                              \''.addslashes($row['tenant_name']).'\',
                              \''.addslashes($row['address']).'\',
                              \''.$row['contact_number'].'\',
                              \''.$row['guardian_contact'].'\',
                              \''.$row['room_id'].'\',
                              \''.$row['deck_type'].'\')">Edit</a>
                           <a href="delete.php?id='.$row['tenant_id'].'" class="btn btn-delete" data-id="'.$row['tenant_id'].'" data-name="'.htmlspecialchars($row['tenant_name'], ENT_QUOTES).'"> Inactive
                           </a>
                          </td>
                        </tr>';
              }
          } else {
              echo "<tr><td colspan='12' style='text-align:center;'>No active tenants</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- INACTIVE TENANTS -->
<div class="tab-pane fade" id="inactiveTenants">
  <div class="table-container" style="overflow-x:auto; overflow-y:auto; max-height:500px; border:1px solid #ddd; border-radius:8px; padding:5px;">
    <table id="inactiveTenantTable" style="width:100%; min-width:1000px; border-collapse:collapse;">
      <thead style="background-color:#f5f5f5; position:sticky; top:0; z-index:1;">
        <tr>
          <th>Profile</th>
          <th>Proof</th>
          <th>Name</th>
          <th>Room</th>
          <th>Address</th>
          <th>Contact</th>
          <th>Guardian</th>
          <th>Date Started</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $inactive_sql = "SELECT t.*, r.room_number 
                         FROM tenants t 
                         LEFT JOIN rooms r ON t.room_id = r.room_id 
                         WHERE t.status='Inactive' 
                         ORDER BY t.created_at DESC";
        $inactive_result = $conn->query($inactive_sql);
        if ($inactive_result->num_rows > 0) {
            while ($row = $inactive_result->fetch_assoc()) {
                echo '<tr>
                        <td><img src="'.BASE_PATH.'/'.htmlspecialchars($row['profile_pic']).'" class="profile-pic"></td>
                        <td><img src="'.BASE_PATH.'/'.htmlspecialchars($row['proof_pic']).'" class="proof-pic"></td>
                        <td>'.htmlspecialchars($row['tenant_name']).'</td>
                        <td>'.htmlspecialchars($row['room_number']).'</td>
                        <td>'.htmlspecialchars($row['address']).'</td>
                        <td>'.htmlspecialchars($row['contact_number']).'</td>
                        <td>'.htmlspecialchars($row['guardian_contact']).'</td>
                        <td>'.htmlspecialchars($row['date_started']).'</td>
                        <td><span style="color:red;">Inactive</span></td>
                      </tr>';
            }
        } else {
            echo "<tr><td colspan='9' style='text-align:center;'>No inactive tenants</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>
</div>

<!-- LINK TO ADD TENANT MODAL -->
<?php include '../../forms/add_tenant_modal_form.php'; ?>

<!-- LINK TO EDIT TENANT MODAL -->
<?php include '../../forms/edit_tenant_modal_form.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= BASE_PATH ?>/js/sweetalert-helpers.js"></script>
<script>

// Display SweetAlert messages from PHP session
<?php if (isset($_SESSION['swal_success'])): ?>
    AlertHelper.success('Success', '<?= addslashes($_SESSION['swal_success']) ?>');
    <?php unset($_SESSION['swal_success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['swal_error'])): ?>
    AlertHelper.error('Error', '<?= addslashes($_SESSION['swal_error']) ?>');
    <?php unset($_SESSION['swal_error']); ?>
<?php endif; ?>



function openModal() { document.getElementById('tenantModal').style.display = 'flex'; }
function closeModal() { document.getElementById('tenantModal').style.display = 'none'; }

function checkDeck() {
    const roomSelect = document.getElementById("roomSelect");
    const deckSelect = document.getElementById("deckSelect");

    const selectedOption = roomSelect.options[roomSelect.selectedIndex];
    if (!selectedOption) return;

    const roomType = selectedOption.dataset.roomType;

    if (roomType === "Whole Room") {
        deckSelect.value = "";
        deckSelect.disabled = true;
        deckSelect.querySelectorAll('option').forEach(opt => {
            if(opt.value !== "") opt.text = opt.value;
        });
        return;
    }

    deckSelect.disabled = false;

    const upperCount = parseInt(selectedOption.dataset.upper);
    const lowerCount = parseInt(selectedOption.dataset.lower);
    const upperOccupied = parseInt(selectedOption.dataset.upperOccupied);
    const lowerOccupied = parseInt(selectedOption.dataset.lowerOccupied);

    const upperAvailable = upperCount - upperOccupied;
    const lowerAvailable = lowerCount - lowerOccupied;

    const upperOption = deckSelect.querySelector('option[value="Upper Deck"]');
    const lowerOption = deckSelect.querySelector('option[value="Lower Deck"]');

    upperOption.disabled = (upperAvailable <= 0);
    lowerOption.disabled = (lowerAvailable <= 0);

    upperOption.style.color = (upperAvailable <= 0) ? "gray" : "black";
    lowerOption.style.color = (lowerAvailable <= 0) ? "gray" : "black";

    upperOption.text = upperAvailable > 0 ? `Upper Deck (${upperAvailable} Available)` : "Upper Deck (Available)";
    lowerOption.text = lowerAvailable > 0 ? `Lower Deck (${lowerAvailable} Available)` : "Lower Deck (Available)";

    if (deckSelect.value === "Upper Deck" && upperAvailable <= 0) deckSelect.value = "";
    if (deckSelect.value === "Lower Deck" && lowerAvailable <= 0) deckSelect.value = "";
}

document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("searchInput");
    const tenantTableBody = document.querySelector("#activeTenants tbody");
    const notFoundBanner = document.getElementById("notFound");

    if (!searchInput || !tenantTableBody) {
        return;
    }

    searchInput.addEventListener("input", function () {
        const filter = this.value.toLowerCase().trim();
        const rows = tenantTableBody.querySelectorAll("tr");
        let found = false;

        rows.forEach(row => {
            const nameCell = row.querySelector("td:nth-child(3)");
            const nameText = nameCell ? nameCell.textContent.toLowerCase() : "";

            if (filter && nameText.includes(filter)) {
                row.style.display = "";
                row.classList.add("highlight");
                found = true;
            } else if (!filter) {
                row.style.display = "";
                row.classList.remove("highlight");
                found = true;
            } else {
                row.style.display = "none";
                row.classList.remove("highlight");
            }
        });

        if (notFoundBanner) {
            notFoundBanner.style.display = found ? "none" : "block";
        }
    });
});

function confirmSave() {
    const tenantInput = document.querySelector('input[name="tenant_name"]');
    const tenantName = tenantInput.value.trim().toUpperCase();
    return confirm(`Are you sure you want to save tenant "${tenantName}"?`);
}

function capitalizeName(input) {
    let words = input.value.toLowerCase().split(' ');
    for (let i = 0; i < words.length; i++) {
        if(words[i].length > 0) {
            words[i] = words[i][0].toUpperCase() + words[i].substr(1);
        }
    }
    input.value = words.join(' ');
}

// ---------- ADD THIS PART FOR FULLNAME + ADDRESS ---------- //
document.addEventListener("DOMContentLoaded", function () {
    const nameField = document.querySelector('input[name="tenant_name"]');
    const addressField = document.querySelector('input[name="address"]');

    if (nameField) {
        nameField.addEventListener("input", function () {
            capitalizeName(this);
        });
    }

    if (addressField) {
        addressField.addEventListener("input", function () {
            capitalizeName(this);
        });
    }
});

// ----------- DELETE TENANT WITH SWEETALERT2 -----------

document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();

        const tenantRow = this.closest('tr');
        const url = this.href;
        const tenantName = this.getAttribute('data-name') || 'this tenant';

        Swal.fire({
            title: 'Are you sure?',
            text: `Do you want to delete ${tenantName}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(url)
                    .then(res => res.text())
                    .then(data => {
                        if (data.trim() === "success") {
                            Swal.fire({
                                title: 'Deleted!',
                                text: `${tenantName} has been deleted successfully.`,
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            });

                            // Remove row from table
                            tenantRow.remove();
                        } else {
                            Swal.fire('Error', 'Failed to delete tenant.', 'error');
                        }
                    })
                    .catch(() => {
                        Swal.fire('Error', 'Something went wrong while deleting.', 'error');
                    });
            }
        });
    });
});


// ----------- EDIT TENANT MODAL FUNCTIONS -----------

// Open Edit Tenant modal and populate fields
function openEditModal(id, name, address, contact, guardian, room_id, deck_type){
    document.getElementById("editTenantId").value = id;
    document.getElementById("editTenantName").value = name;
    document.getElementById("editTenantAddress").value = address;
    document.getElementById("editTenantContact").value = contact;
    document.getElementById("editTenantGuardian").value = guardian;
    document.getElementById("roomSelectEdit").value = room_id;
    document.getElementById("deckSelectEdit").value = deck_type;

    // Update deck availability same as Add Tenant
    checkDeckEdit();

    // Show modal
    let modal = new bootstrap.Modal(document.getElementById('editTenantModal'));
    modal.show();
}

// Check deck availability for EDIT modal
function checkDeckEdit() {
    const roomSelect = document.getElementById("roomSelectEdit");
    const deckSelect = document.getElementById("deckSelectEdit");
    const deckWrapper = document.getElementById("deckWrapperEdit");

    const selectedOption = roomSelect.options[roomSelect.selectedIndex];
    if (!selectedOption) return;

    const roomType = selectedOption.dataset.roomType;

    if (roomType === "Whole Room") {
        deckWrapper.style.display = "none";
        deckSelect.value = "";
        return;
    }

    deckWrapper.style.display = "block";

    const upperCount = parseInt(selectedOption.dataset.upper);
    const lowerCount = parseInt(selectedOption.dataset.lower);
    const upperOccupied = parseInt(selectedOption.dataset.upperOccupied);
    const lowerOccupied = parseInt(selectedOption.dataset.lowerOccupied);

    const upperAvailable = upperCount - upperOccupied;
    const lowerAvailable = lowerCount - lowerOccupied;

    const upperOption = deckSelect.querySelector('option[value="Upper Deck"]');
    const lowerOption = deckSelect.querySelector('option[value="Lower Deck"]');

    // Upper
    if (upperAvailable > 0) {
        upperOption.disabled = false;
        upperOption.style.color = "black";
        upperOption.text = `Upper Deck (${upperAvailable} Available)`;
    } else {
        upperOption.disabled = true;
        upperOption.style.color = "gray";
        upperOption.text = "Upper Deck (0 Available)";
    }

    // Lower
    if (lowerAvailable > 0) {
        lowerOption.disabled = false;
        lowerOption.style.color = "black";
        lowerOption.text = `Lower Deck (${lowerAvailable} Available)`;
    } else {
        lowerOption.disabled = true;
        lowerOption.style.color = "gray";
        lowerOption.text = "Lower Deck (0 Available)";
    }

    if (deckSelect.value === "Upper Deck" && upperAvailable <= 0) deckSelect.value = "";
    if (deckSelect.value === "Lower Deck" && lowerAvailable <= 0) deckSelect.value = "";
}

</script>
</body>
</html>
