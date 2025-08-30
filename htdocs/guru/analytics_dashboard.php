<?php
/**
 * Admin Analytics Dashboard
 */
include '../config.php';

// Redirect if not logged in or not an admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// Get date range for analytics
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Overall statistics
$totalEntries = $db->fetch("SELECT COUNT(*) as count FROM entries")['count'] ?? 0;
$totalUsers = $db->fetch("SELECT COUNT(*) as count FROM users")['count'] ?? 0;
$totalViews = $db->fetch("SELECT SUM(view_count) as total FROM entries")['total'] ?? 0;
$totalCategories = $db->fetch("SELECT COUNT(*) as count FROM categories")['count'] ?? 0;

// Entries by type
$entriesByType = $db->fetchAll("
    SELECT type, COUNT(*) as count 
    FROM entries 
    GROUP BY type
");

// Entries by date (last 30 days)
$entriesByDate = $db->fetchAll("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM entries 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date
");

// Top viewed entries
$topEntries = $db->fetchAll("
    SELECT title, view_count 
    FROM entries 
    ORDER BY view_count DESC 
    LIMIT 10
");

// User registration by date
$userRegistrations = $db->fetchAll("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM users 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date
");

// Categories with entry counts
$categoryStats = $db->fetchAll("
    SELECT c.name, COUNT(e.id) as entry_count
    FROM categories c
    LEFT JOIN entries e ON c.id = e.category_id
    GROUP BY c.id, c.name
    ORDER BY entry_count DESC
");

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
                    <li class="list-group-item bg-transparent border-0"><a href="analytics_dashboard.php" class="text-decoration-none text-white"><i class="fas fa-chart-bar me-2"></i> Analytics</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="error_log_viewer.php" class="text-decoration-none text-white"><i class="fas fa-bug me-2"></i> Error Log</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="settings.php" class="text-decoration-none text-white"><i class="fas fa-cog me-2"></i> Settings</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="../logout.php" class="text-decoration-none text-white"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="col-12 col-lg-8 main-content-area">
            <div class="container py-4">
                <h1 class="text-center mb-4">Analytics Dashboard</h1>
                
                <!-- Date Filter -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-filter"></i> Date Range Filter
                    </div>
                    <div class="card-body">
                        <form method="GET" action="analytics_dashboard.php">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
                                </div>
                                <div class="col-md-5">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($endDate) ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary w-100">Apply</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Overview Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-header bg-primary text-white">Total Entries</div>
                            <div class="card-body">
                                <h3 class="card-title"><?= $totalEntries ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-header bg-success text-white">Total Users</div>
                            <div class="card-body">
                                <h3 class="card-title"><?= $totalUsers ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-header bg-info text-white">Total Views</div>
                            <div class="card-body">
                                <h3 class="card-title"><?= $totalViews ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-header bg-warning text-white">Categories</div>
                            <div class="card-body">
                                <h3 class="card-title"><?= $totalCategories ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <i class="fas fa-chart-pie"></i> Entries by Type
                            </div>
                            <div class="card-body">
                                <canvas id="entriesByTypeChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <i class="fas fa-chart-line"></i> Entries Over Time
                            </div>
                            <div class="card-body">
                                <canvas id="entriesOverTimeChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Stats -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <i class="fas fa-fire"></i> Top Viewed Entries
                            </div>
                            <div class="card-body">
                                <?php if (empty($topEntries)): ?>
                                    <p>No entries found.</p>
                                <?php else: ?>
                                    <div class="list-group">
                                        <?php foreach ($topEntries as $entry): ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <span><?= htmlspecialchars($entry['title']) ?></span>
                                                <span class="badge bg-primary rounded-pill"><?= $entry['view_count'] ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <i class="fas fa-users"></i> User Registrations
                            </div>
                            <div class="card-body">
                                <canvas id="userRegistrationsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category Stats -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-folder-open"></i> Entries by Category
                    </div>
                    <div class="card-body">
                        <?php if (empty($categoryStats)): ?>
                            <p>No categories found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th>Entries</th>
                                            <th>Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $totalCategoryEntries = array_sum(array_column($categoryStats, 'entry_count'));
                                        foreach ($categoryStats as $category): 
                                            $percentage = $totalCategoryEntries > 0 ? round(($category['entry_count'] / $totalCategoryEntries) * 100, 1) : 0;
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($category['name']) ?></td>
                                                <td><?= $category['entry_count'] ?></td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar" role="progressbar" 
                                                             style="width: <?= $percentage ?>%" 
                                                             aria-valuenow="<?= $percentage ?>" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="100">
                                                            <?= $percentage ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar -->
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

<!-- Chart.js for charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Entries by Type Chart
    const entriesByTypeCtx = document.getElementById('entriesByTypeChart').getContext('2d');
    const entriesByTypeChart = new Chart(entriesByTypeCtx, {
        type: 'pie',
        data: {
            labels: [<?php foreach ($entriesByType as $type) { echo "'" . ucfirst($type['type']) . "',"; } ?>],
            datasets: [{
                data: [<?php foreach ($entriesByType as $type) { echo $type['count'] . ","; } ?>],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 205, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 205, 86, 1)',
                    'rgba(75, 192, 192, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });

    // Entries Over Time Chart
    const entriesOverTimeCtx = document.getElementById('entriesOverTimeChart').getContext('2d');
    const entriesOverTimeChart = new Chart(entriesOverTimeCtx, {
        type: 'line',
        data: {
            labels: [<?php foreach ($entriesByDate as $date) { echo "'" . $date['date'] . "',"; } ?>],
            datasets: [{
                label: 'Entries Created',
                data: [<?php foreach ($entriesByDate as $date) { echo $date['count'] . ","; } ?>],
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // User Registrations Chart
    const userRegistrationsCtx = document.getElementById('userRegistrationsChart').getContext('2d');
    const userRegistrationsChart = new Chart(userRegistrationsCtx, {
        type: 'bar',
        data: {
            labels: [<?php foreach ($userRegistrations as $reg) { echo "'" . $reg['date'] . "',"; } ?>],
            datasets: [{
                label: 'New Users',
                data: [<?php foreach ($userRegistrations as $reg) { echo $reg['count'] . ","; } ?>],
                backgroundColor: 'rgba(75, 192, 192, 0.8)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>

<?php include '../footer.php'; ?>