<?php
session_start();
include '../config.php';

// Redirect if not logged in or not an admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin'])) {
    header("Location: ../login.php");
    exit;
}

include '../header.php';
?>

<div class="main-wrapper">
    <div class="row g-0">
        <!-- Left Sidebar (Admin Panel Navigation) -->
        <div class="col-12 col-lg-2 d-none d-lg-block sidebar-left">
            <div class="p-3">
                <h5>Admin Panel</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item bg-transparent border-0"><a href="admin_dashboard.php" class="text-decoration-none text-white"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="manage_users.php" class="text-decoration-none text-white"><i class="fas fa-users me-2"></i> Manage Users</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="manage_entries.php" class="text-decoration-none text-white"><i class="fas fa-list me-2"></i> Manage Entries</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="manage_categories.php" class="text-decoration-none text-white"><i class="fas fa-folder-open me-2"></i> Manage Categories</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="view_activity_logs.php" class="text-decoration-none text-white"><i class="fas fa-history me-2"></i> Activity Logs</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="error_log_viewer.php" class="text-decoration-none text-white"><i class="fas fa-bug me-2"></i> Error Log</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="settings.php" class="text-decoration-none text-white"><i class="fas fa-cog me-2"></i> Settings</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="../logout.php" class="text-decoration-none text-white"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="col-12 col-lg-8 main-content-area">
            <div class="container py-4">
                <h1 class="text-center mb-4">Application Settings</h1>
                <p>This page will manage application settings.</p>
                <!-- Settings form will go here -->
            </div>
        </div>

        <!-- Right Sidebar (Placeholder for now) -->
        <div class="col-12 col-lg-2 d-none d-lg-block sidebar-right">
            <div class="p-3">
                <h5>Quick Links</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item bg-transparent border-0"><a href="/index.php" class="text-decoration-none text-white">Go to Home</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="../register.php" class="text-decoration-none text-white">Register New User</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>