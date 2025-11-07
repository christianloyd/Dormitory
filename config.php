<?php
/**
 * Configuration Loader
 * Loads environment variables from .env file
 * This keeps sensitive credentials separate from code
 */

function loadEnv($filePath = '.env') {
    if (!file_exists($filePath)) {
        die("Error: .env file not found. Please create one from .env.example");
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                $value = $matches[2];
            }

            // Set as constant if not already defined
            if (!defined($key)) {
                define($key, $value);
            }

            // Also set in $_ENV for compatibility
            $_ENV[$key] = $value;
        }
    }
}

// Load environment variables
loadEnv(__DIR__ . '/.env');

/**
 * Helper function to get environment variable
 *
 * @param string $key The environment variable key
 * @param mixed $default Default value if key not found
 * @return mixed The environment value or default
 */
function env($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

/**
 * Convert string to boolean
 *
 * @param string $value
 * @return bool
 */
function envBool($value) {
    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
}
?>
