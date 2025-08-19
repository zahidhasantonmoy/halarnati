<?php
// --- CONFIGURATION ---

// Database Connection
$db_host = 'sql203.infinityfree.com';
$db_user = 'if0_37868453';
$db_pass = 'Yho7V4gkz6bP1';
$db_name = 'if0_37868453_halarnati';
$db_port = 3306;

// --- INITIALIZATION ---

// Error Reporting
ini_set('display_errors', 1); // Set to 0 in production
error_reporting(E_ALL);

// Start Session
session_start();

// Create Database Connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

// Check Connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>