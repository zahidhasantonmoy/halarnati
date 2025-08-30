-- Database schema verification and fix script

-- Check if users table has the correct structure
DESCRIBE users;

-- Check if entries table has the correct structure
DESCRIBE entries;

-- Check if categories table has the correct structure
DESCRIBE categories;

-- Check if user_activity_logs table has the correct structure
DESCRIBE user_activity_logs;

-- Check if notifications table has the correct structure
DESCRIBE notifications;

-- Check if settings table has the correct structure
DESCRIBE settings;

-- Check if entry_categories table has the correct structure (for many-to-many relationship)
DESCRIBE entry_categories;

-- Sample query to verify data
SELECT * FROM users LIMIT 5;
SELECT * FROM entries LIMIT 5;
SELECT * FROM categories LIMIT 5;