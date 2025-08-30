<?php
/**
 * Database Schema Update Script
 * Adds new columns for enhanced features
 */
include 'config.php';

echo "Updating database schema...\n";

// Add theme column to users table
try {
    $db->query("ALTER TABLE users ADD COLUMN theme VARCHAR(10) DEFAULT 'light'");
    echo "✓ Added theme column to users table\n";
} catch (Exception $e) {
    echo "ℹ Theme column already exists or error: " . $e->getMessage() . "\n";
}

// Add remember_token column to users table
try {
    $db->query("ALTER TABLE users ADD COLUMN remember_token VARCHAR(64) NULL");
    echo "✓ Added remember_token column to users table\n";
} catch (Exception $e) {
    echo "ℹ Remember token column already exists or error: " . $e->getMessage() . "\n";
}

// Add bio column to users table
try {
    $db->query("ALTER TABLE users ADD COLUMN bio TEXT NULL");
    echo "✓ Added bio column to users table\n";
} catch (Exception $e) {
    echo "ℹ Bio column already exists or error: " . $e->getMessage() . "\n";
}

// Add social media columns to users table
try {
    $db->query("ALTER TABLE users ADD COLUMN twitter VARCHAR(100) NULL");
    echo "✓ Added twitter column to users table\n";
} catch (Exception $e) {
    echo "ℹ Twitter column already exists or error: " . $e->getMessage() . "\n";
}

try {
    $db->query("ALTER TABLE users ADD COLUMN facebook VARCHAR(100) NULL");
    echo "✓ Added facebook column to users table\n";
} catch (Exception $e) {
    echo "ℹ Facebook column already exists or error: " . $e->getMessage() . "\n";
}

try {
    $db->query("ALTER TABLE users ADD COLUMN linkedin VARCHAR(100) NULL");
    echo "✓ Added linkedin column to users table\n";
} catch (Exception $e) {
    echo "ℹ LinkedIn column already exists or error: " . $e->getMessage() . "\n";
}

// Add cover photo column to users table
try {
    $db->query("ALTER TABLE users ADD COLUMN cover_photo VARCHAR(255) NULL");
    echo "✓ Added cover_photo column to users table\n";
} catch (Exception $e) {
    echo "ℹ Cover photo column already exists or error: " . $e->getMessage() . "\n";
}

// Add indexes for better performance
try {
    $db->query("CREATE INDEX idx_entries_slug ON entries(slug)");
    echo "✓ Added index on entries.slug\n";
} catch (Exception $e) {
    echo "ℹ Index on entries.slug already exists or error: " . $e->getMessage() . "\n";
}

try {
    $db->query("CREATE INDEX idx_entries_user_id ON entries(user_id)");
    echo "✓ Added index on entries.user_id\n";
} catch (Exception $e) {
    echo "ℹ Index on entries.user_id already exists or error: " . $e->getMessage() . "\n";
}

try {
    $db->query("CREATE INDEX idx_entries_created_at ON entries(created_at)");
    echo "✓ Added index on entries.created_at\n";
} catch (Exception $e) {
    echo "ℹ Index on entries.created_at already exists or error: " . $e->getMessage() . "\n";
}

try {
    $db->query("CREATE INDEX idx_users_username ON users(username)");
    echo "✓ Added index on users.username\n";
} catch (Exception $e) {
    echo "ℹ Index on users.username already exists or error: " . $e->getMessage() . "\n";
}

echo "Database schema update completed.\n";
?>
