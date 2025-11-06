<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
?>


<div class="container mt-3">
    <h2>Edit Room Information</h2>
    <p>Manage and update room records here.</p>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Room Number</th>
                <th>Room Type</th>
                <th>Deck Type</th>
                <th>Price</th>
                <th>Capacity</th>
                <th>Available</th>
                <th>Status</th>
                <th>Upper Deck</th>
                <th>Lower Deck</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $rooms = $conn->query("SELECT * FROM rooms ORDER BY room_number ASC");
            while($row = $rooms->fetch_assoc()):
            ?>
            <tr>
                <td><?= htmlspecialchars($row['room_number']) ?></td>
                <td><?= htmlspecialchars($row['room_type']) ?></td>
                <td><?= htmlspecialchars($row['deck_type']) ?></td>
                <td><?= number_format($row['price'],2) ?></td>
                <td><?= htmlspecialchars($row['capacity']) ?></td>
                <td><?= htmlspecialchars($row['available']) ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td><?= htmlspecialchars($row['upper_deck_count']) ?></td>
                <td><?= htmlspecialchars($row['lower_deck_count']) ?></td>
                <td>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#updateRoomModal<?= $row['room_number'] ?>">Update</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Update Modals per Room -->
<?php
$rooms = $conn->query("SELECT * FROM rooms ORDER BY room_number ASC");
while($row = $rooms->fetch_assoc()):
?>
<div class="modal fade" id="updateRoomModal<?= $row['room_number'] ?>" tabindex="-1" aria-labelledby="updateRoomLabel<?= $row['room_number'] ?>" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form action="update_room.php" method="POST"
            onsubmit="return confirm('Are you sure you want to update Room <?= htmlspecialchars($row['room_number']) ?>?')">
        <div class="modal-header">
          <h5 class="modal-title" id="updateRoomLabel<?= $row['room_number'] ?>">Update Room: <?= htmlspecialchars($row['room_number']) ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="room_number" value="<?= $row['room_number'] ?>">

            <div class="mb-3">
                <label class="form-label">Room Type</label>
                <input type="text" name="room_type" class="form-control" value="<?= htmlspecialchars($row['room_type']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Deck Type</label>
                <input type="text" name="deck_type" class="form-control" value="<?= htmlspecialchars($row['deck_type']) ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Price</label>
                <input type="number" step="0.01" name="price" class="form-control" value="<?= $row['price'] ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Capacity</label>
                <input type="number" name="capacity" class="form-control" value="<?= $row['capacity'] ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Available</label>
                <input type="number" name="available" class="form-control" value="<?= $row['available'] ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-control" required>
                    <option value="Available" <?= $row['status']=='Available'?'selected':'' ?>>Available</option>
                    <option value="Unavailable" <?= $row['status']=='Unavailable'?'selected':'' ?>>Unavailable</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Upper Deck Count</label>
                <input type="number" name="upper_deck_count" class="form-control" value="<?= $row['upper_deck_count'] ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Lower Deck Count</label>
                <input type="number" name="lower_deck_count" class="form-control" value="<?= $row['lower_deck_count'] ?>">
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Update Room</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endwhile; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
