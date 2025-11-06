<!-- EDIT TENANT MODAL -->
<div class="modal fade" id="editTenantModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="tenants.php" enctype="multipart/form-data">
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

            <!-- Room -->
            <div class="col-md-6">
              <label>Room</label>
              <select name="room_id" id="roomSelectEdit" class="form-select"
                      onchange="checkDeckEdit()" required>
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

    echo "<option value='" . $r['room_id'] . "' 
                data-room-type='" . $r['room_type'] . "' 
                data-upper='" . $r['upper_deck_count'] . "' 
                data-lower='" . $r['lower_deck_count'] . "' 
                data-upper-occupied='" . ($tenantData['upper_tenants'] ?? 0) . "' 
                data-lower-occupied='" . ($tenantData['lower_tenants'] ?? 0) . "' 
                style='$style' " . ($available <= 0 ? "disabled" : "") . ">$text</option>";
                }
                ?>
              </select>
            </div>

            <!-- Deck Type -->
            <div class="col-md-6" id="deckWrapperEdit" style="display:none;">
              <label>Deck Type</label>
              <select name="deck_type" id="deckSelectEdit" class="form-select">
                <option value="">-- Select Deck --</option>
                <option value="Lower Deck">Lower Deck</option>
                <option value="Upper Deck">Upper Deck</option>
              </select>
            </div>

            <!-- Address -->
           <div class="col-md-6">
  <label>Address</label>
  <input type="text" 
         class="form-control" 
         name="address" 
         id="editTenantAddress" 
         oninput="capitalizeName(this)" 
         required>
</div>


            <!-- Contact Number -->
            <div class="col-md-6">
              <label>Contact Number</label>
              <input type="text" class="form-control" name="contact_number" id="editTenantContact"
                     maxlength="11" placeholder="09XXXXXXXXX" oninput="validatePhone(this)" required>
            </div>

            <!-- Guardian Contact -->
            <div class="col-md-6">
              <label>Guardian Contact</label>
              <input type="text" class="form-control" name="guardian_contact" id="editTenantGuardian"
                     maxlength="11" placeholder="09XXXXXXXXX" oninput="validatePhone(this)" required>
            </div>

          </div>
        </div>

        <div class="modal-footer d-flex justify-content-center">
          <button type="submit" class="btn btn-primary btn-sm" style="background-color:#5A7D7C; border-color:#5A7D7C;">Update Tenant</button>

          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        </div>
