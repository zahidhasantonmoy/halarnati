<?php
/**
 * Comment Manager Class
 */
class CommentManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Add a comment
     * @param int $entryId
     * @param int $userId
     * @param string $content
     * @param int $parentId
     * @return int|bool
     */
    public function addComment($entryId, $userId, $content, $parentId = null) {
        $content = htmlspecialchars($content);
        
        $insertId = $this->db->insert(
            "INSERT INTO comments (entry_id, user_id, content, parent_id) VALUES (?, ?, ?, ?)",
            [$entryId, $userId, $content, $parentId],
            "iisi"
        );
        
        return $insertId;
    }
    
    /**
     * Get comments for an entry
     * @param int $entryId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getComments($entryId, $limit = 10, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT c.id, c.content, c.created_at, c.parent_id,
                    u.username, u.avatar,
                    (SELECT COUNT(*) FROM comment_likes cl WHERE cl.comment_id = c.id) as like_count
             FROM comments c
             JOIN users u ON c.user_id = u.id
             WHERE c.entry_id = ?
             ORDER BY c.created_at ASC
             LIMIT ? OFFSET ?",
            [$entryId, $limit, $offset],
            "iii"
        );
    }
    
    /**
     * Get comment count for an entry
     * @param int $entryId
     * @return int
     */
    public function getCommentCount($entryId) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM comments WHERE entry_id = ?",
            [$entryId],
            "i"
        );
        
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Delete a comment
     * @param int $commentId
     * @param int $userId
     * @return bool
     */
    public function deleteComment($commentId, $userId) {
        // Check if user owns the comment or is admin
        $comment = $this->db->fetch(
            "SELECT user_id FROM comments WHERE id = ?",
            [$commentId],
            "i"
        );
        
        if (!$comment) {
            return false;
        }
        
        if ($comment['user_id'] != $userId && !$_SESSION['is_admin']) {
            return false;
        }
        
        $affected = $this->db->delete(
            "DELETE FROM comments WHERE id = ?",
            [$commentId],
            "i"
        );
        
        return $affected !== false;
    }
    
    /**
     * Like a comment
     * @param int $commentId
     * @param int $userId
     * @return bool
     */
    public function likeComment($commentId, $userId) {
        // Check if already liked
        $existing = $this->db->fetch(
            "SELECT id FROM comment_likes WHERE comment_id = ? AND user_id = ?",
            [$commentId, $userId],
            "ii"
        );
        
        if ($existing) {
            return true; // Already liked
        }
        
        $insertId = $this->db->insert(
            "INSERT INTO comment_likes (comment_id, user_id) VALUES (?, ?)",
            [$commentId, $userId],
            "ii"
        );
        
        return $insertId !== false;
    }
    
    /**
     * Unlike a comment
     * @param int $commentId
     * @param int $userId
     * @return bool
     */
    public function unlikeComment($commentId, $userId) {
        $affected = $this->db->delete(
            "DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?",
            [$commentId, $userId],
            "ii"
        );
        
        return $affected !== false;
    }
    
    /**
     * Check if user liked a comment
     * @param int $commentId
     * @param int $userId
     * @return bool
     */
    public function isCommentLiked($commentId, $userId) {
        $result = $this->db->fetch(
            "SELECT id FROM comment_likes WHERE comment_id = ? AND user_id = ?",
            [$commentId, $userId],
            "ii"
        );
        
        return $result !== null;
    }
    
    /**
     * Get like count for a comment
     * @param int $commentId
     * @return int
     */
    public function getCommentLikeCount($commentId) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM comment_likes WHERE comment_id = ?",
            [$commentId],
            "i"
        );
        
        return $result ? (int)$result['count'] : 0;
    }
}
?>