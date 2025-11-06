<!-- Add Tenant Modal -->
<div class="modal fade" id="addTenantModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Add New Tenant</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <form method="POST" enctype="multipart/form-data" id="addTenantForm">
                <div class="row g-3 ">
                    <div class="col-md-6">
                        <label class="form-label">Profile Picture</label>
                        <input type="file" class="form-control" name="profile_pic">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Proof of Identity</label>
                        <input type="file" class="form-control" name="proof_pic">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Full Name *</label>
                        <input type="text" class="form-control capitalize" name="tenant_name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Room *</label>
                        <select name="room_id" id="roomSelectAdd" class="form-select" required onchange="checkDeck('Add')">
                            <option value="">-- Select Room --</option>
                            <?php
                            $rooms = $conn->query("SELECT * FROM rooms ORDER BY room_number ASC");
                            while ($r = $rooms->fetch_assoc()) {
                                $stmt = $conn->prepare("SELECT 
                                                            SUM(CASE WHEN deck_type='Upper Deck' THEN 1 ELSE 0 END) as upper_tenants,
                                                            SUM(CASE WHEN deck_type='Lower Deck' THEN 1 ELSE 0 END) as lower_tenants,
                                                            COUNT(*) as total_tenants
                                                        FROM tenants WHERE room_id=?");
                                $stmt->bind_param("i", $r['room_id']);
                                $stmt->execute();
                                $tenantData = $stmt->get_result()->fetch_assoc();

                                $total_tenants = $tenantData['total_tenants'] ?? 0;
                                $available = $r['capacity'] - $total_tenants;

                                $style = ($available <= 0) ? "color:gray;" : "";
                                $text = htmlspecialchars($r['room_number']);
                                if ($available <= 0) $text .= " (Full)";

                                echo "<option value='" . $r['room_id'] . "' data-room-type='" . $r['room_type'] . 
                                     "' data-upper-occupied='" . ($tenantData['upper_tenants'] ?? 0) . 
                                     "' data-lower-occupied='" . ($tenantData['lower_tenants'] ?? 0) . 
                                     "' style='$style'";
                                if ($available <= 0) echo " disabled";
                                echo ">$text</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Deck Type *</label>
                        <select name="deck_type" id="deckSelectAdd" class="form-select">
                            <option value="">-- Select Deck --</option>
                            <option value="Lower Deck">Lower Deck</option>
                            <option value="Upper Deck">Upper Deck</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Address *</label>
                        <input type="text" class="form-control capitalize" name="address" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact Number *</label>
                        <input type="text" class="form-control" name="contact_number" maxlength="11" placeholder="09XXXXXXXXX" oninput="validatePhone(this)" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Guardian Contact *</label>
                        <input type="text" class="form-control" name="guardian_contact" maxlength="11" placeholder="09XXXXXXXXX" oninput="validatePhone(this)" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date Started *</label>
                        <input type="date" class="form-control" name="date_started" required>
                    </div>
                </div>
                <div class="mt-3 d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary btn-sm">Save Tenant</button>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
  </div>
</div>


<!-- Edit Tenant Modal -->
<div class="modal fade" id="editTenantModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="tenants.php"> <!-- Form starts here -->
        <input type="hidden" name="edit_tenant_id" id="editTenantId">
        <div class="modal-header">
          <h5 class="modal-title">Edit Tenant</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="row g-3">
                <div class="col-md-6"><label>Full Name</label><input type="text" class="form-control" name="tenant_name" id="editTenantName" required></div>
                <div class="col-md-6">
                    <label>Room</label>
                    <select name="room_id" id="roomSelectEdit" class="form-select" onchange="checkDeck('Edit')" required>
                        <option value="">-- Select Room --</option>
                        <?php
                        $rooms = $conn->query("SELECT * FROM rooms ORDER BY room_number ASC");
                        while ($r = $rooms->fetch_assoc()) {
                            echo "<option value='".$r['room_id']."' data-room-type='".$r['room_type']."'>".$r['room_number']."</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-6" id="deckWrapperEdit" style="display:none;">
                    <label>Deck Type</label>
                    <select name="deck_type" id="deckSelectEdit" class="form-select">
                        <option value="">-- Select Deck --</option>
                        <option value="Lower Deck">Lower Deck</option>
                        <option value="Upper Deck">Upper Deck</option>
                    </select>
                </div>
                <div class="col-md-6"><label>Address</label><input type="text" class="form-control" name="address" id="editTenantAddress" required></div>
                <div class="col-md-6"><label>Contact Number</label><input type="text" class="form-control" name="contact_number" id="editTenantContact" maxlength="11" placeholder="09XXXXXXXXX" oninput="validatePhone(this)" required></div>
                <div class="col-md-6"><label>Guardian Contact</label><input type="text" class="form-control" name="guardian_contact" id="editTenantGuardian" maxlength="11" placeholder="09XXXXXXXXX" oninput="validatePhone(this)" required></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary btn-sm">Update Tenant</button>
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form> <!-- Form ends here -->
    </div>
  </div>
</div>
