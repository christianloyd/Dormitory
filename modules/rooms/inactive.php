<?php
/**
 * Rooms Module - Inactive Rooms
 * Path: /modules/rooms/inactive.php
 */
require_once '../../includes/auth_check.php';

// Fetch all inactive rooms
$inactiveResult = $conn->query("SELECT * FROM rooms WHERE record_status='Inactive' ORDER BY room_number ASC");

// Count Inactive Rooms
$countResult = $conn->query("SELECT COUNT(*) as totalInactive FROM rooms WHERE record_status='Inactive'");
$totalInactive = $countResult->fetch_assoc()['totalInactive'];

// Count Active Rooms (for reference)
$activeCountResult = $conn->query("SELECT COUNT(*) as totalActive FROM rooms WHERE record_status='Active'");
$totalActive = $activeCountResult->fetch_assoc()['totalActive'];
?>
<!DOCTYPE html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/new_room.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lobibox/dist/css/lobibox.min.css"/>
</head>
<body>
<?php include '../../sidebar.php'; ?>

<div class="main-content">
    <div class="container-fluid">

    <?php
// fetch header image from DB
$header = $conn->query("SELECT setting_value FROM settings WHERE setting_name='header_image'")->fetch_assoc();
$header_pic = $header ? $header['setting_value'] : "uploads/default_header.png";

// fetch profile image from DB
$profile = $conn->query("SELECT setting_value FROM settings WHERE setting_name='profile_image'")->fetch_assoc();
$profile_pic = $profile ? $profile['setting_value'] : "uploads/default_profile.png";
?>

<div class="d-flex justify-content-between align-items-center mt-0">
    <h2>Inactive Rooms</h2>
    <div class="profile-box d-flex align-items-center">
        <img src="<?php echo $header_pic; ?>"
             alt="Header Picture"
             class="rounded-circle"
             width="50" height="50">
        <span class="ms-2">Admin</span>
    </div>
</div>
        <!-- Custom HR -->
        <hr style="width: 100%; margin: 10px auto; border: 1px solid #140d0dff;">


     <div class="search-container">
    <!-- Back to Active Rooms button -->
    <button class="top-btn" onclick="window.location.href='index.php'">← Back to Active Rooms</button>

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


<div style="margin-top:10px; font-weight:bold; color:#5A7D7C;">
    <a href="index.php" style="text-decoration:none; color:#5A7D7C;">Total Active Rooms: <?= $totalActive; ?></a>
</div>
<div style="margin-top:10px; font-weight:bold; color:#dc3545;">
    Total Inactive Rooms: <?= $totalInactive; ?>
</div>
<ul class="nav nav-tabs mb-3" id="roomTabs">
  <li class="nav-item">
    <a class="nav-link" href="index.php">Active Rooms</a>
  </li>
  <li class="nav-item">
    <a class="nav-link active" href="inactive_index.php">Inactive Rooms</a>
  </li>
</ul>

<!-- INACTIVE ROOMS TABLE -->
<div class="table-container" style="overflow-x:auto; overflow-y:auto; max-height:500px; border:1px solid #ddd; border-radius:8px; padding:5px;">
  <table id="inactiveRoomTable" style="width:100%; min-width:900px; border-collapse:collapse;">
    <thead style="background-color:#f5f5f5; position:sticky; top:0; z-index:1;">
      <tr>
        <th>Room Number</th>
        <th>Room Type</th>
        <th>Capacity</th>
        <th>Upper Decks</th>
        <th>Lower Decks</th>
        <th>Price</th>
        <th>Status</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
<?php
if ($inactiveResult->num_rows > 0) {
    while ($row = $inactiveResult->fetch_assoc()) {
        echo '<tr>
                <td>' . htmlspecialchars($row['room_number']) . '</td>
                <td>' . htmlspecialchars($row['room_type']) . '</td>
                <td>' . htmlspecialchars($row['capacity']) . '</td>
                <td>' . htmlspecialchars($row['upper_deck_count']) . '</td>
                <td>' . htmlspecialchars($row['lower_deck_count']) . '</td>
                <td>₱' . number_format($row['price'], 2) . '</td>
                <td style="color:red; font-weight:bold;">Inactive</td>
                <td>
                  <button
                    class="btn btn-success btn-sm"
                    data-id="' . $row['room_id'] . '"
                    data-room="' . htmlspecialchars($row['room_number']) . '"
                    onclick="restoreRoom(this)">
                    Restore
                  </button>
                </td>
              </tr>';
    }
} else {
    echo "<tr><td colspan='8' style='text-align:center;'>No inactive rooms</td></tr>";
}
?>
    </tbody>
  </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/room.js"></script>

<script>
// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchValue = this.value.toLowerCase();
    const tableRows = document.querySelectorAll('#inactiveRoomTable tbody tr');

    tableRows.forEach(row => {
        const roomNumber = row.cells[0].textContent.toLowerCase();
        if (roomNumber.includes(searchValue)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Restore room function
function restoreRoom(button) {
    const roomId = button.getAttribute('data-id');
    const roomNumber = button.getAttribute('data-room');

    Swal.fire({
      title: 'Restore Room?',
      text: `Do you want to restore Room ${roomNumber} to active status?`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#28a745',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, restore it',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        fetch(`restore.php?id=${roomId}`)
          .then(res => res.text())
          .then(data => {
            if (data.trim() === "success") {
              Swal.fire({
                title: 'Restored!',
                text: `Room ${roomNumber} has been restored to active status.`,
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
              }).then(() => {
                location.reload();
              });
            } else {
              Swal.fire('Error', 'Failed to restore the room.', 'error');
            }
          })
          .catch(() => {
            Swal.fire('Error', 'Something went wrong while restoring.', 'error');
          });
      }
    });
}
</script>
</body>
</html>
