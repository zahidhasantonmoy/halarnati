<?php
/**
 * Handles user logout.
 */
include 'config.php';
session_unset(); // Unset all session variables
session_destroy(); // Destroy the session

header("Location: login.php"); // Redirect to login page
exit;
?>