<?php
/**
 * Tenants Module - Delete Tenant (Soft Delete)
 * Path: /modules/tenants/delete.php
 */
require_once '../../includes/auth_check.php';

if (isset($_GET['id'])) {
    $tenant_id = intval($_GET['id']);

    // Soft delete — set status = 'Inactive'
    $sql = "UPDATE tenants SET status='Inactive' WHERE tenant_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $tenant_id);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
    exit();
}
echo "invalid";
?>
