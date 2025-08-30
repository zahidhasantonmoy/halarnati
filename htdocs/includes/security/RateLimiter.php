<?php
/**
 * Rate Limiting Class
 */
class RateLimiter {
    private static $limits = [
        'login' => ['attempts' => 5, 'window' => 900], // 5 attempts per 15 minutes
        'registration' => ['attempts' => 3, 'window' => 3600], // 3 attempts per hour
        'password_reset' => ['attempts' => 3, 'window' => 3600], // 3 attempts per hour
    ];
    
    /**
     * Get a unique key for the rate limit
     * @param string $action
     * @param string $identifier
     * @return string
     */
    private static function getKey($action, $identifier) {
        return "rate_limit_{$action}_" . md5($identifier);
    }
    
    /**
     * Check if an action is allowed
     * @param string $action
     * @param string $identifier
     * @return bool
     */
    public static function isAllowed($action, $identifier) {
        if (!isset(self::$limits[$action])) {
            return true; // No limit defined
        }
        
        $key = self::getKey($action, $identifier);
        $limit = self::$limits[$action]['attempts'];
        $window = self::$limits[$action]['window'];
        
        // Get current count
        $attempts = (int)($_SESSION[$key]['attempts'] ?? 0);
        $lastAttempt = (int)($_SESSION[$key]['last_attempt'] ?? 0);
        
        // Reset if window has passed
        if (time() - $lastAttempt > $window) {
            self::reset($action, $identifier);
            return true;
        }
        
        // Check if limit exceeded
        return $attempts < $limit;
    }
    
    /**
     * Log an attempt
     * @param string $action
     * @param string $identifier
     */
    public static function logAttempt($action, $identifier) {
        if (!isset(self::$limits[$action])) {
            return;
        }
        
        $key = self::getKey($action, $identifier);
        $attempts = (int)($_SESSION[$key]['attempts'] ?? 0);
        
        $_SESSION[$key] = [
            'attempts' => $attempts + 1,
            'last_attempt' => time()
        ];
    }
    
    /**
     * Get remaining attempts
     * @param string $action
     * @param string $identifier
     * @return int
     */
    public static function getRemainingAttempts($action, $identifier) {
        if (!isset(self::$limits[$action])) {
            return 999; // No limit
        }
        
        $key = self::getKey($action, $identifier);
        $limit = self::$limits[$action]['attempts'];
        $attempts = (int)($_SESSION[$key]['attempts'] ?? 0);
        
        return max(0, $limit - $attempts);
    }
    
    /**
     * Reset rate limit for an action
     * @param string $action
     * @param string $identifier
     */
    public static function reset($action, $identifier) {
        $key = self::getKey($action, $identifier);
        unset($_SESSION[$key]);
    }
    
    /**
     * Get time until reset
     * @param string $action
     * @param string $identifier
     * @return int
     */
    public static function getTimeUntilReset($action, $identifier) {
        if (!isset(self::$limits[$action])) {
            return 0;
        }
        
        $key = self::getKey($action, $identifier);
        $window = self::$limits[$action]['window'];
        $lastAttempt = (int)($_SESSION[$key]['last_attempt'] ?? 0);
        
        $timePassed = time() - $lastAttempt;
        return max(0, $window - $timePassed);
    }
}
?>