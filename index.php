<?php
/**
 * Application Entry Point
 * Redirects to appropriate page based on authentication status
 */

require_once 'db.php';

// Redirect based on login status
if (Session::isLoggedIn()) {
    header('Location: modules/dashboard/');
} else {
    header('Location: login.php');
}
exit;
?>
