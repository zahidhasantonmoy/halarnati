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

// Pagination setup
$limit = 20; // Number of logs per page
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Fetch logs
$logs_query = "SELECT ual.*, u.username FROM user_activity_logs ual LEFT JOIN users u ON ual.user_id = u.id ORDER BY ual.timestamp DESC LIMIT ? OFFSET ?";
$activity_logs = $db->fetchAll($logs_query, [$limit, $offset], "ii");

// Get total number of logs for pagination
$total_logs_result = $db->fetch("SELECT COUNT(*) AS total FROM user_activity_logs");
$total_logs = $total_logs_result['total'];
$totalPages = ceil($total_logs / $limit);

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
                    <li class="list-group-item bg-transparent border-0"><a href="/guru/analytics_dashboard.php" class="text-decoration-none text-white"><i class="fas fa-chart-bar me-2"></i> Analytics</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="/guru/error_log_viewer.php" class="text-decoration-none text-white"><i class="fas fa-bug me-2"></i> Error Log</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="/guru/settings.php" class="text-decoration-none text-white"><i class="fas fa-cog me-2"></i> Settings</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="/logout.php" class="text-decoration-none text-white"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="col-12 col-lg-8 main-content-area">
            <div class="container py-4">
                <h1 class="text-center mb-4">User Activity Logs</h1>
                <?php if (empty($activity_logs)): ?>
                    <div class="alert alert-info text-center">No activity logs found.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="table-primary">
                                <tr>
                                    <th>Timestamp</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activity_logs as $log): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($log['timestamp']) ?></td>
                                        <td><?= htmlspecialchars($log['username'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($log['action']) ?></td>
                                        <td><?= htmlspecialchars($log['details'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
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