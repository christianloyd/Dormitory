<?php
/**
 * Secure Logout
 * Uses Session helper to properly destroy session and clear cookies
 */
require_once __DIR__ . '/../config/db.php';

// Ensure user is logged in before logging out
if (Session::isLoggedIn()) {
    Session::destroy();
}

// Redirect to login page using absolute path
header("Location: " . BASE_PATH . "/auth/login.php");
exit;
?>
