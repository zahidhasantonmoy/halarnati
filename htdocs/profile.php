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
$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

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
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();
    $stmt->close();

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
            $stmt_check_username = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt_check_username->bind_param("si", $new_username, $user_id);
            $stmt_check_username->execute();
            $stmt_check_username->store_result();
            if ($stmt_check_username->num_rows > 0) {
                $notification = "Username already taken.";
                $stmt_check_username->close();
            } else {
                $stmt_check_username->close();

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

        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param($params, ...$bind_values);

        if ($stmt->execute()) {
            $notification = "Profile updated successfully!";
            log_activity($_SESSION['user_id'], 'User Profile Updated', 'User ' . $_SESSION['username'] . ' updated their profile.');
            // Re-fetch user data to display updated email and username
            $stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
        } else {
            $notification = "Error updating profile: " . $stmt->error;
        }
        $stmt->close();
    }


include 'header.php';
?>

<div class="main-wrapper">
    <div class="row g-0">
        <!-- Left Sidebar (User Panel Navigation) -->
        <div class="col-12 col-lg-2 d-none d-lg-block sidebar-left">
            <div class="p-3">
                <h5>User Panel</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item bg-transparent border-0"><a href="user_panel.php" class="text-decoration-none text-white"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="my_entries.php" class="text-decoration-none text-white"><i class="fas fa-list me-2"></i> My Entries</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="my_drive.php" class="text-decoration-none text-white"><i class="fas fa-hdd me-2"></i> My Drive</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="profile.php" class="text-decoration-none text-white"><i class="fas fa-user-cog me-2"></i> Profile Settings</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="logout.php" class="text-decoration-none text-white"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="col-12 col-lg-8 main-content-area">
            <div class="container py-4">
                <h1 class="text-center mb-4">Profile Settings</h1>

                <div class="card mb-4">
                    <div class="card-header">
                        Update Profile
                    </div>
                    <div class="card-body">
                        <?php if ($notification): ?>
                            <div class="alert alert-info text-center"><?= $notification ?></div>
                        <?php endif; ?>
                        <form action="profile.php" method="post">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password (required to save changes)</label>
                                <input type="password" id="current_password" name="current_password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password (leave blank to keep current)</label>
                                <input type="password" id="new_password" name="new_password" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="confirm_new_password" class="form-label">Confirm New Password</label>
                                <input type="password" id="confirm_new_password" name="confirm_new_password" class="form-control">
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary w-100">Update Profile</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar (Placeholder for now) -->
        <div class="col-12 col-lg-2 d-none d-lg-block sidebar-right">
            <div class="p-3">
                <h5>Quick Links</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item bg-transparent border-0"><a href="index.php" class="text-decoration-none text-white">Go to Home</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="register.php" class="text-decoration-none text-white">Register New User</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>