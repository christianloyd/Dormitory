<!-- Add Tenant Modal -->
<div id="tenantModal" class="modal">
  <div class="modal-content tenant-form-container">
    <span class="close-btn" onclick="closeModal()">&times;</span>
    <form method="POST" enctype="multipart/form-data" onsubmit="return confirmSave()">

      <div class="form-grid">
        <!-- LEFT COLUMN -->
        <div class="form-left">
          <label>Profile Picture:</label>
          <input type="file" name="profile_pic">

          <label>Full Name <span style="color:red">*</span>:</label>
          <input type="text" name="tenant_name" required oninput="capitalizeName(this)">

          <label>Room <span style="color:red;">*</span></label>
          <select name="room_id" id="roomSelect" required onchange="checkDeck()">
              <option value="">-- Select Room --</option>

              
              <?php
              $rooms = $conn->query("SELECT * FROM rooms WHERE record_status = 'Active' ORDER BY room_number ASC");
              while ($r = $rooms->fetch_assoc()) {
                 $stmt = $conn->prepare("SELECT 
                            SUM(CASE WHEN deck_type='Upper Deck' THEN 1 ELSE 0 END) as upper_tenants,
                            SUM(CASE WHEN deck_type='Lower Deck' THEN 1 ELSE 0 END) as lower_tenants,
                            COUNT(*) as total_tenants
                        FROM tenants 
                        WHERE room_id=? AND status='Active'");
                  $stmt->bind_param("i", $r['room_id']);
                  $stmt->execute();
                  $tenantData = $stmt->get_result()->fetch_assoc();

                  $total_tenants = $tenantData['total_tenants'] ?? 0;
                  $available = $r['capacity'] - $total_tenants;

                  $style = ($available <= 0) ? "color:gray;" : "";
                  $text = htmlspecialchars($r['room_number']);
                  if ($available <= 0) $text .= " (Occupied)";

                  echo "<option value='" . $r['room_id'] . "' data-room-type='" . $r['room_type'] . "' data-upper='" . $r['upper_deck_count'] . "' data-lower='" . $r['lower_deck_count'] . "' data-upper-occupied='" . ($tenantData['upper_tenants'] ?? 0) . "' data-lower-occupied='" . ($tenantData['lower_tenants'] ?? 0) . "' style='$style'";
                  if ($available <= 0) echo " disabled";
                  echo ">$text</option>";
              }
              ?>
          </select>

          <label>Contact Number <span style="color:red">*</span>:</label>
          <input type="text" name="contact_number" 
                pattern="09[0-9]{9}" maxlength="11" minlength="11" 
                placeholder="09xxxxxxxxx" onkeypress="return /[0-9]/i.test(event.key)" required>

           <label>Address <span style="color:red">*</span>:</label>
        <input type="text" class="form-control" name="address" id="tenantAddress"
       oninput="capitalizeName(this)" required>
            </div>

        <!-- RIGHT COLUMN -->
        <div class="form-right">
          <label>Proof of Identity:</label>
          <input type="file" name="proof_pic">

          <label>Date Started <span style="color:red">*</span>:</label>
          <input type="date" name="date_started" required>

          <label>Deck Type <span style="color:red;">*</span></label>
          <select name="deck_type" id="deckSelect" required>
              <option value="">-- Select Deck --</option>
              <option value="Lower Deck">Lower Deck</option>
              <option value="Upper Deck">Upper Deck</option>
          </select>

          <label>Guardian Contact <span style="color:red">*</span>:</label>
          <input type="text" name="guardian_contact" 
                pattern="09[0-9]{9}" maxlength="11" minlength="11" 
                placeholder="09xxxxxxxxx" onkeypress="return /[0-9]/i.test(event.key)" required>
        </div>
      </div>

      <!-- Footer Buttons -->
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary btn-sm">Save Tenant</button>
      <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal()" style="background-color:#6c757d; border-color:#6c757d; color:white;">Cancel</button>
      </div>
    </form>
  </div>
</div>