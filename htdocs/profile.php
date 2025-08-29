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
$user = $db->fetch("SELECT username, email, avatar FROM users WHERE id = ?", [$user_id], "i");

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

        // Handle avatar upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            $file_extension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));

            if (in_array($file_extension, $allowed_extensions)) {
                $avatar_dir = 'uploads/avatars/';
                if (!is_dir($avatar_dir)) {
                    mkdir($avatar_dir, 0777, true);
                }
                $avatar_filename = uniqid('avatar_', true) . '.' . $file_extension;
                $avatar_path = $avatar_dir . $avatar_filename;

                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $avatar_path)) {
                    // Delete old avatar if it exists
                    if ($user['avatar'] && file_exists($user['avatar'])) {
                        unlink($user['avatar']);
                    }
                    $update_sql .= ", avatar = ?";
                    $params .= "s";
                    $bind_values[] = $avatar_path;
                } else {
                    $notification = "Error uploading avatar.";
                }
            } else {
                $notification = "Invalid file type for avatar. Only JPG, PNG, and GIF are allowed.";
            }
        }

        $update_sql .= " WHERE id = ?";
        $params .= "i";
        $bind_values[] = $user_id;

        $affected_rows = $db->update($update_sql, $bind_values, $params);

        if ($affected_rows > 0) {
            $notification = "Profile updated successfully!";
            log_activity($_SESSION['user_id'], 'User Profile Updated', 'User ' . $_SESSION['username'] . ' updated their profile.');
            // Re-fetch user data to display updated email and username
            $user = $db->fetch("SELECT username, email, avatar FROM users WHERE id = ?", [$user_id], "i");
        } else {
            if(empty($notification)) {
                $notification = "No changes were made.";
            }
        }
    }
}

include 'header.php';
?>

<div class="main-wrapper">
    <div class="row g-0 justify-content-center">
        <div class="col-12 col-lg-8 main-content-area">
            <div class="container py-4">
                <h1 class="text-center mb-4">Profile Settings</h1>
                <?php if ($notification): ?>
                    <div class="alert alert-info text-center"><?= $notification ?></div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-user-cog"></i> Edit Your Profile
                    </div>
                    <div class="card-body">
                        <form action="profile.php" method="post" enctype="multipart/form-data">
                            <div class="mb-3 text-center">
                                <?php if ($user['avatar']): ?>
                                    <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="User Avatar" class="img-thumbnail rounded-circle" width="150">
                                <?php else: ?>
                                    <i class="fas fa-user-circle fa-8x text-muted"></i>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="avatar" class="form-label">Change Avatar</label>
                                <input type="file" id="avatar" name="avatar" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                            <hr>
                            <h5 class="mb-3">Change Password</h5>
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" id="current_password" name="current_password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" id="new_password" name="new_password" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="confirm_new_password" class="form-label">Confirm New Password</label>
                                <input type="password" id="confirm_new_password" name="confirm_new_password" class="form-control">
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary w-100"><i class="fas fa-save"></i> Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>