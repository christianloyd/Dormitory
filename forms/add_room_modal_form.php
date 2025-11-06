<div class="modal fade" id="addRoomModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content" id="addRoomForm">
        <div class="modal-header">
            <h5 class="modal-title">Add New Room</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <label>Room Number:</label>
            <input type="text" name="room_number" class="form-control" required>
            <label>Room Type:</label>
            <select name="room_type" id="add_room_type" class="form-control" onchange="toggleDeckInputs('add')" required>
                <option value="Bed Spacer">Bed Spacer</option>
                <option value="Whole Room">Whole Room</option>
            </select>
            <label>Capacity:</label>
            <input type="number" name="capacity" id="add_capacity" min="1" class="form-control" required>
            <label>Upper Decks:</label>
            <input type="number" name="upper_deck_count" id="add_upper" min="0" class="form-control">
            <label>Lower Decks:</label>
            <input type="number" name="lower_deck_count" id="add_lower" min="0" class="form-control">
            <label>Price:</label>
            <input type="number" name="price" step="0.01" min="0" class="form-control" required>
        </div>
       <div class="modal-footer d-flex justify-content-center">
    <button type="submit" class="btn btn-success">Save Room</button>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
</div>
    </form>
  </div>
</div>