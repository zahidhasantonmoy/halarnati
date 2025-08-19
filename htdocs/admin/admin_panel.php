<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

require_once '../db.php';

// Fetch dashboard stats
$totalEntries = $conn->query("SELECT COUNT(*) AS total FROM entries")->fetch_assoc()['total'];
$totalVisible = $conn->query("SELECT COUNT(*) AS total FROM entries WHERE is_visible = 1")->fetch_assoc()['total'];
$totalHidden = $conn->query("SELECT COUNT(*) AS total FROM entries WHERE is_visible = 0")->fetch_assoc()['total'];
$totalDownloads = $conn->query("SELECT SUM(download_count) AS total FROM entries")->fetch_assoc()['total'];

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Fetch entries
$result = $conn->query("SELECT * FROM entries ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$entries = $result->fetch_all(MYSQLI_ASSOC);
$totalPages = ceil($totalEntries / $limit);

require_once '../header.php';
?>

<div class="container-fluid">
    <div class="row">
        <main class="col-md-12 ms-sm-auto col-lg-12 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Admin Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="logout.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row">
                <div class="col-md-3 mb-3"><div class="card text-white bg-primary"><div class="card-body"><h5 class="card-title"><i class="fas fa-archive"></i> Total Entries</h5><p class="card-text fs-4">$totalEntries</p></div></div></div>
                <div class="col-md-3 mb-3"><div class="card text-white bg-success"><div class="card-body"><h5 class="card-title"><i class="fas fa-eye"></i> Visible Entries</h5><p class="card-text fs-4">$totalVisible</p></div></div></div>
                <div class="col-md-3 mb-3"><div class="card text-white bg-warning"><div class="card-body"><h5 class="card-title"><i class="fas fa-eye-slash"></i> Hidden Entries</h5><p class="card-text fs-4">$totalHidden</p></div></div></div>
                <div class="col-md-3 mb-3"><div class="card text-white bg-info"><div class="card-body"><h5 class="card-title"><i class="fas fa-download"></i> Total Downloads</h5><p class="card-text fs-4">$totalDownloads ?? 0</p></div></div></div>
            </div>

            <h2 class="mt-4">Manage Entries</h2>
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Created At</th>
                            <th>Status</th>
                            <th>Downloads</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $entry): ?>
                            <tr>
                                <td><?= $entry['id'] ?></td>
                                <td><?= htmlspecialchars($entry['title']) ?></td>
                                <td><?= $entry['created_at'] ?></td>
                                <td><?= $entry['is_visible'] ? '<span class="badge bg-success">Visible</span>' : '<span class="badge bg-warning">Hidden</span>' ?></td>
                                <td><?= $entry['download_count'] ?? 0 ?></td>
                                <td>
                                    <a href="../view_entry.php?id=<?= $entry['id'] ?>" class="btn btn-info btn-sm" target="_blank" title="View"><i class="fas fa-eye"></i></a>
                                    <a href="edit_entry.php?id=<?= $entry['id'] ?>" class="btn btn-primary btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="toggle_visibility.php?id=<?= $entry['id'] ?>" class="btn btn-warning btn-sm" title="Toggle Visibility"><i class="fas fa-exchange-alt"></i></a>
                                    <a href="delete_entry.php?id=<?= $entry['id'] ?>" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this entry permanently?');"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a></li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php require_once '../footer.php'; ?>
