<?php
/**
 * Create Comments Tables Script
 */
include 'config.php';

echo "Creating comments tables...\n";

// Create comments table
try {
    $db->query("
        CREATE TABLE IF NOT EXISTS comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            entry_id INT NOT NULL,
            user_id INT NOT NULL,
            content TEXT NOT NULL,
            parent_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (entry_id) REFERENCES entries(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
        )
    ");
    echo "✓ Comments table created or already exists\n";
} catch (Exception $e) {
    echo "✗ Error creating comments table: " . $e->getMessage() . "\n";
}

// Create comment_likes table
try {
    $db->query("
        CREATE TABLE IF NOT EXISTS comment_likes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            comment_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_comment_like (user_id, comment_id)
        )
    ");
    echo "✓ Comment likes table created or already exists\n";
} catch (Exception $e) {
    echo "✗ Error creating comment likes table: " . $e->getMessage() . "\n";
}

echo "Comments tables setup completed.\n";
?>
