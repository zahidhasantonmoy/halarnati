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

// Define the path to the PHP error log file
// This is a common location, but might vary based on server configuration.
// You might need to adjust this path.
$logFilePath = '../php_error.log'; // Assuming it's in the htdocs directory

$logContent = [];
$notification = "";

if (file_exists($logFilePath) && is_readable($logFilePath)) {
    $logContent = file($logFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $logContent = array_reverse($logContent); // Show latest entries first
} else {
    $notification = "Error log file not found or not readable at: " . htmlspecialchars($logFilePath);
}

// Pagination setup
$limit = 50; // Number of log entries per page
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$totalLogs = count($logContent);
$totalPages = ceil($totalLogs / $limit);

$pagedLogs = array_slice($logContent, $offset, $limit);

include '../header.php';
?>

<div class="main-wrapper">
    <div class="row g-0">
        <!-- Left Sidebar (Admin Panel Navigation) -->
        <div class="col-12 col-lg-2 d-none d-lg-block sidebar-left">
            <div class="p-3">
                <h5>Admin Panel</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item bg-transparent border-0"><a href="/guru/admin_dashboard.php" class="text-decoration-none text-white"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="/guru/manage_users.php" class="text-decoration-none text-white"><i class="fas fa-users me-2"></i> Manage Users</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="/guru/manage_entries.php" class="text-decoration-none text-white"><i class="fas fa-list me-2"></i> Manage Entries</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="/guru/manage_categories.php" class="text-decoration-none text-white"><i class="fas fa-folder-open me-2"></i> Manage Categories</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="/guru/view_activity_logs.php" class="text-decoration-none text-white"><i class="fas fa-history me-2"></i> Activity Logs</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="/guru/error_log_viewer.php" class="text-decoration-none text-white"><i class="fas fa-bug me-2"></i> Error Log</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="/guru/settings.php" class="text-decoration-none text-white"><i class="fas fa-cog me-2"></i> Settings</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="/logout.php" class="text-decoration-none text-white"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="col-12 col-lg-8 main-content-area">
            <div class="container py-4">
                <h1 class="text-center mb-4">PHP Error Log Viewer</h1>
                <?php if ($notification): ?>
                    <div class="alert alert-warning text-center"><?= $notification ?></div>
                <?php endif; ?>

                <?php if (!empty($pagedLogs)): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            Recent Error Log Entries
                        </div>
                        <div class="card-body">
                            <pre style="white-space: pre-wrap; word-wrap: break-word; max-height: 500px; overflow-y: scroll; background-color: #f8f9fa; padding: 15px; border-radius: 5px;"><?= htmlspecialchars(implode("\n", $pagedLogs)) ?></pre>
                        </div>
                    </div>

                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $totalPages; $i++):
                                $isActive = ($i == $page) ? 'active' : '';
                            ?>
                                <li class="page-item <?= $isActive ?>">
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php elseif (!$notification):
                    // This case handles when the log file exists but is empty, or if there was no notification.
                ?>
                    <div class="alert alert-info text-center">No error logs found or log file is empty.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Sidebar (Placeholder for now) -->
        <div class="col-12 col-lg-2 d-none d-lg-block sidebar-right">
            <div class="p-3">
                <h5>Quick Links</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item bg-transparent border-0"><a href="/index.php" class="text-decoration-none text-white">Go to Home</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="/register.php" class="text-decoration-none text-white">Register New User</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>
