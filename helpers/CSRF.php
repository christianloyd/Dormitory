<?php
/**
 * CSRF Protection Helper
 * /helpers/CSRF.php
 *
 * Provides Cross-Site Request Forgery (CSRF) protection for forms.
 * Add token to all forms and verify on submission.
 */

class CSRF {
    /**
     * Generate CSRF token if not exists
     *
     * @return string The CSRF token
     */
    public static function generateToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate CSRF token
     *
     * @param string $token The token to validate
     * @return bool True if valid, false otherwise
     */
    public static function validateToken($token) {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Get CSRF token as hidden input field
     *
     * @return string HTML hidden input field with CSRF token
     */
    public static function getTokenField() {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Verify CSRF token from POST request
     * Dies with error message if validation fails
     */
    public static function verifyRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (!self::validateToken($token)) {
                http_response_code(403);
                die('CSRF token validation failed. Please refresh the page and try again.');
            }
        }
    }

    /**
     * Verify token and return boolean instead of dying
     *
     * @return bool True if valid or not POST, false if invalid
     */
    public static function verify() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            return self::validateToken($token);
        }
        return true; // Not a POST request, no validation needed
    }

    /**
     * Get token for JavaScript/AJAX requests
     *
     * @return string The CSRF token
     */
    public static function getToken() {
        return self::generateToken();
    }
}
?>
