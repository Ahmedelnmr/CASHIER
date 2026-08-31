<?php
/**
 * Validator Helper
 * Centralized validation for form data across all modules
 */

class Validator {
    private $errors = [];
    private $data;

    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * Get string length (multibyte safe)
     */
    private static function strLen($str) {
        if (function_exists('mb_strlen')) {
            return mb_strlen($str, 'UTF-8');
        }
        // Fallback: count UTF-8 characters
        return preg_match_all('/./us', $str, $matches);
    }

    /**
     * Require a field to be present and non-empty
     */
    public function required($field, $label = null) {
        $label = $label ?: $field;
        $value = $this->data[$field] ?? null;
        if ($value === null || (is_string($value) && trim($value) === '')) {
            $this->errors[$field] = "$label مطلوب";
        }
        return $this;
    }

    /**
     * Validate phone number - digits only, 7-15 characters
     */
    public function phone($field, $label = null, $required = false) {
        $label = $label ?: $field;
        $value = $this->data[$field] ?? '';

        if ($required && ($value === null || trim($value) === '')) {
            $this->errors[$field] = "$label مطلوب";
            return $this;
        }

        if ($value !== null && trim($value) !== '') {
            $trimmed = trim($value);
            if (!preg_match('/^[0-9]{7,15}$/', $trimmed)) {
                $this->errors[$field] = "$label يجب أن يحتوي على أرقام فقط (7-15 رقم)";
            }
        }
        return $this;
    }

    /**
     * Validate name - supports Arabic and English, spaces allowed between words
     */
    public function name($field, $label = null, $required = true) {
        $label = $label ?: $field;
        $value = $this->data[$field] ?? '';

        if ($required && ($value === null || trim($value) === '')) {
            $this->errors[$field] = "$label مطلوب";
            return $this;
        }

        if ($value !== null && trim($value) !== '') {
            $trimmed = trim($value);

            // Check minimum length
            $len = self::strLen($trimmed);
            if ($len < 2) {
                $this->errors[$field] = "$label يجب أن يكون حرفين على الأقل";
                return $this;
            }

            // Check maximum length
            if ($len > 100) {
                $this->errors[$field] = "$label يجب أن لا يتجاوز 100 حرف";
                return $this;
            }

            // Reject control characters (allow printable + whitespace)
            if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $trimmed)) {
                $this->errors[$field] = "$label يحتوي على أحرف غير صالحة";
                return $this;
            }

            // Reject multiple consecutive spaces
            if (preg_match('/\s{2,}/', $trimmed)) {
                $this->errors[$field] = "$label يحتوي على مسافات متكررة";
                return $this;
            }

            // Allow Arabic, English, spaces, and common name characters
            if (!preg_match('/^[\p{Arabic}\p{Latin}\s\.\-\']{2,100}$/u', $trimmed)) {
                $this->errors[$field] = "$label يحتوي على أحرف غير مدعومة";
                return $this;
            }
        }
        return $this;
    }

    /**
     * Validate email address
     */
    public function email($field, $label = null, $required = false) {
        $label = $label ?: $field;
        $value = $this->data[$field] ?? '';

        if ($required && ($value === null || trim($value) === '')) {
            $this->errors[$field] = "$label مطلوب";
            return $this;
        }

        if ($value !== null && trim($value) !== '') {
            $trimmed = trim($value);

            // Check length
            if (self::strLen($trimmed) > 254) {
                $this->errors[$field] = "$label طويل جداً (الحد الأقصى 254 حرف)";
                return $this;
            }

            // Reject whitespace
            if (preg_match('/\s/', $trimmed)) {
                $this->errors[$field] = "$label يجب أن لا يحتوي على مسافات";
                return $this;
            }

            if (!filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field] = "$label غير صالح";
                return $this;
            }
        }
        return $this;
    }

    /**
     * Validate numeric field
     */
    public function numeric($field, $label = null) {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !is_numeric($this->data[$field])) {
            $this->errors[$field] = "$label يجب أن يكون رقم";
        }
        return $this;
    }

    /**
     * Validate minimum value
     */
    public function min($field, $min, $label = null) {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && is_numeric($this->data[$field]) && $this->data[$field] < $min) {
            $this->errors[$field] = "$label يجب أن يكون $min على الأقل";
        }
        return $this;
    }

    /**
     * Validate maximum value
     */
    public function max($field, $max, $label = null) {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && is_numeric($this->data[$field]) && $this->data[$field] > $max) {
            $this->errors[$field] = "$label يجب أن لا يتجاوز $max";
        }
        return $this;
    }

    /**
     * Validate non-negative number
     */
    public function nonNegative($field, $label = null) {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && is_numeric($this->data[$field]) && $this->data[$field] < 0) {
            $this->errors[$field] = "$label يجب أن يكون أكبر من أو يساوي صفر";
        }
        return $this;
    }

    /**
     * Validate max length (multibyte safe)
     */
    public function maxLength($field, $max, $label = null) {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && is_string($this->data[$field])) {
            $length = self::strLen($this->data[$field]);
            if ($length > $max) {
                $this->errors[$field] = "$label يجب أن لا يتجاوز $max حرف";
            }
        }
        return $this;
    }

    /**
     * Validate min length (multibyte safe)
     */
    public function minLength($field, $min, $label = null) {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && is_string($this->data[$field]) && trim($this->data[$field]) !== '') {
            $length = self::strLen(trim($this->data[$field]));
            if ($length < $min) {
                $this->errors[$field] = "$label يجب أن يكون $min أحرف على الأقل";
            }
        }
        return $this;
    }

    /**
     * Validate field contains only digits
     */
    public function digitsOnly($field, $label = null, $required = false) {
        $label = $label ?: $field;
        $value = $this->data[$field] ?? '';

        if ($required && ($value === null || trim($value) === '')) {
            $this->errors[$field] = "$label مطلوب";
            return $this;
        }

        if ($value !== null && trim($value) !== '') {
            if (!preg_match('/^[0-9]+$/', trim($value))) {
                $this->errors[$field] = "$label يجب أن يحتوي على أرقام فقط";
            }
        }
        return $this;
    }

    /**
     * Validate field matches a specific pattern
     */
    public function pattern($field, $pattern, $message, $label = null) {
        $label = $label ?: $field;
        $value = $this->data[$field] ?? '';
        if ($value !== null && trim($value) !== '') {
            if (!preg_match($pattern, trim($value))) {
                $this->errors[$field] = $message;
            }
        }
        return $this;
    }

    /**
     * Check if validation passed
     */
    public function passes() {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     */
    public function fails() {
        return !empty($this->errors);
    }

    /**
     * Get validation errors
     */
    public function errors() {
        return $this->errors;
    }

    /**
     * Get first error message
     */
    public function firstError() {
        return !empty($this->errors) ? reset($this->errors) : null;
    }

    /**
     * Get sanitized value
     */
    public static function sanitize($value) {
        if (is_string($value)) {
            return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        }
        return $value;
    }

    /**
     * Get sanitized input array
     */
    public static function sanitizeAll($data) {
        $clean = [];
        foreach ($data as $key => $value) {
            $clean[$key] = self::sanitize($value);
        }
        return $clean;
    }

    /**
     * Trim all string values in data array
     */
    public static function trimAll($data) {
        $trimmed = [];
        foreach ($data as $key => $value) {
            $trimmed[$key] = is_string($value) ? trim($value) : $value;
        }
        return $trimmed;
    }
}
