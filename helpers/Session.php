<?php
/**
 * Secure Session Management Helper
 * /helpers/Session.php
 *
 * Provides secure session handling with timeout, regeneration,
 * and proper cookie security settings.
 */

class Session {
    private static $timeout = 1800; // 30 minutes
    private static $regenerate_interval = 600; // 10 minutes

    /**
     * Initialize secure session with proper settings
     */
    public static function init() {
        // Only start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session parameters before starting
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', 0); // Set to 1 when using HTTPS in production
            ini_set('session.cookie_samesite', 'Strict');

            session_start();
        }

        // Check for session timeout
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > self::$timeout) {
                self::destroy();
                $basePath = self::getBasePath();
                header('Location: ' . $basePath . '/login.php?timeout=1');
                exit;
            }
        }

        $_SESSION['last_activity'] = time();

        // Regenerate session ID periodically to prevent fixation attacks
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > self::$regenerate_interval) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }

    /**
     * Login user and create secure session
     *
     * @param string $username The username to store in session
     */
    public static function login($username) {
        // Regenerate session ID on login to prevent session fixation
        session_regenerate_id(true);

        $_SESSION['admin'] = $username;
        $_SESSION['created'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    }

    /**
     * Destroy session completely
     */
    public static function destroy() {
        $_SESSION = array();

        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
    }

    /**
     * Check if user is logged in
     *
     * @return bool True if logged in, false otherwise
     */
    public static function isLoggedIn() {
        return isset($_SESSION['admin']);
    }

    /**
     * Require authentication - redirect to login if not authenticated
     */
    public static function requireAuth() {
        if (!self::isLoggedIn()) {
            $basePath = self::getBasePath();
            header('Location: ' . $basePath . '/login.php');
            exit;
        }
    }

    /**
     * Get current user
     *
     * @return string|null Username or null if not logged in
     */
    public static function getUser() {
        return $_SESSION['admin'] ?? null;
    }

    /**
     * Set flash message
     *
     * @param string $message The message to display
     * @param string $type Message type (success, danger, warning, info)
     */
    public static function setMessage($message, $type = 'info') {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $type;
    }

    /**
     * Get and clear flash message
     *
     * @return array|null Array with 'message' and 'type' or null
     */
    public static function getMessage() {
        if (isset($_SESSION['message'])) {
            $message = [
                'message' => $_SESSION['message'],
                'type' => $_SESSION['message_type'] ?? 'info'
            ];
            unset($_SESSION['message'], $_SESSION['message_type']);
            return $message;
        }
        return null;
    }

    /**
     * Get base path for the application
     * Calculates the web root path dynamically
     *
     * @return string Base path (e.g., /dorm_system)
     */
    private static function getBasePath() {
        // Use BASE_PATH constant if defined
        if (defined('BASE_PATH')) {
            return BASE_PATH;
        }

        // Otherwise calculate it dynamically
        $script_name = dirname($_SERVER['SCRIPT_NAME']);
        $base_path = str_replace('\\', '/', $script_name);

        // Remove /modules/* from path if present
        if (strpos($base_path, '/modules/') !== false) {
            $base_path = substr($base_path, 0, strpos($base_path, '/modules'));
        }

        // Remove /includes from path if present
        if (strpos($base_path, '/includes') !== false) {
            $base_path = substr($base_path, 0, strpos($base_path, '/includes'));
        }

        return rtrim($base_path, '/');
    }
}
?>
