<?php
/**
 * Rooms Module - Main Rooms Page
 * Path: /modules/rooms/index.php
 */
require_once '../../includes/auth_check.php';
require_once '../../helpers/TenantAssignments.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // --- ADD ROOM ---
    if (isset($_POST['room_number']) && !isset($_POST['edit_room_id'])) {
        $room_number = trim($_POST['room_number']);
        $room_type = trim($_POST['room_type']);
        $capacity = intval($_POST['capacity']);
        $upper_deck = intval($_POST['upper_deck_count']);
        $lower_deck = intval($_POST['lower_deck_count']);
        $price = floatval($_POST['price']);
        $description = trim($_POST['description'] ?? '');

        if ($room_type === "Whole Room") {
            $capacity = 1;
            $upper_deck = 0;
            $lower_deck = 0;
        }

        // Check duplicate
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
            $room_id = $stmt->insert_id;

            // Insert multiple descriptions
            if (!empty($description)) {
                $lines = array_filter(array_map('trim', explode("\n", $description)));
                $desc_stmt = $conn->prepare("INSERT INTO room_additional_descriptions (room_id, description) VALUES (?, ?)");
                foreach ($lines as $line) {
                    $desc_stmt->bind_param("is", $room_id, $line);
                    $desc_stmt->execute();
                }
            }

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
    $description_text = trim($_POST['description'] ?? '');

    if ($room_type === "Whole Room") {
        $capacity = 1;
        $upper_deck = 0;
        $lower_deck = 0;
    }

    // Check duplicate room number
    $check = $conn->prepare("SELECT COUNT(*) as count FROM rooms WHERE room_number=? AND room_id!=? AND record_status='Active'");
    if (!$check) die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
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
    if (!$stmt) die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    $stmt->bind_param("ssiiidi", $room_number, $room_type, $capacity, $upper_deck, $lower_deck, $price, $room_id);

    if ($stmt->execute()) {

        // Delete old descriptions safely
        $delete_stmt = $conn->prepare("DELETE FROM room_additional_descriptions WHERE room_id=?");
        if (!$delete_stmt) die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        $delete_stmt->bind_param("i", $room_id);
        $delete_stmt->execute();

        // Insert new descriptions
        if (!empty($description_text)) {
            $lines = array_filter(array_map('trim', explode("\n", $description_text)));
            $desc_stmt = $conn->prepare("INSERT INTO room_additional_descriptions (room_id, description) VALUES (?, ?)");
            if (!$desc_stmt) die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
            foreach ($lines as $line) {
                $desc_stmt->bind_param("is", $room_id, $line);
                $desc_stmt->execute();
            }
        }

        $_SESSION['message'] = "Room '$room_number' updated successfully!";
        header("Location: index.php");
        exit;
    } else {
        die("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
    }
}


} // end POST

// Fetch active and inactive rooms
$roomResult = $conn->query("SELECT * FROM rooms WHERE record_status='Active' ORDER BY room_number ASC");
$inactiveResult = $conn->query("SELECT * FROM rooms WHERE record_status='Inactive' ORDER BY room_number ASC");

$roomInventory = TenantAssignments::getRoomInventory($conn);
$roomInventoryMap = [];
foreach ($roomInventory as $inventoryRow) {
    $roomInventoryMap[(int)$inventoryRow['room_id']] = $inventoryRow;
}

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
    $header_pic = BASE_PATH . "/uploads/default_header.png";
    $profile_pic = BASE_PATH . "/uploads/default_profile.png";
    ?>

    <div class="d-flex justify-content-between align-items-center mt-0">
        <h2>Room Information</h2>
        <div class="profile-box d-flex align-items-center">
            <img src="<?= $header_pic ?>" alt="Header Picture" class="rounded-circle" width="50" height="50">
            <span class="ms-2">Admin</span>
        </div>
    </div>

    <hr style="width: 100%; margin: 10px auto; border: 1px solid #140d0dff;">

    <div class="search-container">
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

