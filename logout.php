<?php
/**
 * Secure Logout
 * Uses Session helper to properly destroy session and clear cookies
 */
require_once 'db.php';

// Ensure user is logged in before logging out
if (Session::isLoggedIn()) {
    Session::destroy();
}

// Redirect to login page
header("Location: login.php");
exit;
?>
