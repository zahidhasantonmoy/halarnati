<?php
/**
 * Displays the user's entries.
 * Allows searching and pagination.
 */
include 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']);

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$search = htmlspecialchars($_GET['search'] ?? '');
$search_query_param = '%' . $search . '%';

// Fetch user's entries with search and pagination
$user_entries = $db->fetchAll("SELECT id, title, type, created_at, view_count, slug FROM entries WHERE user_id = ? AND (title LIKE ? OR text LIKE ?) ORDER BY created_at DESC LIMIT ? OFFSET ?", [$user_id, $search_query_param, $search_query_param, $limit, $offset], "issii");

// Total entry count for pagination
$total_entries_result = $db->fetch("SELECT COUNT(*) AS total FROM entries WHERE user_id = ? AND (title LIKE ? OR text LIKE ?)", [$user_id, $search_query_param, $search_query_param], "iss");
$total_user_entries = $total_entries_result['total'];
$totalPages = ceil($total_user_entries / $limit);

include 'header.php';
?>

<div class="main-wrapper">
    <div class="row g-0">
        <!-- Left Sidebar (User Panel Navigation) -->
        <div class="col-12 col-lg-2 d-none d-lg-block sidebar-left">
            <div class="p-3">
                <h5>User Panel</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item bg-transparent border-0"><a href="user_panel.php" class="text-decoration-none text-white"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="my_entries.php" class="text-decoration-none text-white"><i class="fas fa-list me-2"></i> My Entries</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="my_drive.php" class="text-decoration-none text-white"><i class="fas fa-hdd me-2"></i> My Drive</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="profile.php" class="text-decoration-none text-white"><i class="fas fa-user-cog me-2"></i> Profile Settings</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="logout.php" class="text-decoration-none text-white"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="col-12 col-lg-8 main-content-area">
            <div class="container py-4">
                <h1 class="text-center mb-4">My Entries</h1>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-search"></i> Search My Entries
                    </div>
                    <div class="card-body">
                        <form action="my_entries.php" method="get">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Search my entries..." value="<?= htmlspecialchars($search) ?>">
                                <button type="submit" class="btn btn-info"><i class="fas fa-search"></i> Search</button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if (empty($user_entries)): ?>
                    <div class="alert alert-warning text-center">No entries found matching your criteria.</div>
                <?php else: ?>
                    <?php foreach ($user_entries as $entry): ?>
                        <div class="card entry-card mb-3">
                            <div class="card-body">
                                <h5 class="card-title entry-title">
                                    <?php
                                    if ($entry['type'] === 'code') {
                                        echo '<i class="fas fa-code"></i> Code: ';
                                    } elseif ($entry['type'] === 'file') {
                                        echo '<i class="fas fa-file"></i> File: ';
                                    } else {
                                        echo '<i class="fas fa-align-left"></i> Text: ';
                                    }
                                    ?><?= htmlspecialchars($entry['title']) ?>
                                </h5>
                                <p class="card-text entry-meta">
                                    <small class="text-muted">Created: <?= $entry['created_at'] ?></small>
                                    <span class="view-count"><i class="fas fa-eye"></i> Views: <?= $entry['view_count'] ?? 0 ?></span>
                                </p>
                                <a href="entry.php?<?= $entry['slug'] ? 'slug=' . htmlspecialchars($entry['slug']) : 'id=' . $entry['id'] ?>" class="btn btn-primary btn-sm mt-2">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                                <a href="edit_entry.php?id=<?= $entry['id'] ?>" class="btn btn-info btn-sm mt-2">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="delete_entry.php?id=<?= $entry['id'] ?>" class="btn btn-danger btn-sm mt-2" onclick="return confirm('Are you sure you want to delete this entry?');">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?><?= isset($_GET['search']) ? '&search=' . htmlspecialchars($_GET['search']) : '' ?>"><?= $i ?></a>
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
                    <li class="list-group-item bg-transparent border-0"><a href="index.php" class="text-decoration-none text-white">Go to Home</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="register.php" class="text-decoration-none text-white">Register New User</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>