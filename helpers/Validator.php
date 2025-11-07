<?php
/**
 * Input Validation Helper
 * /helpers/Validator.php
 *
 * Provides common validation functions for user input.
 * Helps ensure data integrity and security.
 */

class Validator {
    /**
     * Validate Philippine phone number (09XXXXXXXXX format)
     *
     * @param string $phone Phone number to validate
     * @return bool True if valid, false otherwise
     */
    public static function isValidPhoneNumber($phone) {
        // Remove spaces and dashes
        $phone = preg_replace('/[\s\-]/', '', $phone);

        // Check if matches Philippine mobile format: 09XXXXXXXXX (11 digits)
        return preg_match('/^09[0-9]{9}$/', $phone) === 1;
    }

    /**
     * Validate email address
     *
     * @param string $email Email to validate
     * @return bool True if valid, false otherwise
     */
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate date format (YYYY-MM-DD)
     *
     * @param string $date Date to validate
     * @return bool True if valid, false otherwise
     */
    public static function isValidDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Validate that a string is not empty
     *
     * @param string $value Value to check
     * @return bool True if not empty, false otherwise
     */
    public static function isNotEmpty($value) {
        return !empty(trim($value));
    }

    /**
     * Validate string length
     *
     * @param string $value Value to check
     * @param int $min Minimum length
     * @param int $max Maximum length
     * @return bool True if within range, false otherwise
     */
    public static function isValidLength($value, $min = 0, $max = PHP_INT_MAX) {
        $length = mb_strlen($value);
        return $length >= $min && $length <= $max;
    }

    /**
     * Validate that value is a positive number
     *
     * @param mixed $value Value to check
     * @return bool True if positive number, false otherwise
     */
    public static function isPositiveNumber($value) {
        return is_numeric($value) && $value > 0;
    }

    /**
     * Validate that value is a non-negative number (including zero)
     *
     * @param mixed $value Value to check
     * @return bool True if non-negative number, false otherwise
     */
    public static function isNonNegativeNumber($value) {
        return is_numeric($value) && $value >= 0;
    }

    /**
     * Validate integer
     *
     * @param mixed $value Value to check
     * @return bool True if integer, false otherwise
     */
    public static function isInteger($value) {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Validate that value is within a range
     *
     * @param numeric $value Value to check
     * @param numeric $min Minimum value
     * @param numeric $max Maximum value
     * @return bool True if in range, false otherwise
     */
    public static function isInRange($value, $min, $max) {
        return is_numeric($value) && $value >= $min && $value <= $max;
    }

    /**
     * Validate room number format (letters and numbers, e.g., "101", "A-201")
     *
     * @param string $room_number Room number to validate
     * @return bool True if valid, false otherwise
     */
    public static function isValidRoomNumber($room_number) {
        // Allow letters, numbers, and hyphens, length 1-20
        return preg_match('/^[A-Za-z0-9\-]{1,20}$/', $room_number) === 1;
    }

    /**
     * Sanitize string for safe output
     *
     * @param string $value Value to sanitize
     * @return string Sanitized value
     */
    public static function sanitize($value) {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate that value is in allowed list
     *
     * @param mixed $value Value to check
     * @param array $allowed_values Array of allowed values
     * @return bool True if in list, false otherwise
     */
    public static function isInList($value, $allowed_values) {
        return in_array($value, $allowed_values, true);
    }

    /**
     * Validate password strength
     *
     * @param string $password Password to validate
     * @param int $min_length Minimum password length (default: 8)
     * @return array Array with 'valid' (bool) and 'errors' (array)
     */
    public static function isValidPassword($password, $min_length = 8) {
        $errors = [];

        if (strlen($password) < $min_length) {
            $errors[] = "Password must be at least $min_length characters long";
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate multiple fields at once
     *
     * @param array $data Associative array of field => value
     * @param array $rules Associative array of field => validation_rule
     * @return array Array with 'valid' (bool) and 'errors' (array)
     *
     * Example:
     * $rules = [
     *     'name' => 'required|length:3,100',
     *     'phone' => 'required|phone',
     *     'email' => 'email'
     * ];
     */
    public static function validateMultiple($data, $rules) {
        $errors = [];

        foreach ($rules as $field => $rule_string) {
            $value = $data[$field] ?? '';
            $rules_array = explode('|', $rule_string);

            foreach ($rules_array as $rule) {
                $rule_parts = explode(':', $rule);
                $rule_name = $rule_parts[0];
                $rule_params = isset($rule_parts[1]) ? explode(',', $rule_parts[1]) : [];

                switch ($rule_name) {
                    case 'required':
                        if (!self::isNotEmpty($value)) {
                            $errors[$field][] = ucfirst($field) . " is required";
                        }
                        break;

                    case 'length':
                        $min = isset($rule_params[0]) ? intval($rule_params[0]) : 0;
                        $max = isset($rule_params[1]) ? intval($rule_params[1]) : PHP_INT_MAX;
                        if (!self::isValidLength($value, $min, $max)) {
                            $errors[$field][] = ucfirst($field) . " must be between $min and $max characters";
                        }
                        break;

                    case 'phone':
                        if (!empty($value) && !self::isValidPhoneNumber($value)) {
                            $errors[$field][] = "Invalid phone number format";
                        }
                        break;

                    case 'email':
                        if (!empty($value) && !self::isValidEmail($value)) {
                            $errors[$field][] = "Invalid email format";
                        }
                        break;

                    case 'date':
                        if (!empty($value) && !self::isValidDate($value)) {
                            $errors[$field][] = "Invalid date format";
                        }
                        break;

                    case 'numeric':
                        if (!empty($value) && !is_numeric($value)) {
                            $errors[$field][] = ucfirst($field) . " must be a number";
                        }
                        break;

                    case 'positive':
                        if (!empty($value) && !self::isPositiveNumber($value)) {
                            $errors[$field][] = ucfirst($field) . " must be a positive number";
                        }
                        break;
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
?>
