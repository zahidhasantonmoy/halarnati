<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
?>