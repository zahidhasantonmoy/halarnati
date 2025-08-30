<?php
/**
 * Complete Database Setup Script
 * Runs all database update scripts in order
 */
echo "Running complete database setup...\n\n";

// Run database updates
echo "1. Running database schema updates...\n";
include 'database_update.php';

echo "\n2. Creating bookmarks table...\n";
include 'create_bookmarks_table.php';

echo "\n3. Creating notifications table...\n";
include 'create_notifications_table.php';

echo "\n4. Creating follows table...\n";
include 'create_follows_table.php';

echo "\n5. Creating comments tables...\n";
include 'create_comments_tables.php';

echo "\n\nDatabase setup completed successfully!\n";
echo "You may need to run these scripts on your production database as well.\n";
?>
