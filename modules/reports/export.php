<?php
if (session_status() === PHP_SESSION_NONE) {
    
}
?>

<div style="padding:20px;">
    <h2>📷 Image File Exporting</h2>
    <p>Select a report section below and export it as an image (PNG, JPG, JPEG).</p>

    <!-- Dropdown for selecting which section to capture -->
    <label for="reportSelect">Choose Report:</label>
    <select id="reportSelect" style="margin:10px 0; padding:5px;">
        <option value="reportTable">Billing Summary</option>
        <option value="outstandingTable">Outstanding Report</option>
        <option value="collectionTable">Collections Report</option>
        <option value="perTenantTable">Per Tenant Report</option>
    </select>

    <br>
    <!-- File type -->
    <label for="imgFormat">File Format:</label>
    <select id="imgFormat" style="margin:10px 0; padding:5px;">
        <option value="png">PNG</option>
        <option value="jpeg">JPEG</option>
        <option value="jpg">JPG</option>
    </select>

    <br>
    <!-- Export Button -->
    <button onclick="exportSelectedReport()" style="padding:10px 15px; background:#5A7D7C; color:white; border:none; border-radius:6px;">
        ⬇ Export as Image
    </button>
</div>

<!-- Load html2canvas -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
function exportSelectedReport() {
    let selectedId = document.getElementById("reportSelect").value;
    let format = document.getElementById("imgFormat").value;
    let target = document.getElementById(selectedId);

    if (!target) {
        alert("⚠ Selected report section not found on this page.");
        return;
    }

    // Scroll to section to make sure it's visible
    target.scrollIntoView();

    // Capture with html2canvas
    html2canvas(target, { scale: 2, useCORS: true }).then(canvas => {
        let link = document.createElement("a");
        link.download = selectedId + "." + format;
        link.href = canvas.toDataURL("image/" + format);
        link.click();
    }).catch(err => {
        console.error("Error exporting:", err);
        alert("❌ Failed to export image.");
    });
}
</script>
