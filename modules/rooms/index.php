<?php
/**
 * Rooms Module - Main Rooms Page
 * Path: /modules/rooms/index.php
 */
require_once '../../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // --- ADD ROOM ---
    if (isset($_POST['room_number']) && !isset($_POST['edit_room_id'])) {
        $room_number = trim($_POST['room_number']);
        $room_type = trim($_POST['room_type']);
        $capacity = intval($_POST['capacity']);
        $upper_deck = intval($_POST['upper_deck_count']);
        $lower_deck = intval($_POST['lower_deck_count']);
        $price = floatval($_POST['price']);

        if ($room_type === "Whole Room") {
            $capacity = 1;
            $upper_deck = 0;
            $lower_deck = 0;
        }

        // Check duplicate only among ACTIVE rooms
        $check = $conn->prepare("SELECT COUNT(*) as count FROM rooms WHERE room_number=? AND record_status='Active'");
        $check->bind_param("s", $room_number);
        $check->execute();
        $exists = $check->get_result()->fetch_assoc();
        if ($exists['count'] > 0) {
            $_SESSION['message'] = "Room number '$room_number' already exists among active rooms!";
            header("Location: index.php");
            exit;
        }

        // Insert new room
        $stmt = $conn->prepare("INSERT INTO rooms (room_number, room_type, capacity, upper_deck_count, lower_deck_count, price, record_status) VALUES (?, ?, ?, ?, ?, ?, 'Active')");
        $stmt->bind_param("ssiiid", $room_number, $room_type, $capacity, $upper_deck, $lower_deck, $price);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Room '$room_number' added successfully!";
            header("Location: index.php");
            exit;
        }
    }

    // --- EDIT ROOM ---
    elseif (isset($_POST['edit_room_id'])) {
        $room_id = intval($_POST['edit_room_id']);
        $room_number = trim($_POST['room_number']);
        $room_type = trim($_POST['room_type']);
        $capacity = intval($_POST['capacity']);
        $upper_deck = intval($_POST['upper_deck_count']);
        $lower_deck = intval($_POST['lower_deck_count']);
        $price = floatval($_POST['price']);

        if ($room_type === "Whole Room") {
            $capacity = 1;
            $upper_deck = 0;
            $lower_deck = 0;
        }

        // Check duplicate for edit among ACTIVE rooms
        $check = $conn->prepare("SELECT COUNT(*) as count FROM rooms WHERE room_number=? AND room_id!=? AND record_status='Active'");
        $check->bind_param("si", $room_number, $room_id);
        $check->execute();
        $exists = $check->get_result()->fetch_assoc();
        if ($exists['count'] > 0) {
            $_SESSION['message'] = "Room number '$room_number' already exists among active rooms!";
            header("Location: index.php");
            exit;
        }

        // Update room
        $stmt = $conn->prepare("UPDATE rooms SET room_number=?, room_type=?, capacity=?, upper_deck_count=?, lower_deck_count=?, price=? WHERE room_id=?");
        $stmt->bind_param("ssiiidi", $room_number, $room_type, $capacity, $upper_deck, $lower_deck, $price, $room_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Room '$room_number' updated successfully!";
            header("Location: index.php");
            exit;
        }
    }

} // end of POST check

// Fetch all active rooms
$roomResult = $conn->query("SELECT * FROM rooms WHERE record_status='Active' ORDER BY room_number ASC");

