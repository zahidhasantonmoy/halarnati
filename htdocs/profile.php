<?php
/**
 * Handles user profile updates.
 */
include 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$notification = "";

// Fetch user data
$user = $db->fetch("SELECT username, email FROM users WHERE id = ?", [$user_id], "i");

if (!$user) {
    // User not found, something is wrong with session
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $new_email = htmlspecialchars($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    // Validate current password before allowing changes
    $user_password_data = $db->fetch("SELECT password FROM users WHERE id = ?", [$user_id], "i");
    $hashed_password = $user_password_data['password'];

    if (!password_verify($current_password, $hashed_password)) {
        $notification = "Incorrect current password.";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $notification = "Invalid email format.";
    } elseif (!empty($new_password) && strlen($new_password) < 6) {
        $notification = "New password must be at least 6 characters long.";
    } elseif (!empty($new_password) && $new_password !== $confirm_new_password) {
        $notification = "New passwords do not match.";
    } else {
        $update_sql = "UPDATE users SET email = ?";
        $params = "s";
        $bind_values = [$new_email];

        // If username is changed, update it
        $new_username = htmlspecialchars($_POST['username']);
        if ($new_username !== $user['username']) {
            // Check if new username already exists
            $existing_username = $db->fetch("SELECT id FROM users WHERE username = ? AND id != ?", [$new_username, $user_id], "si");
            if ($existing_username) {
                $notification = "Username already taken.";
            } else {
                $update_sql = "UPDATE users SET username = ?, email = ?"; // Re-set SQL for username update
                $params = "ss";
                $bind_values = [$new_username, $new_email];
                $_SESSION['username'] = $new_username; // Update session username
            }
        }

        if (!empty($new_password)) {
            $update_sql .= ", password = ?";
            $params .= "s";
            $bind_values[] = password_hash($new_password, PASSWORD_DEFAULT);
        }
        $update_sql .= " WHERE id = ?";
        $params .= "i";
        $bind_values[] = $user_id;

        $affected_rows = $db->update($update_sql, $bind_values, $params);

        if ($affected_rows > 0) {
            $notification = "Profile updated successfully!";
            log_activity($_SESSION['user_id'], 'User Profile Updated', 'User ' . $_SESSION['username'] . ' updated their profile.');
            // Re-fetch user data to display updated email and username
            $user = $db->fetch("SELECT username, email FROM users WHERE id = ?", [$user_id], "i");
        } else {
            $notification = "Error updating profile: " . $db->getConnection()->error;
        }
    }
}


include 'header.php';
?>