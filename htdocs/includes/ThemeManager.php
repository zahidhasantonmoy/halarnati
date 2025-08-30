<?php
/**
 * Theme Manager Class
 */
class ThemeManager {
    const LIGHT_THEME = 'light';
    const DARK_THEME = 'dark';
    
    /**
     * Get user's preferred theme
     * @param int $userId
     * @return string
     */
    public static function getUserTheme($userId = null) {
        // If user is logged in, get their preference from database
        if ($userId) {
            global $db;
            $result = $db->fetch("SELECT theme FROM users WHERE id = ?", [$userId], "i");
            if ($result && !empty($result['theme'])) {
                return $result['theme'];
            }
        }
        
        // Check for theme cookie
        if (isset($_COOKIE['theme'])) {
            $theme = $_COOKIE['theme'];
            if (in_array($theme, [self::LIGHT_THEME, self::DARK_THEME])) {
                return $theme;
            }
        }
        
        // Default to light theme
        return self::LIGHT_THEME;
    }
    
    /**
     * Set user's theme preference
     * @param string $theme
     * @param int $userId
     */
    public static function setUserTheme($theme, $userId = null) {
        if (!in_array($theme, [self::LIGHT_THEME, self::DARK_THEME])) {
            $theme = self::LIGHT_THEME;
        }
        
        // Set cookie
        setcookie('theme', $theme, time() + (30 * 24 * 60 * 60), '/', '', true, true);
        
        // If user is logged in, save to database
        if ($userId) {
            global $db;
            $db->update("UPDATE users SET theme = ? WHERE id = ?", [$theme, $userId], "si");
        }
    }
    
    /**
     * Get CSS file for current theme
     * @return string
     */
    public static function getThemeCSS() {
        $theme = self::getUserTheme($_SESSION['user_id'] ?? null);
        return $theme === self::DARK_THEME ? 'dark_style.css' : 'style.css';
    }
    
    /**
     * Toggle between themes
     * @return string
     */
    public static function toggleTheme() {
        $currentTheme = self::getUserTheme($_SESSION['user_id'] ?? null);
        $newTheme = $currentTheme === self::DARK_THEME ? self::LIGHT_THEME : self::DARK_THEME;
        self::setUserTheme($newTheme, $_SESSION['user_id'] ?? null);
        return $newTheme;
    }
}
?>