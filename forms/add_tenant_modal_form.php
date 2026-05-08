<?php
if (!isset($roomInventory)) {
    try {
        $roomInventory = TenantAssignments::getRoomInventory($conn);
    } catch (Exception $e) {
        $roomInventory = [];
        error_log('Failed to load room inventory: ' . $e->getMessage());
    }
}
?>
<!-- Add Tenant Modal -->
<div id="tenantModal" class="modal">
  <div class="modal-content tenant-form-container">
    <span class="close-btn" onclick="closeModal()">&times;</span>
    <form method="POST" enctype="multipart/form-data" id="addTenantForm" onsubmit="return confirmSave()">

      <div class="form-grid">

        <!-- ROW 1: Profile Picture | Proof of Identity -->
        <div class="form-row">
          <div class="form-left">
            <label>Profile Picture:</label>
            <input type="file" name="profile_pic">
          </div>
          <div class="form-right">
            <label>Proof of Identity:</label>
            <input type="file" name="proof_pic">
          </div>
        </div>

        <!-- ROW 2: Full Name | Date Started -->
        <div class="form-row">
          <div class="form-left">
            <label>Full Name <span style="color:red">*</span>:</label>
            <input type="text" name="tenant_name" required 
                   oninput="this.value = this.value.replace(/[0-9]/g, ''); capitalizeName(this)">
          </div>
          <div class="form-right">
            <label>Date Started <span style="color:red">*</span>:</label>
            <input type="date" name="date_started" required>
          </div>
        </div>

        <!-- ROW 3: Room Assignments -->
        <div class="form-row">
          <div class="form-left">
            <label>Rooms to Rent <span style="color:red">*</span>:</label>
            <div id="addRoomContainer" class="room-assignment-container" data-room-name="room_id[]" data-deck-name="deck_type[]" data-template-id="roomRowTemplate" data-min-rows="1"></div>
            <button type="button" class="btn btn-success btn-sm" id="addRoomRowBtn" <?= empty($roomInventory) ? 'disabled' : '' ?>>+ Add Room</button>
            <?php if (empty($roomInventory)): ?>
              <div class="alert alert-warning mt-2" role="alert">
                  No active rooms are currently available. Please add or activate rooms first.
              </div>
            <?php endif; ?>
            <template id="roomRowTemplate">
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

        <!-- ROW 4: Contact Number | Guardian Contact -->
        <div class="form-row">
          <div class="form-left">
            <label>Contact Number <span style="color:red">*</span>:</label>
            <input type="text" name="contact_number" pattern="09[0-9]{9}" maxlength="11" minlength="11" placeholder="09xxxxxxxxx" required>
          </div>
          <div class="form-right">
            <label>Guardian Contact <span style="color:red">*</span>:</label>
            <input type="text" name="guardian_contact" pattern="09[0-9]{9}" maxlength="11" minlength="11" placeholder="09xxxxxxxxx" required>
          </div>
        </div>

        <!-- ROW 5: Address -->
        <div class="form-row">
          <div class="form-left" style="flex:1;">
            <label>Address <span style="color:red">*</span>:</label>
            <input type="text" name="address" id="tenantAddress" oninput="capitalizeName(this)" required>
          </div>
        </div>

      </div>

      <!-- Footer Buttons -->
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary btn-sm">Save Tenant</button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal()">Cancel</button>
      </div>

    </form>
  </div>
</div>

<style>
.form-grid {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.form-row {
    display: flex;
    gap: 20px;
}

.form-left, .form-right {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.
.room-assignment-container {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 8px;
}

.room-row {
    display: flex;
    gap: 8px;
    align-items: center;
}

.room-row .roomSelect,
.room-row .deckSelect {
    flex: 1;
}

.room-row .remove-room {
    flex: 0 0 auto;
    background-color: #dc3545;
    color: #fff;
    border: none;
    border-radius: 4px;
    width: 32px;
    height: 32px;
    line-height: 1;
    display: flex;
    justify-content: center;
    align-items: center;
}

</style>

<script type="application/json" id="roomInventoryJson"><?= htmlspecialchars(json_encode($roomInventory, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_NOQUOTES, 'UTF-8'); ?></script>
