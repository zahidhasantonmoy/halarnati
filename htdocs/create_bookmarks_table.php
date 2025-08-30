<?php
/**
 * Create Bookmarks Table Script
 */
include 'config.php';

echo "Creating bookmarks table...\n";

// Create bookmarks table
try {
    $db->query("
        CREATE TABLE IF NOT EXISTS bookmarks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            entry_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (entry_id) REFERENCES entries(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_entry (user_id, entry_id)
        )
    ");
    echo "✓ Bookmarks table created or already exists\n";
} catch (Exception $e) {
    echo "✗ Error creating bookmarks table: " . $e->getMessage() . "\n";
}

echo "Bookmarks table setup completed.\n";
?>
