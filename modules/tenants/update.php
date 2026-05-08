<?php
/**
 * Secure Tenant Update Handler
 * Fixed SQL injection and added secure file upload
 */
require_once '../../includes/auth_check.php';
require_once __DIR__ . '/../../helpers/TenantAssignments.php';

try {
    // Verify CSRF token
    CSRF::verifyRequest();

    // Validate tenant_id is provided
    if (!isset($_POST['edit_tenant_id'])) {
        throw new Exception("Tenant ID is required.");
    }

    $tenant_id = intval($_POST['edit_tenant_id']);

    TenantAssignments::updateTenant($conn, $tenant_id, $_POST, $_FILES);

    Session::setMessage("Tenant updated successfully!", "success");
    header("Location: index.php");
    exit();

} catch (Exception $e) {
    Session::setMessage($e->getMessage(), "danger");
    header("Location: index.php");
    exit();
}
?>
