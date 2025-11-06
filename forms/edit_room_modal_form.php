<div class="modal fade" id="editRoomModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content" id="editRoomForm">
        <input type="hidden" name="edit_room_id" id="edit_room_id">
        <div class="modal-header">
            <h5 class="modal-title">Edit Room</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <label>Room Number:</label>
            <input type="text" name="room_number" id="edit_room_number" class="form-control" required>
            <label>Room Type:</label>
            <select name="room_type" id="edit_room_type" class="form-control" onchange="toggleDeckInputs('edit')" required>
                <option value="Bed Spacer">Bed Spacer</option>
                <option value="Whole Room">Whole Room</option>
            </select>
            <label>Capacity:</label>
            <input type="number" name="capacity" id="edit_capacity" min="1" class="form-control" required>
            <label>Upper Decks:</label>
            <input type="number" name="upper_deck_count" id="edit_upper" min="0" class="form-control">
            <label>Lower Decks:</label>
            <input type="number" name="lower_deck_count" id="edit_lower" min="0" class="form-control">
            <label>Price:</label>
            <input type="number" name="price" step="0.01" min="0" id="edit_price" class="form-control" required>
        </div>
             <div class="modal-footer d-flex justify-content-center">
            <button type="submit" class="btn btn-success">Update Room</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
    </form>
  </div>
</div>