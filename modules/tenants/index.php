<?php
/**
 * Tenants Module - Main Tenants Page
 * Path: /modules/tenants/index.php
 */
require_once '../../includes/auth_check.php';

require_once __DIR__ . '/../../helpers/TenantAssignments.php';

$roomInventory = TenantAssignments::getRoomInventory($conn);

// --- Handle Edit Tenant Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['edit_tenant_id'])) {
    $tenantId = intval($_POST['edit_tenant_id']);

    try {
        TenantAssignments::updateTenant($conn, $tenantId, $_POST, $_FILES);
        $_SESSION['swal_success'] = "Tenant updated successfully!";
    } catch (Exception $e) {
        $_SESSION['swal_error'] = $e->getMessage();
    }

    header("Location: index.php");
    exit;
}

// --- Handle Add Tenant Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tenant_name'])) {
    try {
        TenantAssignments::createTenant($conn, $_POST, $_FILES);
        $_SESSION['swal_success'] = "Tenant added successfully!";
    } catch (Exception $e) {
        $_SESSION['swal_error'] = $e->getMessage();
    }

    header("Location: index.php");
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
<title>Tenant Info</title>
<link rel="stylesheet" href="../../css/tenants.css">
<link rel="stylesheet" href="../../css/newtenant.css">
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>
<div class="main-content">

<?php
// fetch header image from DB
$header = $conn->query("SELECT setting_value FROM settings WHERE setting_name='header_image'")->fetch_assoc();
$header_pic = $header ? BASE_PATH . '/' . $header['setting_value'] : BASE_PATH . "/uploads/default_header.png";

// fetch profile image from DB
$profile = $conn->query("SELECT setting_value FROM settings WHERE setting_name='profile_image'")->fetch_assoc();
$profile_pic = $profile ? BASE_PATH . '/' . $profile['setting_value'] : BASE_PATH . "/uploads/default_profile.png";
?>

<div class="d-flex justify-content-between align-items-center mt-0">
    <h2>Tenant Information</h2>
    <div class="profile-box d-flex align-items-center">
        <img src="<?php echo $header_pic; ?>" 
             alt="Header Picture" 
             class="rounded-circle" 
             width="50" height="50">
        <span class="ms-2">Admin</span> <!-- Always display "admin" -->
    </div>
</div>
        <!-- Custom HR -->
        <hr style="width: 100%; margin: 10px auto; border: 1px solid #140d0dff;">


   <div class="search-container">
    <button class="top-btn" onclick="openModal()">Add New Tenant</button>
    
    <form class="search-form" onsubmit="return false;">
          <button type="submit" class="btn btn-login">Search</button>
        <input type="text" id="searchInput" placeholder="Search tenant name...">
      
    </form>
</div>
    <div style="clear: both;"></div> <!-- para mo-break ang float -->


<?php
$activeQuery = $conn->query("SELECT COUNT(*) as active_count FROM tenants WHERE status='Active'");
$inactiveQuery = $conn->query("SELECT COUNT(*) as inactive_count FROM tenants WHERE status='Inactive'");

$activeCount = $activeQuery->fetch_assoc()['active_count'];
$inactiveCount = $inactiveQuery->fetch_assoc()['inactive_count'];

$activeTenantsResult = $conn->query("SELECT * FROM tenants WHERE status='Active' ORDER BY tenant_name, date_started ASC");
$activeTenants = [];
$activeTenantIds = [];
if ($activeTenantsResult) {
    while ($row = $activeTenantsResult->fetch_assoc()) {
        $activeTenants[] = $row;
        $activeTenantIds[] = (int)$row['tenant_id'];
    }
}

$inactiveTenantsResult = $conn->query("SELECT * FROM tenants WHERE status='Inactive' ORDER BY tenant_name, date_started DESC");
$inactiveTenants = [];
$inactiveTenantIds = [];
if ($inactiveTenantsResult) {
    while ($row = $inactiveTenantsResult->fetch_assoc()) {
        $inactiveTenants[] = $row;
        $inactiveTenantIds[] = (int)$row['tenant_id'];
    }
}

