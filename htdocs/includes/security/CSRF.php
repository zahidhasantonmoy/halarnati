<?php
/**
 * CSRF Protection Class
 */
class CSRF {
    /**
     * Generate a CSRF token
     * @return string
     */
    public static function generateToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate a CSRF token
     * @param string $token
     * @return bool
     */
    public static function validateToken($token) {
        if (!isset($_SESSION['csrf_token']) || !isset($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Generate a hidden input field with CSRF token
     * @return string
     */
    public static function csrfField() {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Verify CSRF token from POST request
     * @return bool
     */
    public static function verifyRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (!self::validateToken($token)) {
                // Log the failed attempt
                error_log("CSRF token validation failed for request from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown'));
                return false;
            }
            return true;
        }
        return true; // Non-POST requests don't need CSRF validation
    }
}
?>