<?php
/**
 * Create Notifications Table Script
 */
include 'config.php';

echo "Creating notifications table...\n";

// Create notifications table
try {
    $db->query("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
            related_id INT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "✓ Notifications table created or already exists\n";
} catch (Exception $e) {
    echo "✗ Error creating notifications table: " . $e->getMessage() . "\n";
}

echo "Notifications table setup completed.\n";
?>
