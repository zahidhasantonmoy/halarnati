<?php
/**
 * Create Follows Table Script
 */
include 'config.php';

echo "Creating follows table...\n";

// Create follows table
try {
    $db->query("
        CREATE TABLE IF NOT EXISTS follows (
            id INT AUTO_INCREMENT PRIMARY KEY,
            follower_id INT NOT NULL,
            following_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_follow (follower_id, following_id)
        )
    ");
    echo "✓ Follows table created or already exists\n";
} catch (Exception $e) {
    echo "✗ Error creating follows table: " . $e->getMessage() . "\n";
}

echo "Follows table setup completed.\n";
?>
