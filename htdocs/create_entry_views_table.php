<?php
/**
 * Create Entry Views Table Script
 */
include 'config.php';

echo "Creating entry_views table...\n";

// Create entry_views table
try {
    $db->query("
        CREATE TABLE IF NOT EXISTS entry_views (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            entry_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (entry_id) REFERENCES entries(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_entry_view (user_id, entry_id)
        )
    ");
    echo "✓ Entry views table created or already exists\n";
} catch (Exception $e) {
    echo "✗ Error creating entry views table: " . $e->getMessage() . "\n";
}

echo "Entry views table setup completed.\n";
?>