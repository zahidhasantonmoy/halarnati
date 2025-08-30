<?php
/**
 * Content Recommendation System
 */
class ContentRecommendation {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Get recommended entries based on user preferences
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getRecommendationsForUser($userId, $limit = 5) {
        // Get user's bookmarked categories
        $bookmarkedCategories = $this->db->fetchAll(
            "SELECT DISTINCT c.id, c.name
             FROM bookmarks b
             JOIN entries e ON b.entry_id = e.id
             JOIN categories c ON e.category_id = c.id
             WHERE b.user_id = ?",
            [$userId],
            "i"
        );
        
        // Get user's viewed entries categories
        $viewedCategories = $this->db->fetchAll(
            "SELECT DISTINCT c.id, c.name
             FROM entry_views ev
             JOIN entries e ON ev.entry_id = e.id
             JOIN categories c ON e.category_id = c.id
             WHERE ev.user_id = ?",
            [$userId],
            "i"
        );
        
        // Combine categories
        $preferredCategories = array_merge($bookmarkedCategories, $viewedCategories);
        $categoryIds = array_unique(array_column($preferredCategories, 'id'));
        
        // If we have preferred categories, recommend entries from those categories
        if (!empty($categoryIds)) {
            $placeholders = str_repeat('?,', count($categoryIds) - 1) . '?';
            $types = str_repeat('i', count($categoryIds));
            
            return $this->db->fetchAll(
                "SELECT e.*, c.name as category_name, u.username
                 FROM entries e
                 LEFT JOIN categories c ON e.category_id = c.id
                 LEFT JOIN users u ON e.user_id = u.id
                 WHERE e.category_id IN ($placeholders)
                 AND e.is_visible = 1
                 AND e.user_id != ?
                 ORDER BY e.view_count DESC, e.created_at DESC
                 LIMIT ?",
                array_merge($categoryIds, [$userId, $limit]),
                $types . "ii"
            );
        }
        
        // Fallback: recommend popular entries
        return $this->getPopularEntries($limit);
    }
    
    /**
     * Get popular entries for anonymous users or when no preferences
     * @param int $limit
     * @return array
     */
    public function getPopularEntries($limit = 5) {
        return $this->db->fetchAll(
            "SELECT e.*, c.name as category_name, u.username
             FROM entries e
             LEFT JOIN categories c ON e.category_id = c.id
             LEFT JOIN users u ON e.user_id = u.id
             WHERE e.is_visible = 1
             ORDER BY e.view_count DESC, e.created_at DESC
             LIMIT ?",
            [$limit],
            "i"
        );
    }
    
    /**
     * Get similar entries based on current entry
     * @param int $entryId
     * @param int $limit
     * @return array
     */
    public function getSimilarEntries($entryId, $limit = 5) {
        // Get the current entry's category
        $entry = $this->db->fetch(
            "SELECT category_id, type FROM entries WHERE id = ?",
            [$entryId],
            "i"
        );
        
        if (!$entry) {
            return [];
        }
        
        // Get similar entries from same category and type
        return $this->db->fetchAll(
            "SELECT e.*, c.name as category_name, u.username
             FROM entries e
             LEFT JOIN categories c ON e.category_id = c.id
             LEFT JOIN users u ON e.user_id = u.id
             WHERE e.id != ?
             AND e.category_id = ?
             AND e.type = ?
             AND e.is_visible = 1
             ORDER BY e.view_count DESC, e.created_at DESC
             LIMIT ?",
            [$entryId, $entry['category_id'], $entry['type'], $limit],
            "iissi"
        );
    }
    
    /**
     * Record entry view for recommendation algorithm
     * @param int $userId
     * @param int $entryId
     */
    public function recordView($userId, $entryId) {
        // Check if view already recorded
        $existing = $this->db->fetch(
            "SELECT id FROM entry_views WHERE user_id = ? AND entry_id = ?",
            [$userId, $entryId],
            "ii"
        );
        
        if (!$existing) {
            $this->db->insert(
                "INSERT INTO entry_views (user_id, entry_id) VALUES (?, ?)",
                [$userId, $entryId],
                "ii"
            );
        }
    }
}
?>