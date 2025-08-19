<?php
session_start();
// Database connection
$host = 'sql203.infinityfree.com';
$user = 'if0_37868453';
$pass = 'Yho7V4gkz6bP1';
$db = 'if0_37868453_halarnati';
$port = 3306;
$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$notification = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = 'murikhaw'; // Hardcoded admin username
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($newPassword !== $confirmPassword) {
        $error = "New passwords do not match.";
    } else {
        // Fetch the current user record
        $stmt = $conn->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Verify the current password using the OLD method
        if ($user && hash('sha256', $currentPassword) === $user['password']) {
            // Current password is correct, now update to the new, secure hash
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

            $updateStmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE username = ?");
            $updateStmt->bind_param("ss", $newPasswordHash, $username);
            $updateStmt->execute();
            $updateStmt->close();

            $notification = "Password successfully updated to the new secure format! You can now log in with your new password. Please delete this file immediately.";
        } else {
            $error = "The current password you entered is incorrect.";
        }
    }
}

require_once '../header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card mt-4">
                <div class="card-header">
                    <h3><i class="fas fa-shield-alt"></i> Secure Password Upgrade</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> This is a one-time setup script. For your security, you MUST delete this file (secure_password_setup.php) from your server after you have successfully updated your password.</div>
                    <?php if ($notification): ?>
                        <div class="alert alert-success"><?= $notification ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Enter Your CURRENT Password</label>
                            <input type="password" name="current_password" id="current_password" class="form-control" required>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Enter Your NEW Password</label>
                            <input type="password" name="new_password" id="new_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Your NEW Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> Upgrade My Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../footer.php'; ?>
