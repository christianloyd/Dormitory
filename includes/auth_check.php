<?php
/**
 * Authentication Check Include
 * Include this at the top of every protected page instead of manual session checks
 *
 * Usage:
 * require_once 'includes/auth_check.php';
 */

// Load db.php if not already loaded (includes all helpers and session init)
if (!defined('DB_LOADED')) {
    require_once __DIR__ . '/../db.php';
    define('DB_LOADED', true);
}

// Require authentication - redirect to login if not authenticated
Session::requireAuth();
?>
