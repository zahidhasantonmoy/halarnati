<?php
// Session test
echo "<h1>Session Test</h1>";

// Check session status
echo "<p>Session status: " . session_status() . "</p>";
echo "<p>Session ID: " . session_id() . "</p>";

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    echo "<p>Starting session...</p>";
    session_start();
    echo "<p>Session started. New session ID: " . session_id() . "</p>";
} else {
    echo "<p>Session already started.</p>";
}

// Check session data
echo "<p>Session data:</p>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Try to set a session variable
$_SESSION['test_var'] = 'test_value';
echo "<p>Set test session variable.</p>";

// Check if it was set
echo "<p>Test variable value: " . (isset($_SESSION['test_var']) ? $_SESSION['test_var'] : 'Not set') . "</p>";

// Try to include config to see if it affects session
echo "<p>Including config.php...</p>";
include 'config.php';

echo "<p>Session data after including config:</p>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    echo "<p>User is logged in with ID: " . $_SESSION['user_id'] . "</p>";
    
    if (isset($_SESSION['is_admin'])) {
        echo "<p>User is admin: " . ($_SESSION['is_admin'] ? 'Yes' : 'No') . "</p>";
    } else {
        echo "<p>Admin status not set in session</p>";
    }
} else {
    echo "<p>User is not logged in</p>";
}
?>