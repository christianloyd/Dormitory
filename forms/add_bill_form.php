<?php
// ✅ Assumes session_start() is already called in viewbill.php

if (!function_exists('computeNextDueDateForRoom')) {
    function computeNextDueDateForRoom(?string $lastDue, string $fallbackDate): string
    {
        $reference = $lastDue ?: $fallbackDate;
        if (!$reference) {
            $reference = date('Y-m-d');
        }

        try {
            $referenceDate = new DateTime($reference);
        } catch (Exception $e) {
            $referenceDate = new DateTime(date('Y-m-d'));
        }

        $originalDay = (int)$referenceDate->format('d');
        $referenceDate->modify('+1 month');
        $year = (int)$referenceDate->format('Y');
        $month = (int)$referenceDate->format('n');
        $day = min($originalDay, cal_days_in_month(CAL_GREGORIAN, $month, $year));
        $referenceDate->setDate($year, $month, $day);

        return $referenceDate->format('Y-m-d');
    }
}

$start_day = intval(date('d', strtotime($tenant['date_started'])));
$tenant_id = $tenant['tenant_id'];
$defaultRoomId = isset($default_room_id) ? (int)$default_room_id : 0;
$defaultBaseRent = isset($default_base_rent) ? (float)$default_base_rent : 0.0;

// Compute room-specific last due dates
$roomLastDueDates = [];
if (!empty($rooms)) {
    $lastRoomStmt = $conn->prepare("SELECT room_id, MAX(due_date) AS last_due_date FROM billing WHERE tenant_id = ? GROUP BY room_id");
    if ($lastRoomStmt) {
        $lastRoomStmt->bind_param('i', $tenant_id);
        $lastRoomStmt->execute();
        $lastRoomResult = $lastRoomStmt->get_result();
        while ($row = $lastRoomResult->fetch_assoc()) {
            $roomId = isset($row['room_id']) ? (int)$row['room_id'] : 0;
            if ($roomId > 0 && !empty($row['last_due_date'])) {
                $roomLastDueDates[$roomId] = $row['last_due_date'];
            }
        }
        $lastRoomStmt->close();
    }

    foreach ($rooms as &$roomInfo) {
        $roomId = isset($roomInfo['room_id']) ? (int)$roomInfo['room_id'] : 0;
        $lastDue = $roomLastDueDates[$roomId] ?? null;
        $roomInfo['last_due_date'] = $lastDue;
        $roomInfo['next_due_date'] = computeNextDueDateForRoom($lastDue, $tenant['date_started']);
    }
    unset($roomInfo);
}

if (!$defaultRoomId && !empty($rooms)) {
    $first = $rooms[0];
    $defaultRoomId = (int)($first['room_id'] ?? 0);
    $defaultBaseRent = isset($first['price']) ? (float)$first['price'] : 0.0;
}

$defaultRoomLastDue = $rooms[0]['last_due_date'] ?? null;
$default_due_date = $rooms[0]['next_due_date'] ?? computeNextDueDateForRoom(null, $tenant['date_started']);
$billing_exists = !empty($defaultRoomLastDue);

// Fetch additional charges (room descriptions)
$room_id = $defaultRoomId;
$additional_descriptions = [];
if($room_id){
    $descResult = $conn->query("SELECT description FROM room_additional_descriptions WHERE room_id = $room_id ORDER BY description ASC");
    while($row = $descResult->fetch_assoc()){
        $additional_descriptions[] = $row['description'];
    }
}

$roomOptions = '<option value="">-- Select Room --</option>';
if (!empty($rooms)) {
    foreach ($rooms as $room) {
        $roomId = (int)($room['room_id'] ?? 0);
        $roomNumber = htmlspecialchars($room['room_number'] ?? ('Room #' . $roomId), ENT_QUOTES, 'UTF-8');
        $roomType = htmlspecialchars($room['room_type'] ?? 'Room', ENT_QUOTES, 'UTF-8');
        $roomPrice = isset($room['price']) ? number_format((float)$room['price'], 2, '.', '') : '0.00';
        $selected = ($roomId === $defaultRoomId) ? ' selected' : '';
        $label = trim($roomNumber . ' • ' . $roomType);
        $lastDue = $room['last_due_date'] ?? '';
        $nextDue = $room['next_due_date'] ?? computeNextDueDateForRoom(null, $tenant['date_started']);
        $roomOptions .= '<option value="' . $roomId . '" data-room-type="' . $roomType . '" data-room-price="' . $roomPrice . '" data-last-due="' . htmlspecialchars($lastDue, ENT_QUOTES, 'UTF-8') . '" data-next-due="' . htmlspecialchars($nextDue, ENT_QUOTES, 'UTF-8') . '"' . $selected . '>' . $label . '</option>';
    }
}
?>

