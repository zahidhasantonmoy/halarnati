<?php
/**
 * Handles user login.
 */
include 'config.php'; // Include your database connection

$notification = "";

// Generate CSRF token for the form
$csrf_token = generate_csrf_token();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $notification = "CSRF token validation failed. Please try again.";
        log_activity(null, 'CSRF Attack Attempt', 'Invalid CSRF token on login submission.');
        // For now, we'll just set notification and let the page reload
    } else {
        $username = htmlspecialchars($_POST['username']);
        $password = $_POST['password'];

        // Basic validation
        if (empty($username) || empty($password)) {
            $notification = "Username and password are required.";
            log_activity(null, 'User Login Failed', 'Missing credentials for username: ' . $username); // Log failed attempt
        } else {
            // Fetch user from database
            $user = $db->fetch("SELECT id, username, password, is_admin FROM users WHERE username = ?", [$username], "s");

            if ($user) {
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Password is correct, start session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['is_admin'] = $user['is_admin'];

                    log_activity($user['id'], 'User Login', 'Successful login for username: ' . $user['username']); // Log successful login

                    // Redirect to dashboard or home page
                    if ($user['is_admin']) {
                        header("Location: guru/admin_dashboard.php"); // Redirect to admin panel if admin
                    } else {
                        header("Location: index.php"); // Redirect to home page for regular users
                    }
                    exit;
                } else {
                    $notification = "Invalid username or password.";
                    log_activity(null, 'User Login Failed', 'Invalid password for username: ' . $username); // Log failed attempt
                }
            } else {
                $notification = "Invalid username or password.";
                log_activity(null, 'User Login Failed', 'Invalid username: ' . $username); // Log failed attempt
            }
        }
    }
}

// If user is already logged in, redirect them
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['is_admin']) {
        header("Location: guru/admin_dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Halarnati</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="main-wrapper">
        <div class="row g-0 justify-content-center">
            <div class="col-12 col-md-6 col-lg-4 main-content-area">
                <div class="container py-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            Login
                        </div>
                        <div class="card-body">
                            <?php if ($notification): ?>
                                <div class="alert alert-info text-center"><?= $notification ?></div>
                            <?php endif; ?>
                            <form action="login.php" method="post">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" id="username" name="username" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" id="password" name="password" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Login</button>
                            </form>
                            <p class="mt-3 text-center">Don't have an account? <a href="register.php">Register here</a> | <a href="index.php">Go to Homepage</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>