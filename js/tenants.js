function openModal() { document.getElementById('tenantModal').style.display = 'flex'; }
function closeModal() { document.getElementById('tenantModal').style.display = 'none'; }

function checkDeck() {
    const roomSelect = document.getElementById("roomSelect");
    const deckSelect = document.getElementById("deckSelect");

    const selectedOption = roomSelect.options[roomSelect.selectedIndex];
    if (!selectedOption) return;

    const roomType = selectedOption.dataset.roomType;

    if (roomType === "Whole Room") {
        deckSelect.value = "";
        deckSelect.disabled = true;
        deckSelect.querySelectorAll('option').forEach(opt => {
            if(opt.value !== "") opt.text = opt.value;
        });
        return;
    }

    deckSelect.disabled = false;

    const upperCount = parseInt(selectedOption.dataset.upper);
    const lowerCount = parseInt(selectedOption.dataset.lower);
    const upperOccupied = parseInt(selectedOption.dataset.upperOccupied);
    const lowerOccupied = parseInt(selectedOption.dataset.lowerOccupied);

    const upperAvailable = upperCount - upperOccupied;
    const lowerAvailable = lowerCount - lowerOccupied;

    const upperOption = deckSelect.querySelector('option[value="Upper Deck"]');
    const lowerOption = deckSelect.querySelector('option[value="Lower Deck"]');

    upperOption.disabled = (upperAvailable <= 0);
    lowerOption.disabled = (lowerAvailable <= 0);

    upperOption.style.color = (upperAvailable <= 0) ? "gray" : "black";
    lowerOption.style.color = (lowerAvailable <= 0) ? "gray" : "black";

    upperOption.text = upperAvailable > 0 ? `Upper Deck (${upperAvailable} slots)` : "Upper Deck (Full)";
    lowerOption.text = lowerAvailable > 0 ? `Lower Deck (${lowerAvailable} slots)` : "Lower Deck (Full)";

    if (deckSelect.value === "Upper Deck" && upperAvailable <= 0) deckSelect.value = "";
    if (deckSelect.value === "Lower Deck" && lowerAvailable <= 0) deckSelect.value = "";
}

document.getElementById("searchInput").addEventListener("input", function () {
    const filter = this.value.toLowerCase().trim();
    const rows = document.querySelectorAll("#tenantBody tr");
    let found = false;
    rows.forEach(row => {
        const name = row.querySelector(".tenant-name").textContent.toLowerCase();
        if (filter && name.includes(filter)) {
            row.style.display = "";
            row.classList.add("highlight");
            found = true;
        } else if (!filter) {
            row.style.display = "";
            row.classList.remove("highlight");
            found = true;
        } else {
            row.style.display = "none";
            row.classList.remove("highlight");
        }
    });
    document.getElementById("notFound").style.display = found ? "none" : "block";
});

function confirmSave() {
    const tenantInput = document.querySelector('input[name="tenant_name"]');
    const tenantName = tenantInput.value.trim().toUpperCase();
    return confirm(`Are you sure you want to save tenant "${tenantName}"?`);
}

function capitalizeName(input) {
    let words = input.value.toLowerCase().split(' ');
    for (let i = 0; i < words.length; i++) {
        if(words[i].length > 0) {
            words[i] = words[i][0].toUpperCase() + words[i].substr(1);
        }
    }
    input.value = words.join(' ');
}

// ---------- ADD THIS PART FOR FULLNAME + ADDRESS ---------- //
document.addEventListener("DOMContentLoaded", function () {
    const nameField = document.querySelector('input[name="tenant_name"]');
    const addressField = document.querySelector('input[name="address"]');

    if (nameField) {
        nameField.addEventListener("input", function () {
            capitalizeName(this);
        });
    }

    if (addressField) {
        addressField.addEventListener("input", function () {
            capitalizeName(this);
        });
    }
});


// ----------- EDIT TENANT MODAL FUNCTIONS -----------

// Open Edit Tenant modal and populate fields
function openEditModal(id, name, address, contact, guardian, room_id, deck_type){
    document.getElementById("editTenantId").value = id;
    document.getElementById("editTenantName").value = name;
    document.getElementById("editTenantAddress").value = address;
    document.getElementById("editTenantContact").value = contact;
    document.getElementById("editTenantGuardian").value = guardian;
    document.getElementById("roomSelectEdit").value = room_id;
    document.getElementById("deckSelectEdit").value = deck_type;

    // Update deck availability same as Add Tenant
    checkDeckEdit();

    // Show modal
    let modal = new bootstrap.Modal(document.getElementById('editTenantModal'));
    modal.show();
}

// Check deck availability for EDIT modal
function checkDeckEdit() {
    const roomSelect = document.getElementById("roomSelectEdit");
    const deckSelect = document.getElementById("deckSelectEdit");
    const deckWrapper = document.getElementById("deckWrapperEdit");

    const selectedOption = roomSelect.options[roomSelect.selectedIndex];
    if (!selectedOption) return;

    const roomType = selectedOption.dataset.roomType;

    if (roomType === "Whole Room") {
        deckWrapper.style.display = "none";
        deckSelect.value = "";
        return;
    }

    deckWrapper.style.display = "block";

    const upperCount = parseInt(selectedOption.dataset.upper);
    const lowerCount = parseInt(selectedOption.dataset.lower);
    const upperOccupied = parseInt(selectedOption.dataset.upperOccupied);
    const lowerOccupied = parseInt(selectedOption.dataset.lowerOccupied);

    const upperAvailable = upperCount - upperOccupied;
    const lowerAvailable = lowerCount - lowerOccupied;

    const upperOption = deckSelect.querySelector('option[value="Upper Deck"]');
    const lowerOption = deckSelect.querySelector('option[value="Lower Deck"]');

    // Upper
    if (upperAvailable > 0) {
        upperOption.disabled = false;
        upperOption.style.color = "black";
        upperOption.text = `Upper Deck (${upperAvailable} slots)`;
    } else {
        upperOption.disabled = true;
        upperOption.style.color = "gray";
        upperOption.text = "Upper Deck (0 slots)";
    }

    // Lower
    if (lowerAvailable > 0) {
        lowerOption.disabled = false;
        lowerOption.style.color = "black";
        lowerOption.text = `Lower Deck (${lowerAvailable} slots)`;
    } else {
        lowerOption.disabled = true;
        lowerOption.style.color = "gray";
        lowerOption.text = "Lower Deck (0 slots)";
    }

    if (deckSelect.value === "Upper Deck" && upperAvailable <= 0) deckSelect.value = "";
    if (deckSelect.value === "Lower Deck" && lowerAvailable <= 0) deckSelect.value = "";
}