<!-- VIEW ROOM DESCRIPTIONS MODAL -->
<div class="modal fade" id="viewDescriptionsModal" tabindex="-1" aria-labelledby="viewDescriptionsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header d-flex justify-content-between align-items-center">
        <h5 class="modal-title" id="viewDescriptionsModalLabel">Room Descriptions</h5>
        <span id="descriptionCount" class="badge bg-primary"></span> <!-- Counter sa top-right -->
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <ul id="descriptionList" style="list-style-type: disc; padding-left:20px;"></ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


    <?php
    $activeCount = $conn->query("SELECT COUNT(*) as totalActive FROM rooms WHERE record_status='Active'")->fetch_assoc()['totalActive'];
    $inactiveCount = $conn->query("SELECT COUNT(*) as totalInactive FROM rooms WHERE record_status='Inactive'")->fetch_assoc()['totalInactive'];
    ?>

    <div style="margin-top:10px; font-weight:bold; color:#5A7D7C;">
        Total Active Rooms: <?= $activeCount; ?>
    </div>
    <div style="margin-top:10px; font-weight:bold; color:#dc3545;">
        <a href="inactive.php" style="text-decoration:none; color:#dc3545;">Total Inactive Rooms: <?= $inactiveCount; ?></a>
    </div>

    <ul class="nav nav-tabs mb-3" id="roomTabs">
      <li class="nav-item"><a class="nav-link active" href="index.php">Active Rooms</a></li>
      <li class="nav-item"><a class="nav-link" href="inactive.php">Inactive Rooms</a></li>
    </ul>

    <div class="table-container" style="overflow-x:auto; overflow-y:auto; max-height:500px; border:1px solid #ddd; border-radius:8px; padding:5px;">
        <table id="roomTable" class="table table-bordered">
          <thead class="table-light" style="position:sticky; top:0; z-index:1;">
            <tr>
              <th>Room Number</th>
              <th>Room Type</th>
              <th>Total Decks</th>
              <th>Available</th>
              <th>Status</th>
              <th>Upper Decks</th>
              <th>Lower Decks</th>
              <th>Price</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
<?php while ($row = $roomResult->fetch_assoc()):
    $roomId = (int)$row['room_id'];
    $inventory = $roomInventoryMap[$roomId] ?? null;

    $roomType = $row['room_type'];
    $upperDeckCount = (int)($inventory['upper_deck_count'] ?? $row['upper_deck_count']);
    $lowerDeckCount = (int)($inventory['lower_deck_count'] ?? $row['lower_deck_count']);
    $totalDecks = ($roomType === 'Whole Room') ? 0 : ($upperDeckCount + $lowerDeckCount);

    $availableSlots = (int)($inventory['available_slots'] ?? (($roomType === 'Whole Room') ? max(1, (int)$row['capacity']) : $totalDecks));
    $upperAvailable = (int)($inventory['upper_available'] ?? ($roomType === 'Whole Room' ? 0 : $upperDeckCount));
    $lowerAvailable = (int)($inventory['lower_available'] ?? ($roomType === 'Whole Room' ? 0 : $lowerDeckCount));

    $upperOccupied = max($upperDeckCount - $upperAvailable, 0);
    $lowerOccupied = max($lowerDeckCount - $lowerAvailable, 0);

    if ($roomType === 'Whole Room') {
        $availableSlots = (int)($inventory['available_slots'] ?? max(1, (int)$row['capacity']));
    }

    $statusLabel = $availableSlots > 0 ? 'Available' : 'Occupied';
