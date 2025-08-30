<?php
/**
 * Handles user registration.
 */
include 'config.php'; // Include your database connection

$notification = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        echo "All fields are required.";
        exit;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format.";
        exit;
    } elseif ($password !== $confirm_password) {
        echo "Passwords do not match.";
        exit;
    } elseif (strlen($password) < 6) {
        echo "Password must be at least 6 characters long.";
        exit;
    } else {
        // Check if username or email already exists
        $existing_user = $db->fetch("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email], "ss");

        if ($existing_user) {
            echo "Username or Email already exists.";
            exit;
        } else {
            // Handle avatar upload
            $avatar_path = null;
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

                    if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $avatar_path)) {
                        echo "Error uploading avatar.";
                        $avatar_path = null;
                        exit;
                    }
                } else {
                    echo "Invalid file type for avatar. Only JPG, PNG, and GIF are allowed.";
                    exit;
                }
            }

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user
            $insert_id = $db->insert("INSERT INTO users (username, email, password, is_admin, avatar) VALUES (?, ?, ?, 0, ?)", [$username, $email, $hashed_password, $avatar_path], "ssss");

            if ($insert_id) {
                echo "success";
                exit;
            } else {
                echo "Error: " . $db->getConnection()->error;
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Halarnati</title>
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
                            Register
                        </div>
                        <div class="card-body">
                            <?php if ($notification): ?>
                                <div class="alert alert-info text-center"><?= $notification ?></div>
                            <?php endif; ?>
                            <form action="register.php" method="post" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" id="username" name="username" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" id="email" name="email" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" id="password" name="password" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="avatar" class="form-label">Avatar (Optional)</label>
                                    <input type="file" id="avatar" name="avatar" class="form-control">
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Register</button>
                            </form>
                            <p class="mt-3 text-center">Already have an account? <a href="login.php">Login here</a> | <a href="index.php">Go to Homepage</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>