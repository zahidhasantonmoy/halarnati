<?php
/**
 * Follow Manager Class
 */
class FollowManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Follow a user
     * @param int $followerId
     * @param int $followingId
     * @return bool
     */
    public function follow($followerId, $followingId) {
        // Can't follow yourself
        if ($followerId == $followingId) {
            return false;
        }
        
        // Check if already following
        $existing = $this->db->fetch(
            "SELECT id FROM follows WHERE follower_id = ? AND following_id = ?",
            [$followerId, $followingId],
            "ii"
        );
        
        if ($existing) {
            return true; // Already following
        }
        
        $insertId = $this->db->insert(
            "INSERT INTO follows (follower_id, following_id) VALUES (?, ?)",
            [$followerId, $followingId],
            "ii"
        );
        
        return $insertId !== false;
    }
    
    /**
     * Unfollow a user
     * @param int $followerId
     * @param int $followingId
     * @return bool
     */
    public function unfollow($followerId, $followingId) {
        $affected = $this->db->delete(
            "DELETE FROM follows WHERE follower_id = ? AND following_id = ?",
            [$followerId, $followingId],
            "ii"
        );
        
        return $affected !== false;
    }
    
    /**
     * Check if user is following another user
     * @param int $followerId
     * @param int $followingId
     * @return bool
     */
    public function isFollowing($followerId, $followingId) {
        $result = $this->db->fetch(
            "SELECT id FROM follows WHERE follower_id = ? AND following_id = ?",
            [$followerId, $followingId],
            "ii"
        );
        
        return $result !== null;
    }
    
    /**
     * Get followers count
     * @param int $userId
     * @return int
     */
    public function getFollowersCount($userId) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM follows WHERE following_id = ?",
            [$userId],
            "i"
        );
        
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Get following count
     * @param int $userId
     * @return int
     */
    public function getFollowingCount($userId) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM follows WHERE follower_id = ?",
            [$userId],
            "i"
        );
        
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Get followers
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getFollowers($userId, $limit = 10, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar
             FROM follows f
             JOIN users u ON f.follower_id = u.id
             WHERE f.following_id = ?
             ORDER BY f.created_at DESC
             LIMIT ? OFFSET ?",
            [$userId, $limit, $offset],
            "iii"
        );
    }
    
    /**
     * Get following
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getFollowing($userId, $limit = 10, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar
             FROM follows f
             JOIN users u ON f.following_id = u.id
             WHERE f.follower_id = ?
             ORDER BY f.created_at DESC
             LIMIT ? OFFSET ?",
            [$userId, $limit, $offset],
            "iii"
        );
    }
}
?>