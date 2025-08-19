<?php
session_start(); // Start session for user login
include 'config.php'; // Include your database connection

$notification = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = htmlspecialchars($_POST['username']);
    $password = $_POST['password'];

    // Basic validation
    if (empty($username) || empty($password)) {
        $notification = "Username and password are required.";
    } else {
        // Fetch user from database
        $stmt = $conn->prepare("SELECT id, username, password, is_admin FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id, $db_username, $hashed_password, $is_admin);
            $stmt->fetch();

            // Verify password
            if (password_verify($password, $hashed_password)) {
                // Password is correct, start session
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $db_username;
                $_SESSION['is_admin'] = $is_admin;

                // Redirect to dashboard or home page
                if ($is_admin) {
                    header("Location: admin_panel.php"); // Redirect to admin panel if admin
                } else {
                    header("Location: index.php"); // Redirect to home page for regular users
                }
                exit;
            } else {
                $notification = "Invalid username or password.";
            }
        } else {
            $notification = "Invalid username or password.";
        }
        $stmt->close();
    }
}

// If user is already logged in, redirect them
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['is_admin']) {
        header("Location: admin_panel.php");
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
                            <p class="mt-3 text-center">Don't have an account? <a href="register.php">Register here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>