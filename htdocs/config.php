<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Error Logging Configuration
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log'); // Log errors to php_error.log in the same directory as config.php
ini_set('display_errors', 0); // Disable displaying errors on screen for production
error_reporting(E_ALL); // Keep error reporting enabled for logging

// Database connection parameters
$host = 'sql203.infinityfree.com';
$user = 'if0_37868453';
$pass = 'Yho7V4gkz6bP1';
$db_name = 'if0_37868453_halarnati'; // Renamed to avoid conflict with $db object
$port = 3306; // Default MySQL port

// Include the Database class
require_once __DIR__ . '/includes/Database.php';

// Instantiate the Database class
$db = new Database($host, $user, $pass, $db_name, $port);

// Replace global $conn with global $db in functions
// log_activity, get_setting, set_setting will now use $db->getConnection() or $db methods directly.

if (!function_exists('log_activity')) {
/**
 * Logs user activity to the database.
 *
 * @param int|null $user_id The ID of the user performing the action. Null if action is not tied to a specific user (e.g., failed login).
 * @param string $action A short description of the action (e.g., "User Login", "Entry Created").
 * @param string|null $details More detailed information about the action.
 * @param string|null $ip_address The IP address from which the action originated.
 */
function log_activity($user_id, $action, $details = null, $ip_address = null) {
    global $db; // Access the global Database object

    // Get IP address if not provided
    if ($ip_address === null) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    }

    // Use the insert method of the Database class
    $sql = "INSERT INTO user_activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)";
    $types = "isss";
    $params = [$user_id, $action, $details, $ip_address];

    // Adjust types and params if user_id is null, to match 's' for string
    if ($user_id === null) {
        $types[0] = 's'; // Change 'i' to 's' for user_id
        $params[0] = null; // Ensure it's explicitly null for binding
    }
    
    // Use the query method for simplicity, as insert returns last_insert_id which is not needed here
    $db->query($sql, $params, $types);
}
}

if (!function_exists('get_setting')) {
/**
 * Gets a setting value from the database.
 *
 * @param string $key The setting key.
 * @param mixed $default The default value to return if the setting is not found.
 * @return mixed The setting value or the default value.
 */
function get_setting($key, $default = null) {
    global $db;
    $sql = "SELECT setting_value FROM settings WHERE setting_key = ?";
    $row = $db->fetch($sql, [$key], "s");
    if ($row) {
        return $row['setting_value'];
    }
    return $default;
}
}

if (!function_exists('set_setting')) {
/**
 * Sets a setting value in the database.
 * Inserts if the key does not exist, updates if it does.
 *
 * @param string $key The setting key.
 * @param mixed $value The setting value.
 * @return bool True on success, false on failure.
 */
function set_setting($key, $value) {
    global $db;
    // Check if setting exists
    $sql_check = "SELECT COUNT(*) FROM settings WHERE setting_key = ?";
    $row_check = $db->fetch($sql_check, [$key], "s");
    $exists = $row_check['COUNT(*)'] > 0;

    if ($exists) {
        $sql = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
        $affected_rows = $db->update($sql, [$value, $key], "ss");
    } else {
        $sql = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)";
        $affected_rows = $db->insert($sql, [$key, $value], "ss");
    }
    return $affected_rows > 0;
}
}
?>