<!-- EDIT TENANT MODAL -->
<div class="modal fade" id="editTenantModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="index.php" enctype="multipart/form-data">
        <input type="hidden" name="edit_tenant_id" id="editTenantId">

        <!-- Hidden fields to prevent errors -->
        <input type="hidden" name="status" id="editTenantStatus" value="Active">


        <div class="modal-header">
          <h5 class="modal-title">Edit Tenant</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">

            <!-- Full Name -->
            <div class="col-md-6">
              <label>Full Name</label>
              <input type="text" class="form-control" name="tenant_name" id="editTenantName"
                     oninput="capitalizeName(this)" required>
            </div>

            <!-- Date Started -->
            <div class="col-md-6">
              <label>Date Started</label>
              <input type="date" class="form-control" name="date_started" id="editTenantDate" required>
            </div>

            <!-- Address -->
            <div class="col-md-6">
              <label>Address</label>
              <input type="text" class="form-control" name="address" id="editTenantAddress"
                     oninput="capitalizeName(this)" required>
            </div>

            <!-- Contact Number -->
            <div class="col-md-6">
              <label>Contact Number</label>
              <input type="text" class="form-control" name="contact_number" id="editTenantContact"
                     maxlength="11" placeholder="09XXXXXXXXX" required>
            </div>

            <!-- Guardian Contact -->
            <div class="col-md-6">
              <label>Guardian Contact</label>
              <input type="text" class="form-control" name="guardian_contact" id="editTenantGuardian"
                     maxlength="11" placeholder="09XXXXXXXXX" required>
            </div>

            <!-- Room Assignments -->
            <div class="col-12">
              <label class="d-flex justify-content-between align-items-center">
                <span>Room Assignments</span>
                <button type="button" class="btn btn-success btn-sm" id="editAddRoomRowBtn">+ Add Room</button>
              </label>
              <div id="editRoomContainer" class="room-assignment-container" data-room-name="edit_room_id[]" data-deck-name="edit_deck_type[]"></div>
              <small class="text-muted">Adjust the set of rooms (and decks) this tenant occupies.</small>
              <template id="editRoomRowTemplate">
                <div class="room-row" data-room-row>
                  <select class="form-select roomSelect" required></select>
                  <select class="form-select deckSelect">
                    <option value="">Deck</option>
                    <option value="Lower Deck">Lower Deck</option>
                    <option value="Upper Deck">Upper Deck</option>
                  </select>
                  <button type="button" class="remove-room" aria-label="Remove room">&times;</button>
                </div>
              </template>
            </div>

          </div>
        </div>

        <div class="modal-footer d-flex justify-content-center">
          <button type="submit" class="btn btn-primary btn-sm" style="background-color:#5A7D7C; border-color:#5A7D7C;">Update Tenant</button>
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