<!-- Add Bill Modal -->
<div class="modal fade" id="addBillModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post" action="save.php">
        <div class="modal-header">
          <h5 class="modal-title">
            Add Bill for <?= htmlspecialchars($tenant['tenant_name']); ?>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="container-fluid">

            <div id="roomDueDateNotice" class="alert alert-info" style="display:none;"></div>

            <input type="hidden" name="tenant_id" value="<?= $tenant_id; ?>">
            <input type="hidden" name="base_rent" id="baseRentInput" value="<?= number_format($defaultBaseRent, 2, '.', ''); ?>">
            <input type="hidden" name="reserved_amount" id="reservationAmount" value="0">
            <input type="hidden" name="reservation_flag" id="reservationFlag" value="0">

            <!-- Due Date -->
            <div class="row mb-3">
              <div class="col-md-6">
                <label><b>Due Date</b></label>
                <input type="text" id="due_date_picker" name="due_date" class="form-control" required value="<?= $default_due_date; ?>">
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-4">
                <label><b>Room Number</b></label>
                <select name="room_id" id="roomSelect" class="form-control" required>
                  <?= $roomOptions; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label><b>Number of Tenants</b></label>
                <input type="number" id="tenantCount" class="form-control" value="0" readonly>
              </div>
              <div class="col-md-4">
                <label><b>Amount (₱)</b></label>
                <input type="number" step="0.01" id="billAmount" name="utility_amount[]" class="form-control" min="0" value="0" required>
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-4">
                <label><b>Type</b></label>
                <input type="text" name="utility_fee[]" class="form-control" value="Electricity" readonly>
              </div>
              <div class="col-md-4">
                <label><b>Per Tenant (₱)</b></label>
                <input type="number" step="0.01" id="perTenant" name="utility_per_tenant[]" class="form-control" value="0" readonly>
              </div>
            </div>

            <button type="button" class="btn btn-sm btn-success mb-3" id="addUtility">+ Add Utility</button>
            <hr>

            <!-- Additional Charges -->
            <div id="charge-container">
              <div class="row mb-2 charge-row">
                <div class="col-md-6">
                  <label><b>Additional Charges</b></label>
                  <select name="add_charges[]" class="form-control add-charge-select">
                    <option value="">-- Select Additional Charge --</option>
                    <?php foreach($additional_descriptions as $desc): ?>
                        <option value="<?= htmlspecialchars($desc) ?>"><?= htmlspecialchars($desc) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-5">
                  <label><b>Charges Amount</b></label>
                  <div class="input-group">
                    <span class="input-group-text">₱</span>
                    <input type="number" step="0.01" name="add_amount[]" class="form-control" value="0">
                  </div>
                </div>
                <div class="col-md-1 d-flex align-items-center">
                  <button type="button" class="btn btn-danger btn-sm remove-charge">&times;</button>
                </div>
              </div>
            </div>
            <button type="button" class="btn btn-sm btn-success mb-3" id="addCharge">+ Add Charge</button>


          </div>
        </div>

        <div class="modal-footer d-flex justify-content-center">
          <button type="submit" class="btn btn-primary">Save Bill</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Flatpickr -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    let startDay = <?= $start_day; ?>;
    let defaultDate = "<?= $default_due_date; ?>";

    const dueDatePicker = flatpickr("#due_date_picker", {
        dateFormat: "Y-m-d",
        defaultDate: defaultDate,
        disable: [
            function(date){ return date.getDate() !== startDay; }
        ],
        onChange: function() {
            refreshReservationPrefill();
        }
    });

    function renderChargeOptions(selectEl, descriptions) {
        const currentValue = selectEl.value;
        selectEl.innerHTML = '';
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = '-- Select Additional Charge --';
        selectEl.appendChild(defaultOption);

        descriptions.forEach(desc => {
            const option = document.createElement('option');
            option.value = desc;
            option.textContent = desc;
            if (desc === currentValue) {
                option.selected = true;
            }
            selectEl.appendChild(option);
        });
    }

    function refreshChargeDropdowns(descriptions) {
        const selects = document.querySelectorAll('.add-charge-select');
        selects.forEach(select => renderChargeOptions(select, descriptions));
    }

    document.getElementById('addCharge').addEventListener('click', function(){
        let container = document.getElementById('charge-container');
        let row = document.createElement('div');
        row.className = "row mb-2 charge-row";
        row.innerHTML = `
          <div class="col-6">
            <select name="add_charges[]" class="form-control add-charge-select"></select>
          </div>
          <div class="col-5">
            <div class="input-group">
              <span class="input-group-text">₱</span>
              <input type="number" step="0.01" name="add_amount[]" class="form-control" value="0">
            </div>
          </div>
          <div class="col-1 d-flex align-items-center">
            <button type="button" class="btn btn-danger btn-sm remove-charge">&times;</button>
          </div>
        `;
        container.appendChild(row);
        refreshChargeDropdowns(currentCharges);
    });

    document.addEventListener('click', function(e){
        if(e.target.classList.contains('remove-charge')){
            e.target.closest('.charge-row').remove();
        }
    });

    const roomSelect = document.getElementById('roomSelect');
    const tenantCountInput = document.getElementById('tenantCount');
    const amountInput = document.getElementById('billAmount');
    const perTenantInput = document.getElementById('perTenant');
    const baseRentInput = document.getElementById('baseRentInput');
    const dueNotice = document.getElementById('roomDueDateNotice');
    const tenantIdInput = document.querySelector('input[name="tenant_id"]');
    const dueDateInput = document.getElementById('due_date_picker');
    const hiddenReservationAmount = document.getElementById('reservationAmount');
    const hiddenReservationFlag = document.getElementById('reservationFlag');
    let latestReservationKey = '';

    function updatePerTenant() {
        const amount = parseFloat(amountInput.value);
        const tenantCount = parseInt(tenantCountInput.value, 10);

        if (amountInput.dataset.prefillReservation === 'true') {
            perTenantInput.value = (amount > 0 ? amount.toFixed(2) : 0);
            return;
        }

        if (!tenantCount || tenantCount <= 0) {
            perTenantInput.value = (amount > 0 ? amount.toFixed(2) : 0);
            return;
        }

        perTenantInput.value = (amount > 0 ? (amount / tenantCount).toFixed(2) : 0);
    }

    let currentCharges = <?php
        $escaped = array_map(fn($d) => htmlspecialchars($d, ENT_QUOTES), $additional_descriptions);
        echo json_encode($escaped);
    ?>;

    function updateBaseRent(roomId) {
        if (!baseRentInput) {
            return;
        }

        if (!roomId) {
            baseRentInput.value = '0.00';
            return;
        }

        const options = Array.from(roomSelect.options || []);
        const match = options.find(opt => opt.value === String(roomId));
        const price = match ? match.dataset.roomPrice : null;
        baseRentInput.value = price || '0.00';

        if (dueNotice) {
            const lastDue = match ? match.dataset.lastDue : '';
            const nextDue = match ? match.dataset.nextDue : '';
            if (nextDue) {
                const lastDueText = lastDue ? `Last bill due: ${lastDue}. ` : '';
                dueNotice.textContent = `${lastDueText}Next due date suggested: ${nextDue}.`;
                dueNotice.style.display = 'block';
            } else {
                dueNotice.textContent = 'No previous bills found for this room. Due date will default to one month after start date.';
                dueNotice.style.display = 'block';
            }
        }

        if (dueDatePicker) {
            const nextDue = match ? match.dataset.nextDue : '';
            if (nextDue) {
                dueDatePicker.setDate(nextDue, true);
            }
        }
    }

    function clearReservationPrefill() {
        if (amountInput.dataset.prefillReservation) {
            delete amountInput.dataset.prefillReservation;
        }
        amountInput.value = '0';
        if (hiddenReservationAmount) {
            hiddenReservationAmount.value = '0';
        }
        if (hiddenReservationFlag) {
            hiddenReservationFlag.value = '0';
        }
        updatePerTenant();
    }

    function applyReservationPrefill(totals) {
        const reserved = parseFloat((totals && (totals['Electricity'] ?? totals['electricity'])) || 0);
        if (reserved > 0) {
            amountInput.dataset.prefillReservation = 'true';
            amountInput.value = reserved.toFixed(2);
            if (hiddenReservationAmount) {
                hiddenReservationAmount.value = reserved.toFixed(2);
            }
            if (hiddenReservationFlag) {
                hiddenReservationFlag.value = '1';
            }
            updatePerTenant();
        } else {
            clearReservationPrefill();
        }
    }

    function refreshReservationPrefill() {
        const roomId = roomSelect ? roomSelect.value : '';
        const dueDate = dueDateInput ? dueDateInput.value : '';
        const tenantId = tenantIdInput ? tenantIdInput.value : '';

        if (!tenantId || !roomId || !dueDate) {
            clearReservationPrefill();
            return;
        }

        const key = `${tenantId}|${roomId}|${dueDate}`;
        latestReservationKey = key;

        fetch(`get_utility_reservations.php?tenant_id=${encodeURIComponent(tenantId)}&room_id=${encodeURIComponent(roomId)}&due_date=${encodeURIComponent(dueDate)}`)
            .then(response => response.json())
            .then(data => {
                if (latestReservationKey !== key) {
                    return;
                }

                if (data.success && data.totals) {
                    applyReservationPrefill(data.totals);
                } else {
                    clearReservationPrefill();
                }

                updatePerTenant();
            })
            .catch(() => {
                if (latestReservationKey === key) {
                    clearReservationPrefill();
                    updatePerTenant();
                }
            });
    }

    function fetchTenantCount(roomId) {
        if (!roomId) {
            tenantCountInput.value = 0;
            updatePerTenant();
            currentCharges = [];
            refreshChargeDropdowns(currentCharges);
            updateBaseRent('');
            clearReservationPrefill();
            return;
        }

        fetch(`get_tenant_count.php?room_id=${roomId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    tenantCountInput.value = data.count;
                } else {
                    tenantCountInput.value = 0;
                    console.error(data.message || 'Failed to fetch tenant count');
                }
                updatePerTenant();
                refreshReservationPrefill();
            })
            .catch(error => {
                console.error('Error fetching tenant count:', error);
                tenantCountInput.value = 0;
                updatePerTenant();
                refreshReservationPrefill();
            });

        fetch(`get_room_charges.php?room_id=${roomId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentCharges = data.descriptions || [];
                } else {
                    currentCharges = [];
                    console.error(data.message || 'Failed to fetch room charges');
                }
                refreshChargeDropdowns(currentCharges);
            })
            .catch(error => {
                console.error('Error fetching room charges:', error);
                currentCharges = [];
                refreshChargeDropdowns(currentCharges);
            });
    }

    roomSelect.addEventListener('change', function() {
        const roomId = this.value;
        fetchTenantCount(roomId);
        updateBaseRent(roomId);
        refreshReservationPrefill();
    });

    amountInput.addEventListener('input', function() {
        if (amountInput.dataset.prefillReservation) {
            delete amountInput.dataset.prefillReservation;
        }
        if (hiddenReservationFlag) {
            hiddenReservationFlag.value = '0';
        }
        updatePerTenant();
    });

    if (dueDateInput) {
        dueDateInput.addEventListener('change', refreshReservationPrefill);
    }

    // Initialise defaults
    const defaultRoomIdValue = "<?= $defaultRoomId; ?>";
    if (defaultRoomIdValue) {
        roomSelect.value = defaultRoomIdValue;
        updateBaseRent(defaultRoomIdValue);
        fetchTenantCount(defaultRoomIdValue);
        refreshReservationPrefill();
    } else {
        updateBaseRent('');
        fetchTenantCount('');
    }

    // --- Save via AJAX + SweetAlert ---
    $('#addBillModal form').on('submit', function(e){
        e.preventDefault();
        let form = $(this);
        Swal.fire({
            title: 'Confirm Save',
            text: 'Are you sure you want to save this billing?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, save it',
            cancelButtonText: 'Cancel'
        }).then((result)=>{
            if(result.isConfirmed){
                Swal.fire({
                    title: 'Saving...',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: ()=>{ Swal.showLoading(); }
                });
                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: form.serialize(),
                    dataType: 'json',
                    success: function(response){
                        if(response.success){
                            Swal.fire({
                                title: 'Success!',
                                text: 'Billing saved successfully!',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(()=>window.location.href="view.php?tenant_id="+response.tenant_id);
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function(xhr){
                        Swal.fire('Error', "Unexpected error: "+xhr.responseText, 'error');
                    }
                });
            }
        });
    });
});
</script>
