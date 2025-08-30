<?php
include 'config.php'; // Include config for settings functions

$user_avatar = null;
if (isset($_SESSION['user_id'])) {
    $user_data = $db->fetch("SELECT avatar FROM users WHERE id = ?", [$_SESSION['user_id']], "i");
    if ($user_data && $user_data['avatar']) {
        $user_avatar = $user_data['avatar'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(get_setting('site_title', 'Halarnati')) ?> - <?= htmlspecialchars(get_setting('site_description', 'Modern Sharing Platform')) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Roboto+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dark_style.css">
    <!-- Prism.js for code highlighting -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet" />
    <style>
        /* Modal animation styles */
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        .modal-header {
            border-radius: 15px 15px 0 0;
        }
        .fade-scale {
            transform: scale(0.7);
            opacity: 0;
            transition: all 0.3s ease-in-out;
        }
        .fade-scale.show {
            transform: scale(1);
            opacity: 1;
        }
        .login-btn, .register-btn {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php"><i class="fas fa-share-alt"></i> <?= htmlspecialchars(get_setting('site_title', 'Halarnati')) ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home"></i> Home</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="user_panel.php">
                                <?php if ($user_avatar): ?>
                                    <img src="<?= htmlspecialchars($user_avatar) ?>" alt="User Avatar" class="rounded-circle" width="25" height="25">
                                <?php else: ?>
                                    <i class="fas fa-user"></i>
                                <?php endif; ?>
                                Welcome, <?= htmlspecialchars($_SESSION['username']) ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="user_panel.php"><i class="fas fa-user-circle"></i> User Panel</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </li>
                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="guru/admin_dashboard.php"><i class="fas fa-user-shield"></i> Admin Panel</a>
                            </li>
                        <?php endif; ?>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link login-btn" data-bs-toggle="modal" data-bs-target="#loginModal"><i class="fas fa-sign-in-alt"></i> Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link register-btn" data-bs-toggle="modal" data-bs-target="#registerModal"><i class="fas fa-user-plus"></i> Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content fade-scale">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="loginModalLabel">Login to Your Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="loginForm">
                        <div class="mb-3">
                            <label for="modalUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="modalUsername" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="modalPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="modalPassword" name="password" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>
                    <div class="text-center mt-3">
                        <p>Don't have an account? <a href="#" class="register-btn" data-bs-toggle="modal" data-bs-target="#registerModal">Register here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content fade-scale">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="registerModalLabel">Create New Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="registerForm">
                        <div class="mb-3">
                            <label for="modalRegUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="modalRegUsername" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="modalRegEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="modalRegEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="modalRegPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="modalRegPassword" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="modalRegConfirmPassword" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="modalRegConfirmPassword" name="confirm_password" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">Register</button>
                        </div>
                    </form>
                    <div class="text-center mt-3">
                        <p>Already have an account? <a href="#" class="login-btn" data-bs-toggle="modal" data-bs-target="#loginModal">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for modal forms -->
    <script>
        // Handle login form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = document.getElementById('modalUsername').value;
            const password = document.getElementById('modalPassword').value;
            
            // Create FormData object
            const formData = new FormData();
            formData.append('username', username);
            formData.append('password', password);
            
            // Send AJAX request
            fetch('/login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Check if login was successful
                if (data.includes('success')) {
                    // Close modal and reload page
                    const modal = bootstrap.Modal.getInstance(document.getElementById('loginModal'));
                    if (modal) {
                        modal.hide();
                    }
                    location.reload();
                } else {
                    // Show error message
                    alert('Login failed. Please check your credentials.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred during login.');
            });
        });
        
        // Handle register form submission
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = document.getElementById('modalRegUsername').value;
            const email = document.getElementById('modalRegEmail').value;
            const password = document.getElementById('modalRegPassword').value;
            const confirmPassword = document.getElementById('modalRegConfirmPassword').value;
            
            // Check if passwords match
            if (password !== confirmPassword) {
                alert('Passwords do not match.');
                return;
            }
            
            // Create FormData object
            const formData = new FormData();
            formData.append('username', username);
            formData.append('email', email);
            formData.append('password', password);
            formData.append('confirm_password', confirmPassword);
            
            // Send AJAX request
            fetch('/register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Check if registration was successful
                if (data.includes('success')) {
                    // Close modal and show success message
                    const modal = bootstrap.Modal.getInstance(document.getElementById('registerModal'));
                    if (modal) {
                        modal.hide();
                    }
                    alert('Registration successful! You can now login.');
                    // Open login modal
                    setTimeout(() => {
                        new bootstrap.Modal(document.getElementById('loginModal')).show();
                    }, 500);
                } else {
                    // Show error message
                    alert('Registration failed. ' + data);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred during registration.');
            });
        });
        
        // Handle navigation between modals
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('login-btn')) {
                const registerModal = document.getElementById('registerModal');
                if (registerModal) {
                    const registerModalInstance = bootstrap.Modal.getInstance(registerModal);
                    if (registerModalInstance) {
                        registerModalInstance.hide();
                    }
                }
            } else if (e.target.classList.contains('register-btn')) {
                const loginModal = document.getElementById('loginModal');
                if (loginModal) {
                    const loginModalInstance = bootstrap.Modal.getInstance(loginModal);
                    if (loginModalInstance) {
                        loginModalInstance.hide();
                    }
                }
            }
        });
    </script>
</body>
</html>