?>
<tr>
    <td><?= htmlspecialchars($row['room_number']) ?></td>
    <td><?= htmlspecialchars($row['room_type']) ?></td>
    <td><?= $roomType === 'Whole Room' ? '—' : $totalDecks ?></td>
    <td><?= $availableSlots ?></td>
    <td><?= $statusLabel ?></td>
    <td><?= $roomType === 'Whole Room' ? '—' : ($upperOccupied . '/' . $upperDeckCount) ?></td>
    <td><?= $roomType === 'Whole Room' ? '—' : ($lowerOccupied . '/' . $lowerDeckCount) ?></td>
    <td><?= number_format($row['price'],2) ?></td>
    <td>
        <button class="btn btn-view" onclick="openViewModal(<?= $row['room_id'] ?>)">👁️ View</button>
        <button class="btn btn-edit" onclick="openEditModal(
            <?= $row['room_id'] ?>,
            '<?= addslashes($row['room_number']) ?>',
            '<?= $row['room_type'] ?>',
            <?= $row['upper_deck_count'] ?>,
            <?= $row['lower_deck_count'] ?>,
            <?= $row['price'] ?>
        )">Edit</button>
        <button class="btn btn-delete" data-id="<?= $row['room_id']; ?>" data-room="<?= htmlspecialchars($row['room_number']); ?>">Delete</button>
    </td>
</tr>
<?php endwhile; ?>

          </tbody>
        </table>
    </div>

</div>
</div>

<?php include '../../forms/add_room_modal_form.php'; ?>
<?php include '../../forms/edit_room_modal_form.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// DELETE ROOM
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function() {
        const roomId = this.dataset.id;
        const roomNumber = this.dataset.room;
        Swal.fire({
            title: 'Are you sure?',
            text: `You want to delete Room ${roomNumber}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it'
        }).then((result) => {
            if(result.isConfirmed){
                fetch(`delete.php?id=${roomId}`)
                    .then(res=>res.text())
                    .then(data=>{
                        if(data.trim()==='success') Swal.fire('Deleted!', `Room ${roomNumber} moved to Inactive.`, 'success').then(()=>location.reload());
                        else Swal.fire('Error','Failed to delete.','error');
                    });
            }
        });
    });
});

// EDIT MODAL
function openEditModal(roomId, roomNumber, roomType, capacity, upperDeck, lowerDeck, price){
    document.getElementById('edit_room_id').value = roomId;
    document.getElementById('edit_room_number').value = roomNumber;
    document.getElementById('edit_room_type').value = roomType;
    document.getElementById('edit_capacity').value = capacity;
    document.getElementById('edit_upper').value = upperDeck;
    document.getElementById('edit_lower').value = lowerDeck;
    document.getElementById('edit_price').value = price;

    // Fetch descriptions automatically
    fetch(`get_room_descriptions.php?room_id=${roomId}`)
        .then(res=>res.json())
        .then(data=>{
            const descField = document.getElementById('editRoomDescription');
            descField.value = data.length ? data.map(d=>d.description).join("\n") : '';
        })
        .catch(err=>{
            console.error("Failed to fetch descriptions", err);
            document.getElementById('editRoomDescription').value = '';
        });

    new bootstrap.Modal(document.getElementById('editRoomModal')).show();
}

// VIEW DESCRIPTIONS
function openViewModal(roomId){
    fetch(`get_room_descriptions.php?room_id=${roomId}`)
        .then(res=>res.json())
        .then(data=>{
            const list = document.getElementById('descriptionList');
            const count = document.getElementById('descriptionCount');
            list.innerHTML='';

            count.textContent = data.length + " Description" + (data.length > 1 ? "s" : "");

            if(!data.length) list.innerHTML='<li>No descriptions found.</li>';
            else data.forEach((d,i)=>{
                const li = document.createElement('li');
                li.textContent = d.description;
                li.style.marginBottom='8px';
                list.appendChild(li);
            });

            new bootstrap.Modal(document.getElementById('viewDescriptionsModal')).show();
        }).catch(err=>console.error(err));
}

// OPEN MODAL FUNCTION
function openModal(modalId) {
    const modalEl = document.getElementById(modalId);
    if(modalEl){
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    } else {
        console.error('Modal not found:', modalId);
    }
}

</script>
</body>
</html>
