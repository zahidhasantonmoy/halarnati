<?php
/**
 * Theme Toggle Endpoint
 */
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit;
}

// Toggle theme
$newTheme = ThemeManager::toggleTheme();

// Return JSON response
header('Content-Type: application/json');
echo json_encode(['theme' => $newTheme]);
?>