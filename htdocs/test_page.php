<?php
/**
 * Simple test to check if the page loads correctly
 */
include 'config.php';

echo "<h1>Simple Test Page</h1>";
echo "<p>If you can see this, the page is loading correctly.</p>";

if (isset($_SESSION['user_id'])) {
    echo "<p>You are logged in as: " . htmlspecialchars($_SESSION['username']) . "</p>";
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        echo "<p>You are an admin user.</p>";
    }
} else {
    echo "<p>You are not logged in.</p>";
}

echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
?>