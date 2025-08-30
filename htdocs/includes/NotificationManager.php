<?php
/**
 * Notification Manager Class
 */
class NotificationManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Create a notification
     * @param int $userId
     * @param string $message
     * @param string $type
     * @param int $relatedId
     * @return bool
     */
    public function createNotification($userId, $message, $type = 'info', $relatedId = null) {
        $insertId = $this->db->insert(
            "INSERT INTO notifications (user_id, message, type, related_id) VALUES (?, ?, ?, ?)",
            [$userId, $message, $type, $relatedId],
            "issi"
        );
        
        return $insertId !== false;
    }
    
    /**
     * Get user's notifications
     * @param int $userId
     * @param int $limit
     * @param bool $unreadOnly
     * @return array
     */
    public function getUserNotifications($userId, $limit = 10, $unreadOnly = true) {
        $query = "SELECT * FROM notifications WHERE user_id = ?";
        $params = [$userId];
        $types = "i";
        
        if ($unreadOnly) {
            $query .= " AND is_read = 0";
        }
        
        $query .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        $types .= "i";
        
        return $this->db->fetchAll($query, $params, $types);
    }
    
    /**
     * Mark notification as read
     * @param int $notificationId
     * @param int $userId
     * @return bool
     */
    public function markAsRead($notificationId, $userId) {
        $affected = $this->db->update(
            "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?",
            [$notificationId, $userId],
            "ii"
        );
        
        return $affected !== false;
    }
    
    /**
     * Mark all notifications as read
     * @param int $userId
     * @return bool
     */
    public function markAllAsRead($userId) {
        $affected = $this->db->update(
            "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0",
            [$userId],
            "i"
        );
        
        return $affected !== false;
    }
    
    /**
     * Get unread notification count
     * @param int $userId
     * @return int
     */
    public function getUnreadCount($userId) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0",
            [$userId],
            "i"
        );
        
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Delete notification
     * @param int $notificationId
     * @param int $userId
     * @return bool
     */
    public function deleteNotification($notificationId, $userId) {
        $affected = $this->db->delete(
            "DELETE FROM notifications WHERE id = ? AND user_id = ?",
            [$notificationId, $userId],
            "ii"
        );
        
        return $affected !== false;
    }
}
?>