// Fetch all inactive rooms
$inactiveResult = $conn->query("SELECT * FROM rooms WHERE record_status='Inactive' ORDER BY room_number ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/new_room.css">
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container-fluid">
      
    <?php
// fetch header image from DB
$header = $conn->query("SELECT setting_value FROM settings WHERE setting_name='header_image'")->fetch_assoc();
$header_pic = $header ? BASE_PATH . '/' . $header['setting_value'] : BASE_PATH . "/uploads/default_header.png";

// fetch profile image from DB
$profile = $conn->query("SELECT setting_value FROM settings WHERE setting_name='profile_image'")->fetch_assoc();
$profile_pic = $profile ? BASE_PATH . '/' . $profile['setting_value'] : BASE_PATH . "/uploads/default_profile.png";
?>

<div class="d-flex justify-content-between align-items-center mt-0">
    <h2>Room Information</h2>
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
    <!-- Add New Room button left -->
    <button class="top-btn" onclick="openModal('addRoomModal')">Add New Room</button>

     <form class="search-form" onsubmit="return false;">
        <button type="submit" class="btn btn-login">Search</button>
        <input type="text" id="searchInput" placeholder="Search room number...">
        
    </form>
</div>

    <?php if (isset($_SESSION['message'])): ?>
        <div id="flash-message" class="flash-message"><?= $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <script>
            setTimeout(() => {
                let msg = document.getElementById("flash-message");
                if(msg){ msg.style.transition="opacity 1s"; msg.style.opacity="0"; setTimeout(()=>msg.remove(),1000); }
            }, 3000);
        </script>
    <?php endif; ?>


<?php
// Count Active Rooms
$activeResult = $conn->query("SELECT COUNT(*) as totalActive FROM rooms WHERE record_status='Active'");
$totalActive = $activeResult->fetch_assoc()['totalActive'];

// Count Inactive Rooms
$inactiveResult = $conn->query("SELECT COUNT(*) as totalInactive FROM rooms WHERE record_status='Inactive'");
$totalInactive = $inactiveResult->fetch_assoc()['totalInactive'];
?>
<div style="margin-top:10px; font-weight:bold; color:#5A7D7C;">
    Total Active Rooms: <?= $totalActive; ?>
</div>
<div style="margin-top:10px; font-weight:bold; color:#dc3545;">
    <a href="inactive.php" style="text-decoration:none; color:#dc3545;">Total Inactive Rooms: <?= $totalInactive; ?></a>
</div>
<ul class="nav nav-tabs mb-3" id="roomTabs">
  <li class="nav-item">
    <a class="nav-link active" href="index.php">Active Rooms</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" href="inactive.php">Inactive Rooms</a>
  </li>
</ul>


<!-- ACTIVE ROOMS TABLE -->
<div class="table-container" style="overflow-x:auto; overflow-y:auto; max-height:500px; border:1px solid #ddd; border-radius:8px; padding:5px;">
    <table id="roomTable" style="width:100%; min-width:900px; border-collapse:collapse;">
      <thead style="background-color:#f5f5f5; position:sticky; top:0; z-index:1;">
        <tr>
          <th>Room Number</th>
          <th>Room Type</th>
          <th>Capacity</th>
          <th>Available</th>
          <th>Status</th>
          <th>Upper Decks</th>
          <th>Lower Decks</th>
          <th>Price</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody id="roomBody">
<?php 
while ($row = mysqli_fetch_assoc($roomResult)): 
    $stmt = $conn->prepare("SELECT 
        SUM(CASE WHEN deck_type='Upper Deck' THEN 1 ELSE 0 END) as upper_tenants,
        SUM(CASE WHEN deck_type='Lower Deck' THEN 1 ELSE 0 END) as lower_tenants,
        COUNT(*) as total_tenants
        FROM tenants 
        WHERE room_id=? AND status='Active'");
    $stmt->bind_param("i", $row['room_id']); 
    $stmt->execute();
    $tenantData = $stmt->get_result()->fetch_assoc();
    $total_tenants = $tenantData['total_tenants'] ?? 0;
    $upper_tenants = $tenantData['upper_tenants'] ?? 0;
    $lower_tenants = $tenantData['lower_tenants'] ?? 0;
    $available = $row['capacity'] - $total_tenants;
?>
        <tr>
            <td class="room-number"><?= htmlspecialchars($row['room_number']) ?></td>
            <td><?= htmlspecialchars($row['room_type']) ?></td>
            <td><?= htmlspecialchars($row['capacity']) ?></td>
            <td><?= $available ?></td>
            <td class="room-status"><?= $available > 0 ? 'Available' : 'Occupied' ?></td>
            <td><?= $upper_tenants . '/' . $row['upper_deck_count'] ?></td>
            <td><?= $lower_tenants . '/' . $row['lower_deck_count'] ?></td>
            <td><?= number_format($row['price'],2) ?></td>
            <td>
                <button class="btn btn-edit" onclick="openEditModal(
                    <?= $row['room_id'] ?>,
                    '<?= addslashes($row['room_number']) ?>',
                    '<?= $row['room_type'] ?>',
                    <?= $row['capacity'] ?>,
                    <?= $row['upper_deck_count'] ?>,
                    <?= $row['lower_deck_count'] ?>,
                    <?= $row['price'] ?>
                )">Edit</button>

                <button 
                  class="btn btn-delete" 
                  data-id="<?= $row['room_id']; ?>" 
                  data-room="<?= htmlspecialchars($row['room_number']); ?>">
                  Delete
                </button>
            </td>
        </tr>
<?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- LINK TO ADD ROOM MODAL -->
<?php include '../../forms/add_room_modal_form.php'; ?>

<!-- EDIT ROOM MODAL -->
 <?php include '../../forms/edit_room_modal_form.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../js/room.js"></script>

<script>
document.querySelectorAll('.btn-delete').forEach(btn => {
  btn.addEventListener('click', function() {
    const roomId = this.getAttribute('data-id');
    const roomNumber = this.getAttribute('data-room');

    Swal.fire({
      title: 'Are you sure?',
      text: `You want to delete Room ${roomNumber}?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, delete it',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        fetch(`delete.php?id=${roomId}`)
          .then(res => res.text())
          .then(data => {
            if (data.trim() === "success") {
              Swal.fire({
                title: 'Deleted!',
                text: `Room ${roomNumber} has been moved to Inactive List.`,
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
              }).then(() => {
                // Reload table or page
                location.reload();
              });
            } else {
              Swal.fire('Error', 'Failed to delete the room.', 'error');
            }
          })
          .catch(() => {
            Swal.fire('Error', 'Something went wrong while deleting.', 'error');
          });
      }
    });
  });
});

// ==============================
// SAVE ROOM CONFIRMATION USING SWEETALERT2
// When user clicks "Save Room", this shows a confirmation prompt
// ==============================

function confirmSaveRoom(callback) {
    // Get the Room Number input value
    const roomInput = document.querySelector('input[name="room_number"]');
    const roomNumber = roomInput.value.trim().toUpperCase();

    // Show warning if no room number is entered
    if (!roomNumber) {
        Swal.fire({
            title: 'Warning',
            text: 'Please enter room number before saving.',
            icon: 'warning',
            confirmButtonText: 'OK',
            confirmButtonColor: '#f0ad4e'
        });
        return;
    }

    // Show SweetAlert2 confirm dialog
    Swal.fire({
        title: 'Confirm Save',
        text: `Are you sure you want to save room "${roomNumber}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, save it',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if(result.isConfirmed && typeof callback === 'function') {
            // If user clicks "Yes", submit the form
            callback();
        }
    });
}

// Attach the click event to the "Save Room" button
document.addEventListener("DOMContentLoaded", function() {
    const saveBtn = document.getElementById('saveRoomBtn');
    if(saveBtn) {
        saveBtn.addEventListener('click', function() {
            // Call the confirmSaveRoom function
            confirmSaveRoom(function() {
                document.getElementById('roomForm').submit();
            });
        });
    }
});

// ==============================
// OPEN EDIT MODAL FUNCTION
// Populates the edit modal with room data
// ==============================
function openEditModal(roomId, roomNumber, roomType, capacity, upperDeck, lowerDeck, price) {
    // Set the hidden room ID
    document.getElementById('edit_room_id').value = roomId;

    // Populate form fields
    document.getElementById('edit_room_number').value = roomNumber;
    document.getElementById('edit_room_type').value = roomType;
    document.getElementById('edit_capacity').value = capacity;
    document.getElementById('edit_upper').value = upperDeck;
    document.getElementById('edit_lower').value = lowerDeck;
    document.getElementById('edit_price').value = price;

    // Show the modal
    const editModal = new bootstrap.Modal(document.getElementById('editRoomModal'));
    editModal.show();
}

</script>
</body>
</html>
       