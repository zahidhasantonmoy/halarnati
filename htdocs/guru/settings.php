<?php
// Include your database connection, which now initializes $db
include_once '../config.php';

// Check if $db is properly initialized
if (!isset($db) || !is_object($db)) {
    die("Database connection failed. Please check your configuration.");
}

// Redirect if not logged in or not an admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin'])) {
    header("Location: ../login.php");
    exit;
}

$notification = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $site_title = htmlspecialchars($_POST['site_title']);
    $site_description = htmlspecialchars($_POST['site_description']);

    $success_title = set_setting('site_title', $site_title);
    $success_description = set_setting('site_description', $site_description);

    if ($success_title && $success_description) {
        $notification = "Settings saved successfully!";
    } else {
        $notification = "Error saving settings.";
    }
}

// Fetch current settings
$current_site_title = get_setting('site_title', 'Halarnati'); // Default value
$current_site_description = get_setting('site_description', 'A modern platform for sharing text, code, and files.'); // Default value

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
                <?php if ($notification): ?>
                    <div class="alert alert-info text-center"><?= $notification ?></div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header">
                        General Settings
                    </div>
                    <div class="card-body">
                        <form action="settings.php" method="POST">
                            <div class="mb-3">
                                <label for="site_title" class="form-label">Site Title</label>
                                <input type="text" id="site_title" name="site_title" class="form-control" value="<?= htmlspecialchars($current_site_title) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="site_description" class="form-label">Site Description</label>
                                <textarea id="site_description" name="site_description" class="form-control" rows="3" required><?= htmlspecialchars($current_site_description) ?></textarea>
                            </div>
                            <button type="submit" name="save_settings" class="btn btn-primary w-100">Save Settings</button>
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
                    <li class="list-group-item bg-transparent border-0"><a href="/index.php" class="text-decoration-none text-white">Go to Home</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="../register.php" class="text-decoration-none text-white">Register New User</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>