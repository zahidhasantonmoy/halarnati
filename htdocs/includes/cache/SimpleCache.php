<?php
/**
 * Simple File-based Caching System
 */
class SimpleCache {
    private static $cacheDir = __DIR__ . '/../../cache';
    
    public function __construct() {
        // Create cache directory if it doesn't exist
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
    }
    
    /**
     * Generate a cache key from input
     * @param string $key
     * @return string
     */
    private static function generateKey($key) {
        return md5($key);
    }
    
    /**
     * Get cached data
     * @param string $key
     * @param int $ttl Time to live in seconds (default 1 hour)
     * @return mixed|null
     */
    public static function get($key, $ttl = 3600) {
        $cacheFile = self::$cacheDir . '/' . self::generateKey($key) . '.cache';
        
        if (!file_exists($cacheFile)) {
            return null;
        }
        
        // Check if cache is expired
        if (time() - filemtime($cacheFile) > $ttl) {
            unlink($cacheFile); // Delete expired cache
            return null;
        }
        
        $data = unserialize(file_get_contents($cacheFile));
        return $data;
    }
    
    /**
     * Set cached data
     * @param string $key
     * @param mixed $data
     * @param int $ttl Time to live in seconds
     * @return bool
     */
    public static function set($key, $data, $ttl = 3600) {
        $cacheFile = self::$cacheDir . '/' . self::generateKey($key) . '.cache';
        return file_put_contents($cacheFile, serialize($data)) !== false;
    }
    
    /**
     * Delete cached data
     * @param string $key
     * @return bool
     */
    public static function delete($key) {
        $cacheFile = self::$cacheDir . '/' . self::generateKey($key) . '.cache';
        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }
        return true;
    }
    
    /**
     * Clear all cache
     * @return bool
     */
    public static function clear() {
        $files = glob(self::$cacheDir . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }
    
    /**
     * Get cache statistics
     * @return array
     */
    public static function getStats() {
        $files = glob(self::$cacheDir . '/*.cache');
        $totalSize = 0;
        foreach ($files as $file) {
            $totalSize += filesize($file);
        }
        
        return [
            'count' => count($files),
            'size' => $totalSize,
            'directory' => self::$cacheDir
        ];
    }
}
?>