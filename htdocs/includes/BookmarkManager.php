<?php
/**
 * Bookmark Manager Class
 */
class BookmarkManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Add bookmark
     * @param int $userId
     * @param int $entryId
     * @return bool
     */
    public function addBookmark($userId, $entryId) {
        // Check if bookmark already exists
        $existing = $this->db->fetch(
            "SELECT id FROM bookmarks WHERE user_id = ? AND entry_id = ?", 
            [$userId, $entryId], 
            "ii"
        );
        
        if ($existing) {
            return true; // Already bookmarked
        }
        
        $insertId = $this->db->insert(
            "INSERT INTO bookmarks (user_id, entry_id) VALUES (?, ?)",
            [$userId, $entryId],
            "ii"
        );
        
        return $insertId !== false;
    }
    
    /**
     * Remove bookmark
     * @param int $userId
     * @param int $entryId
     * @return bool
     */
    public function removeBookmark($userId, $entryId) {
        $affected = $this->db->delete(
            "DELETE FROM bookmarks WHERE user_id = ? AND entry_id = ?",
            [$userId, $entryId],
            "ii"
        );
        
        return $affected !== false;
    }
    
    /**
     * Check if entry is bookmarked by user
     * @param int $userId
     * @param int $entryId
     * @return bool
     */
    public function isBookmarked($userId, $entryId) {
        $result = $this->db->fetch(
            "SELECT id FROM bookmarks WHERE user_id = ? AND entry_id = ?",
            [$userId, $entryId],
            "ii"
        );
        
        return $result !== null;
    }
    
    /**
     * Get user's bookmarks
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getUserBookmarks($userId, $limit = 10, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT b.id as bookmark_id, e.id, e.title, e.type, e.slug, e.created_at, e.view_count
             FROM bookmarks b
             JOIN entries e ON b.entry_id = e.id
             WHERE b.user_id = ?
             ORDER BY b.created_at DESC
             LIMIT ? OFFSET ?",
            [$userId, $limit, $offset],
            "iii"
        );
    }
    
    /**
     * Get bookmark count for an entry
     * @param int $entryId
     * @return int
     */
    public function getBookmarkCount($entryId) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM bookmarks WHERE entry_id = ?",
            [$entryId],
            "i"
        );
        
        return $result ? (int)$result['count'] : 0;
    }
}
?>