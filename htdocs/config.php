<?php
// Error Logging Configuration
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log'); // Log errors to php_error.log in the same directory as config.php
ini_set('display_errors', 0); // Disable displaying errors on screen for production
error_reporting(E_ALL); // Keep error reporting enabled for logging

// Database connection
$host = 'sql203.infinityfree.com';
$user = 'if0_37868453';
$pass = 'Yho7V4gkz6bP1';
$db = 'if0_37868453_halarnati';
$port = 3306; // Default MySQL port

$conn = new mysqli($host, $user, $pass, $db, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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
    global $conn; // Access the global database connection

    // Get IP address if not provided
    if ($ip_address === null) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    }

    $stmt = $conn->prepare("INSERT INTO user_activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    // 'siss' -> string, int, string, string (user_id can be null, so 'i' for int, but if null, it will be treated as 0 by bind_param, so better to use 's' and handle null explicitly or make user_id nullable in DB and pass null)
    // For simplicity and to avoid issues with bind_param and null integers, we'll cast user_id to string if it's null.
    $user_id_param = ($user_id === null) ? null : (int)$user_id;

    // Use call_user_func_array for dynamic binding with potentially null values
    $types = "isss"; // user_id (int), action (string), details (string), ip_address (string)
    $params = array(&$user_id_param, &$action, &$details, &$ip_address);

    // Adjust types and params if user_id is null, to match 's' for string
    if ($user_id === null) {
        $types[0] = 's'; // Change 'i' to 's' for user_id
        $user_id_param = null; // Ensure it's explicitly null for binding
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->close();
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
    global $conn;
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
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
    global $conn;
    // Check if setting exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_row();
    $exists = $row[0] > 0;
    $stmt->close();

    if ($exists) {
        $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->bind_param("ss", $value, $key);
    } else {
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->bind_param("ss", $key, $value);
    }
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}
}
?>