$activeAssignments = TenantAssignments::getAssignmentsForTenants($conn, $activeTenantIds);
$inactiveAssignments = TenantAssignments::getAssignmentsForTenants($conn, $inactiveTenantIds);
?>

<div style="margin-top:10px; font-weight:bold;">
    <span style="color:#5A7D7C;">Active Tenants: <?= $activeCount; ?></span><br>
    <span style="color:#dc3545;">Inactive Tenants: <?= $inactiveCount; ?></span>
</div>

<!-- FILTER TABS -->
<ul class="nav nav-tabs mb-3" id="tenantTabs">
  <li class="nav-item">
    <a class="nav-link active" data-bs-toggle="tab" href="#activeTenants">Active Tenants</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" data-bs-toggle="tab" href="#inactiveTenants">Inactive Tenants</a>
  </li>
</ul>

<div class="tab-content">

    <!-- ACTIVE TENANTS -->
  <div class="tab-pane fade show active" id="activeTenants">
    <div class="table-container" style="overflow-x:auto; overflow-y:auto; max-height:500px; border:1px solid #ddd; border-radius:8px; padding:5px;">
      <table id="tenantTable" style="width:100%; min-width:1000px; border-collapse:collapse;">
        <thead style="background-color:#f5f5f5; position:sticky; top:0; z-index:1;">
          <tr>
            <th>Profile</th>
            <th>Proof</th>
            <th>Name</th>
            <th>Room</th>
            <th>Room Type</th>
            <th>Deck</th>
            <th>Address</th>
            <th>Contact</th>
            <th>Guardian</th>
            <th>Date Started</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
        <?php
        if (!empty($activeTenants)) {
            foreach ($activeTenants as $tenant) {
                $tenantId = (int)$tenant['tenant_id'];
                $assignments = $activeAssignments[$tenantId] ?? [];

                $roomNumbers = [];
                $roomTypes = [];
                $deckLabels = [];

                foreach ($assignments as $assignment) {
                    $roomNumbers[] = htmlspecialchars($assignment['room_number'], ENT_QUOTES, 'UTF-8');
                    $roomTypes[] = htmlspecialchars($assignment['room_type'], ENT_QUOTES, 'UTF-8');
                    $deckLabels[] = htmlspecialchars($assignment['deck_type'] ?: 'Whole Room', ENT_QUOTES, 'UTF-8');
                }

                if (empty($roomNumbers)) {
                    $roomNumbers[] = '<span class="text-muted">Unassigned</span>';
                    $roomTypes[] = '<span class="text-muted">N/A</span>';
                    $deckLabels[] = '<span class="text-muted">N/A</span>';
                }

                $editPayload = [
                    'id' => $tenantId,
                    'name' => $tenant['tenant_name'] ?? '',
                    'address' => $tenant['address'] ?? '',
                    'contact' => $tenant['contact_number'] ?? '',
                    'guardian' => $tenant['guardian_contact'] ?? '',
                    'assignments' => $assignments,
                    'date_started' => $tenant['date_started'] ?? '',
                ];
                $editDataAttr = htmlspecialchars(json_encode($editPayload, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');

                echo '<tr>';
                echo '<td>'.(!empty($tenant['profile_pic'])
                    ? '<img src="'.BASE_PATH.'/'.htmlspecialchars($tenant['profile_pic']).'" class="profile-pic">'
                    : '<i class="fa-solid fa-circle-user fa-2x"></i>').'</td>';
                echo '<td>'.(!empty($tenant['proof_pic'])
                    ? '<img src="'.BASE_PATH.'/'.htmlspecialchars($tenant['proof_pic']).'" class="proof-pic">'
                    : '<i class="fa-solid fa-circle-user fa-2x"></i>').'</td>';
                echo '<td>'.htmlspecialchars($tenant['tenant_name']).'</td>';
                echo '<td>'.implode('<br>', $roomNumbers).'</td>';
                echo '<td>'.implode('<br>', $roomTypes).'</td>';
                echo '<td>'.implode('<br>', $deckLabels).'</td>';
                echo '<td>'.htmlspecialchars($tenant['address']).'</td>';
                echo '<td>'.htmlspecialchars($tenant['contact_number']).'</td>';
                echo '<td>'.htmlspecialchars($tenant['guardian_contact']).'</td>';
                echo '<td>'.htmlspecialchars($tenant['date_started']).'</td>';
                echo '<td><span style="color:green;">Active</span></td>';
                echo '<td>
                        <a href="#" class="btn btn-edit" data-edit="'.$editDataAttr.'">Edit</a>
                        <a href="delete.php?id='.$tenantId.'" class="btn btn-delete" data-id="'.$tenantId.'" data-name="'.htmlspecialchars($tenant['tenant_name'], ENT_QUOTES).'">Inactive</a>
                      </td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="12" style="text-align:center;">No active tenants</td></tr>';
        }
        ?>


        </tbody>
      </table>
    </div>
  </div>

  <!-- INACTIVE TENANTS -->
<div class="tab-pane fade" id="inactiveTenants">
  <div class="table-container" style="overflow-x:auto; overflow-y:auto; max-height:500px; border:1px solid #ddd; border-radius:8px; padding:5px;">
    <table id="inactiveTenantTable" style="width:100%; min-width:1000px; border-collapse:collapse;">
      <thead style="background-color:#f5f5f5; position:sticky; top:0; z-index:1;">
        <tr>
          <th>Profile</th>
          <th>Proof</th>
          <th>Name</th>
          <th>Room</th>
          <th>Address</th>
          <th>Contact</th>
          <th>Guardian</th>
          <th>Date Started</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if (!empty($inactiveTenants)) {
            foreach ($inactiveTenants as $tenant) {
                $tenantId = (int)$tenant['tenant_id'];
                $assignments = $inactiveAssignments[$tenantId] ?? [];

                $roomSummary = [];
                foreach ($assignments as $assignment) {
                    $roomSummary[] = htmlspecialchars($assignment['room_number'].' — '.($assignment['deck_type'] ?: 'Whole Room'), ENT_QUOTES, 'UTF-8');
                }
                if (empty($roomSummary)) {
                    $roomSummary[] = '<span class="text-muted">Unassigned</span>';
                }

                echo '<tr>';
                echo '<td>'.(!empty($tenant['profile_pic'])
                    ? '<img src="'.BASE_PATH.'/'.htmlspecialchars($tenant['profile_pic']).'" class="profile-pic">'
                    : '<i class="fa-solid fa-circle-user fa-2x"></i>').'</td>';
                echo '<td>'.(!empty($tenant['proof_pic'])
                    ? '<img src="'.BASE_PATH.'/'.htmlspecialchars($tenant['proof_pic']).'" class="proof-pic">'
                    : '<i class="fa-solid fa-circle-user fa-2x"></i>').'</td>';
                echo '<td>'.htmlspecialchars($tenant['tenant_name']).'</td>';
                echo '<td>'.implode('<br>', $roomSummary).'</td>';
                echo '<td>'.htmlspecialchars($tenant['address']).'</td>';
                echo '<td>'.htmlspecialchars($tenant['contact_number']).'</td>';
                echo '<td>'.htmlspecialchars($tenant['guardian_contact']).'</td>';
                echo '<td>'.htmlspecialchars($tenant['date_started']).'</td>';
                echo '<td><span style="color:red;">Inactive</span></td>';
                echo '</tr>';
            }
        } else {
            echo "<tr><td colspan='9' style='text-align:center;'>No inactive tenants</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>
</div>

<!-- LINK TO ADD TENANT MODAL -->
<?php include '../../forms/add_tenant_modal_form.php'; ?>

<!-- LINK TO EDIT TENANT MODAL -->
<?php include '../../forms/edit_tenant_modal_form.php'; ?>

<script src="<?= BASE_PATH ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= BASE_PATH ?>/js/sweetalert-helpers.js"></script>
<script>
(function () {
    // Display SweetAlert messages from PHP session
<?php if (isset($_SESSION['swal_success'])): ?>
    AlertHelper.success('Success', '<?= addslashes($_SESSION['swal_success']) ?>');
    <?php unset($_SESSION['swal_success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['swal_error'])): ?>
    AlertHelper.error('Error', '<?= addslashes($_SESSION['swal_error']) ?>');
    <?php unset($_SESSION['swal_error']); ?>
<?php endif; ?>

    const inventoryElement = document.getElementById('roomInventoryJson');
    const roomInventory = inventoryElement ? JSON.parse(inventoryElement.textContent || '[]') : [];
    const roomInventoryMap = new Map(roomInventory.map(item => [String(item.room_id), item]));

    let addAssignmentManager = null;
    let editAssignmentManager = null;

    function buildRoomLabel(room) {
        const base = `${room.room_number} • ${room.room_type}`;
        if (room.room_type === 'Whole Room') {
            return `${base} (Available: ${room.available_slots})`;
        }
        return `${base} (Upper ${room.upper_available}/${room.upper_deck_count}, Lower ${room.lower_available}/${room.lower_deck_count})`;
    }

    function initAssignmentManager(containerId, addButtonId, templateId) {
        const container = document.getElementById(containerId);
        const addButton = document.getElementById(addButtonId);
        const template = document.getElementById(templateId);

        if (!container || !template) {
            return null;
        }

        const minRows = parseInt(container.dataset.minRows || '1', 10);
        const roomFieldName = container.dataset.roomName || 'room_id[]';
        const deckFieldName = container.dataset.deckName || 'deck_type[]';

        function getRows() {
            return Array.from(container.querySelectorAll('[data-room-row]'));
        }

        function getSelectedRooms(excludeRow) {
            return getRows()
                .filter(row => row !== excludeRow)
                .map(row => row.querySelector('.roomSelect')?.value)
                .filter(value => value);
        }

        function ensureFallbackOption(select, selectedValue, rowEl) {
            if (!selectedValue || roomInventoryMap.has(selectedValue)) {
                return;
            }
            const option = document.createElement('option');
            option.value = selectedValue;
            const fallbackLabel = rowEl?.dataset.initialRoomNumber
                ? `${rowEl.dataset.initialRoomNumber} • ${rowEl.dataset.initialRoomType || 'Room'}`
                : `Room #${selectedValue}`;
            option.textContent = `${fallbackLabel} (Inactive)`;
            option.dataset.roomType = rowEl?.dataset.initialRoomType || '';
            select.appendChild(option);
        }

        function populateRoomOptions(select, selectedRoomId, rowEl) {
            const selectedValue = selectedRoomId ? String(selectedRoomId) : '';
            const selectedInOthers = new Set(getSelectedRooms(rowEl));

            select.innerHTML = '';
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = '-- Select Room --';
            select.appendChild(placeholder);

            roomInventory.forEach(room => {
                const option = document.createElement('option');
                option.value = String(room.room_id);
                option.textContent = buildRoomLabel(room);
                option.dataset.roomType = room.room_type;

                if (selectedInOthers.has(option.value) && option.value !== selectedValue) {
                    option.disabled = true;
                    option.textContent += ' (Already selected)';
                } else if (room.available_slots <= 0 && option.value !== selectedValue) {
                    option.disabled = true;
                    option.textContent += ' (Full)';
                }

                select.appendChild(option);
            });

            ensureFallbackOption(select, selectedValue, rowEl);
            select.value = selectedValue;
        }

        function updateDeckState(roomSelect, deckSelect, selectedDeck) {
            const roomId = roomSelect.value;
            const room = roomInventoryMap.get(roomId);

            if (!roomId || !room) {
                deckSelect.innerHTML = '<option value="">Deck</option>';
                deckSelect.value = '';
                deckSelect.disabled = true;
                deckSelect.required = false;
                return;
            }

            if (room.room_type === 'Whole Room') {
                deckSelect.innerHTML = '<option value="">Whole Room</option>';
                deckSelect.value = '';
                deckSelect.disabled = true;
                deckSelect.required = false;
                return;
            }

            deckSelect.disabled = false;
            deckSelect.required = true;
            deckSelect.innerHTML = '';

            const decks = [
                {
                    value: 'Lower Deck',
                    available: room.lower_available,
                    label: `Lower Deck (${room.lower_available}/${room.lower_deck_count} available)`
                },
                {
                    value: 'Upper Deck',
                    available: room.upper_available,
                    label: `Upper Deck (${room.upper_available}/${room.upper_deck_count} available)`
                }
            ];

            decks.forEach(deck => {
                const option = document.createElement('option');
                option.value = deck.value;
                option.textContent = deck.label;
                if (deck.available <= 0 && deck.value !== selectedDeck) {
                    option.disabled = true;
                    option.textContent += ' (Full)';
                }
                deckSelect.appendChild(option);
            });

            if (selectedDeck) {
                deckSelect.value = selectedDeck;
            }

            if (!deckSelect.value) {
                const firstEnabled = Array.from(deckSelect.options).find(opt => !opt.disabled && opt.value);
                deckSelect.value = firstEnabled ? firstEnabled.value : '';
            }
        }

        function refreshRoomOptions(excludeRow = null) {
            getRows().forEach(row => {
                if (row === excludeRow) {
                    return;
                }
                const roomSelect = row.querySelector('.roomSelect');
                const deckSelect = row.querySelector('.deckSelect');
                const currentValue = roomSelect?.value || '';
                populateRoomOptions(roomSelect, currentValue, row);
                updateDeckState(roomSelect, deckSelect, deckSelect?.value || '');
            });
        }

        function attachRowHandlers(row, initial = {}) {
            const roomSelect = row.querySelector('.roomSelect');
            const deckSelect = row.querySelector('.deckSelect');
            const removeButton = row.querySelector('.remove-room');

            row.dataset.initialRoomNumber = initial.room_number || '';
            row.dataset.initialRoomType = initial.room_type || '';

            if (roomSelect) {
                roomSelect.name = roomFieldName;
            }
            if (deckSelect) {
                deckSelect.name = deckFieldName;
            }

            roomSelect?.addEventListener('change', () => {
                populateRoomOptions(roomSelect, roomSelect.value, row);
                updateDeckState(roomSelect, deckSelect, deckSelect?.value || '');
                refreshRoomOptions(row);
            });

            deckSelect?.addEventListener('change', () => {
                updateDeckState(roomSelect, deckSelect, deckSelect.value);
            });

            removeButton?.addEventListener('click', () => {
                if (getRows().length > minRows) {
                    row.remove();
                    refreshRoomOptions();
                }
            });
        }

        function addRow(initial = {}) {
            const fragment = template.content.firstElementChild.cloneNode(true);
            attachRowHandlers(fragment, initial);
            container.appendChild(fragment);
            const roomSelect = fragment.querySelector('.roomSelect');
            const deckSelect = fragment.querySelector('.deckSelect');
            populateRoomOptions(roomSelect, initial.room_id ?? initial.roomId ?? null, fragment);
            updateDeckState(roomSelect, deckSelect, initial.deck_type ?? initial.deckType ?? '');
            return fragment;
        }

        if (addButton) {
            addButton.addEventListener('click', () => {
                addRow({});
                refreshRoomOptions();
            });
        }

        return {
            reset(assignments = []) {
                container.innerHTML = '';
                const rows = assignments.length ? assignments : Array.from({ length: minRows }, () => ({}));
                rows.forEach(item => addRow(item));
                refreshRoomOptions();
            }
        };
    }

    function parseAssignments(json) {
        try {
            const parsed = JSON.parse(json || '[]');
            return Array.isArray(parsed) ? parsed : [];
        } catch (err) {
            console.warn('Invalid assignments payload', err);
            return [];
        }
    }

    function enforceNumericInputs(selectors) {
        document.querySelectorAll(selectors).forEach(input => {
            input.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '').slice(0, 11);
            });
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        addAssignmentManager = initAssignmentManager('addRoomContainer', 'addRoomRowBtn', 'roomRowTemplate');
        if (addAssignmentManager) {
            addAssignmentManager.reset([]);
        }

        editAssignmentManager = initAssignmentManager('editRoomContainer', 'editAddRoomRowBtn', 'editRoomRowTemplate');

        const searchInput = document.getElementById('searchInput');
        const tenantTableBody = document.querySelector('#activeTenants tbody');
        const notFoundBanner = document.getElementById('notFound');

        if (searchInput && tenantTableBody) {
            searchInput.addEventListener('input', function () {
                const filter = this.value.toLowerCase().trim();
                const rows = tenantTableBody.querySelectorAll('tr');
                let found = false;

                rows.forEach(row => {
                    const nameCell = row.querySelector('td:nth-child(3)');
                    const nameText = nameCell ? nameCell.textContent.toLowerCase() : '';

                    if (filter && nameText.includes(filter)) {
                        row.style.display = '';
                        row.classList.add('highlight');
                        found = true;
                    } else if (!filter) {
                        row.style.display = '';
                        row.classList.remove('highlight');
                        found = true;
                    } else {
                        row.style.display = 'none';
                        row.classList.remove('highlight');
                    }
                });

                if (notFoundBanner) {
                    notFoundBanner.style.display = found ? 'none' : 'block';
                }
            });
        }

        enforceNumericInputs('input[name="contact_number"], input[name="guardian_contact"], #editTenantContact, #editTenantGuardian');
    });

    window.openModal = function () {
        addAssignmentManager?.reset([]);
        document.getElementById('tenantModal').style.display = 'flex';
    };

    window.closeModal = function () {
        document.getElementById('tenantModal').style.display = 'none';
    };

    window.openEditModal = function (id, name, address, contact, guardian, assignmentsJson, dateStarted) {
        document.getElementById('editTenantId').value = id;
        document.getElementById('editTenantName').value = name;
        document.getElementById('editTenantAddress').value = address;
        document.getElementById('editTenantContact').value = contact;
        document.getElementById('editTenantGuardian').value = guardian;
        document.getElementById('editTenantDate').value = dateStarted;

        const assignments = parseAssignments(assignmentsJson);
        editAssignmentManager?.reset(assignments);

        const modal = new bootstrap.Modal(document.getElementById('editTenantModal'));
        modal.show();
    };

    window.confirmSave = function () {
        const form = document.getElementById('addTenantForm');
        if (!form) {
            return false;
        }

        const tenantInput = form.querySelector('input[name="tenant_name"]');
        const roomSelects = form.querySelectorAll('#addRoomContainer .roomSelect');

        const allRoomsSelected = Array.from(roomSelects).every(select => select.value);
        if (!allRoomsSelected) {
            AlertHelper?.error('Incomplete', 'Please select a room for each assignment.');
            return false;
        }

        const tenantName = tenantInput ? tenantInput.value.trim().toUpperCase() : 'this tenant';
        return confirm(`Are you sure you want to save tenant "${tenantName}"?`);
    };

    window.capitalizeName = function (input) {
        const words = input.value.toLowerCase().split(' ').map(word => word ? word[0].toUpperCase() + word.slice(1) : '');
        input.value = words.join(' ');
    };

    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();

            const raw = this.getAttribute('data-edit') || '{}';
            let payload = null;

            try {
                payload = JSON.parse(raw);
            } catch (err) {
                console.error('Invalid edit payload', err);
                AlertHelper?.error('Error', 'Unable to load tenant details for editing.');
                return;
            }

            if (!editAssignmentManager) {
                AlertHelper?.error('Error', 'Unable to load room assignments for editing.');
                return;
            }

            openEditModal(
                payload.id ?? '',
                payload.name ?? '',
                payload.address ?? '',
                payload.contact ?? '',
                payload.guardian ?? '',
                JSON.stringify(payload.assignments ?? []),
                payload.date_started ?? ''
            );
        });
    });

    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();

            const tenantRow = this.closest('tr');
            const url = this.href;
            const tenantName = this.getAttribute('data-name') || 'this tenant';

            Swal.fire({
                title: 'Are you sure?',
                text: `Do you want to delete ${tenantName}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(url)
                        .then(res => res.text())
                        .then(data => {
                            if (data.trim() === 'success') {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: `${tenantName} has been deleted successfully.`,
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                });

                                tenantRow.remove();
                            } else {
                                Swal.fire('Error', 'Failed to delete tenant.', 'error');
                            }
                        })
                        .catch(() => {
                            Swal.fire('Error', 'Something went wrong while deleting.', 'error');
                        });
                }
            });
        });
    });
})();
</script>
</body>
</html>
