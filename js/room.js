document.addEventListener("DOMContentLoaded", function() {

    // --- MODAL FUNCTIONS ---
    function openModal(id) {
        let m = new bootstrap.Modal(document.getElementById(id));
        m.show();
    }

    window.openModal = openModal; // expose globally

    window.openEditModal = function(id, number, type, capacity, upper, lower, price){
        document.getElementById("edit_room_id").value = id;
        document.getElementById("edit_room_number").value = number;
        document.getElementById("edit_room_type").value = type;
        document.getElementById("edit_capacity").value = capacity;
        document.getElementById("edit_upper").value = upper;
        document.getElementById("edit_lower").value = lower;
        document.getElementById("edit_price").value = price;
        toggleDeckInputs('edit');
        openModal('editRoomModal');
    }

    window.toggleDeckInputs = function(modalType){
        let typeInput = document.getElementById(modalType==='add'?'add_room_type':'edit_room_type');
        let upper = document.getElementById(modalType==='add'?'add_upper':'edit_upper');
        let lower = document.getElementById(modalType==='add'?'add_lower':'edit_lower');
        let cap = document.getElementById(modalType==='add'?'add_capacity':'edit_capacity');
        if(typeInput.value==='Whole Room'){
            upper.value=0; lower.value=0; upper.disabled=true; lower.disabled=true; cap.value=1; cap.disabled=true;
        } else {
            upper.disabled=false; lower.disabled=false; cap.disabled=false;
        }
    }

    // --- VALIDATE BED SPACER CAPACITY ---
    const addForm = document.getElementById('addRoomForm');
    if(addForm){
        addForm.addEventListener('submit', function(e){
            const type = document.getElementById('add_room_type').value;
            if(type==='Bed Spacer'){
                const capacity = parseInt(document.getElementById('add_capacity').value) || 0;
                const upper = parseInt(document.getElementById('add_upper').value) || 0;
                const lower = parseInt(document.getElementById('add_lower').value) || 0;
                if((upper + lower) !== capacity){
                    e.preventDefault();
                    alert('For Bed Spacer, Upper + Lower deck count must equal the Capacity.');
                    return false;
                }
            }
        });
    }

    const editForm = document.getElementById('editRoomForm');
    if(editForm){
        editForm.addEventListener('submit', function(e){
            const type = document.getElementById('edit_room_type').value;
            if(type==='Bed Spacer'){
                const capacity = parseInt(document.getElementById('edit_capacity').value) || 0;
                const upper = parseInt(document.getElementById('edit_upper').value) || 0;
                const lower = parseInt(document.getElementById('edit_lower').value) || 0;
                if((upper + lower) !== capacity){
                    e.preventDefault();
                    alert('For Bed Spacer, Upper + Lower deck count must equal the Capacity.');
                    return false;
                }
            }
        });
    }

    // --- SEARCH FUNCTIONALITY ---
    const searchInput = document.getElementById("searchInput");
    if(searchInput){
        searchInput.addEventListener("input", function(){
            let filter = this.value.toLowerCase().trim();
            let rows = document.querySelectorAll("#roomBody tr");
            let found = false;

            rows.forEach(r => {
                let numElem = r.querySelector(".room-number");
                let statusElem = r.querySelector(".room-status");

                let num = numElem ? numElem.textContent.toLowerCase().trim() : "";
                let status = statusElem ? statusElem.textContent.toLowerCase().trim() : "";

                if(!filter || num.includes(filter) || status.includes(filter)){
                    r.style.display = "";
                    found = true;
                } else {
                    r.style.display = "none";
                }
            });

            // Show "Not Found" message
            let notFoundElem = document.getElementById("notFound");
            if(notFoundElem){
                notFoundElem.style.display = found ? "none" : "block";
            }
        });
    }

});
