<?php
/**
 * Rate Limiter for Login Protection
 * /helpers/RateLimiter.php
 *
 * Prevents brute force attacks by limiting login attempts.
 * Uses session-based tracking for simplicity.
 */

class RateLimiter {
    private $max_attempts = 5;
    private $lockout_time = 900; // 15 minutes in seconds
    private $attempt_window = 300; // 5 minutes in seconds

    /**
     * Check if the identifier has exceeded rate limit
     *
     * @param string $identifier Unique identifier (username, IP, etc.)
     * @throws Exception If rate limit exceeded
     * @return bool True if within limit
     */
    public function checkLimit($identifier) {
        $key = 'rate_limit_' . md5($identifier);

        // Initialize if not exists
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'count' => 0,
                'first_attempt' => time(),
                'locked_until' => 0
            ];
        }

        $data = $_SESSION[$key];

        // Check if currently locked out
        if ($data['locked_until'] > time()) {
            $remaining = $data['locked_until'] - time();
            $minutes = ceil($remaining / 60);
            throw new Exception("Too many failed login attempts. Please try again in $minutes minute(s).");
        }

        // Reset counter if attempt window has passed
        if (time() - $data['first_attempt'] > $this->attempt_window) {
            $_SESSION[$key] = [
                'count' => 0,
                'first_attempt' => time(),
                'locked_until' => 0
            ];
            return true;
        }

        // Check if exceeded max attempts
        if ($data['count'] >= $this->max_attempts) {
            $_SESSION[$key]['locked_until'] = time() + $this->lockout_time;
            throw new Exception("Too many failed login attempts. Account temporarily locked for 15 minutes.");
        }

        return true;
    }

    /**
     * Record a login attempt
     *
     * @param string $identifier Unique identifier
     * @param bool $success Whether the attempt was successful
     */
    public function recordAttempt($identifier, $success = false) {
        $key = 'rate_limit_' . md5($identifier);

        // Clear on success
        if ($success) {
            unset($_SESSION[$key]);
            return;
        }

        // Initialize if not exists
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'count' => 0,
                'first_attempt' => time(),
                'locked_until' => 0
            ];
        }

        // Increment failed attempt counter
        $_SESSION[$key]['count']++;
    }

    /**
     * Get remaining attempts before lockout
     *
     * @param string $identifier Unique identifier
     * @return int Number of remaining attempts
     */
    public function getRemainingAttempts($identifier) {
        $key = 'rate_limit_' . md5($identifier);

        if (!isset($_SESSION[$key])) {
            return $this->max_attempts;
        }

        $data = $_SESSION[$key];

        // If locked, return 0
        if ($data['locked_until'] > time()) {
            return 0;
        }

        // If attempt window passed, return max
        if (time() - $data['first_attempt'] > $this->attempt_window) {
            return $this->max_attempts;
        }

        return max(0, $this->max_attempts - $data['count']);
    }

    /**
     * Check if identifier is currently locked out
     *
     * @param string $identifier Unique identifier
     * @return bool True if locked, false otherwise
     */
    public function isLocked($identifier) {
        $key = 'rate_limit_' . md5($identifier);

        if (!isset($_SESSION[$key])) {
            return false;
        }

        return $_SESSION[$key]['locked_until'] > time();
    }

    /**
     * Manually unlock an identifier (for admin override)
     *
     * @param string $identifier Unique identifier
     */
    public function unlock($identifier) {
        $key = 'rate_limit_' . md5($identifier);
        unset($_SESSION[$key]);
    }

    /**
     * Set custom rate limit parameters
     *
     * @param int $max_attempts Maximum attempts allowed
     * @param int $lockout_time Lockout duration in seconds
     * @param int $attempt_window Time window for counting attempts in seconds
     */
    public function setLimits($max_attempts, $lockout_time, $attempt_window) {
        $this->max_attempts = intval($max_attempts);
        $this->lockout_time = intval($lockout_time);
        $this->attempt_window = intval($attempt_window);
    }
}
?>
