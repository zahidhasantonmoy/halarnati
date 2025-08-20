<?php
/**
 * Admin dashboard.
 * Displays stats and allows managing entries.
 */
session_start();
include '../config.php'; // Include your database connection

// Redirect if not logged in or not an admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php"); // Redirect to main login page
    exit;
}

// Notifications
$notification = "";

// Handle Delete Entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_entry'])) {
    $entryId = (int)$_POST['entry_id'];

    // Fetch file path before deleting
    $stmt = $conn->prepare("SELECT file_path FROM entries WHERE id = ?");
    $stmt->bind_param("i", $entryId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $filePath = $result['file_path'];
    $stmt->close();

    if ($filePath && file_exists('../' . $filePath)) { // Adjust path for admin context
        unlink('../' . $filePath); // Delete the file from storage
    }

    $stmt = $conn->prepare("DELETE FROM entries WHERE id = ?");
    $stmt->bind_param("i", $entryId);
    $stmt->execute();
    $stmt->close();

    $notification = "Entry and associated file successfully deleted.";
}

// Handle Edit Entry (Simplified for now, full edit will be via edit_entry.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_entry_modal'])) {
    $entryId = (int)$_POST['entry_id'];
    $title = htmlspecialchars($_POST['title']);
    $text = htmlspecialchars($_POST['text']);
    $lockKey = htmlspecialchars($_POST['lock_key'] ?? null);
    $language = htmlspecialchars($_POST['language'] ?? '');
    $slug = htmlspecialchars($_POST['slug'] ?? '');
    $is_visible = (int)$_POST['is_visible'];

    // Determine type based on language/file_path (simplified for admin edit)
    $entry_type = 'text';
    $current_file_path_stmt = $conn->prepare("SELECT file_path FROM entries WHERE id = ?");
    $current_file_path_stmt->bind_param("i", $entryId);
    $current_file_path_stmt->execute();
    $current_file_path_result = $current_file_path_stmt->get_result()->fetch_assoc();
    $current_file_path = $current_file_path_result['file_path'];
    $current_file_path_stmt->close();

    if (!empty($current_file_path)) {
        $entry_type = 'file';
    } elseif (!empty($language)) {
        $entry_type = 'code';
    }

    $stmt = $conn->prepare("UPDATE entries SET title = ?, text = ?, type = ?, language = ?, lock_key = ?, slug = ?, is_visible = ? WHERE id = ?");
    $stmt->bind_param("ssssssii", $title, $text, $entry_type, $language, $lockKey, $slug, $is_visible, $entryId);
    $stmt->execute();
    $stmt->close();

    $notification = "Entry successfully updated.";
}

// Handle Visibility Toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_visibility'])) {
    $entryId = (int)$_POST['entry_id'];
    $visibility = (int)$_POST['visibility']; // 0 = hidden, 1 = visible
    $stmt = $conn->prepare("UPDATE entries SET is_visible = ? WHERE id = ?");
    $stmt->bind_param("ii", $visibility, $entryId);
    $stmt->execute();
    $stmt->close();

    $notification = $visibility ? "Entry made visible." : "Entry hidden.";
}

// Handle Export Entries (Adapt to new schema)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_entries'])) {
    $result = $conn->query("SELECT id, title, text, type, language, file_path, lock_key, slug, user_id, created_at, view_count, is_visible FROM entries");
    $filename = "entries_" . date('Ymd') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Title', 'Text', 'Type', 'Language', 'File Path', 'Lock Key', 'Slug', 'User ID', 'Created At', 'View Count', 'Visibility']);
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Handle Import Entries (Needs significant re-work for new schema, skipping for now)
// if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_entries'])) {
//     $notification = "Import functionality needs to be updated for new schema.";
// }

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$search = htmlspecialchars($_GET['search'] ?? '');
$query = "SELECT e.id, e.title, e.type, e.language, e.file_path, e.lock_key, e.slug, e.user_id, e.created_at, e.view_count, e.is_visible, u.username FROM entries e LEFT JOIN users u ON e.user_id = u.id WHERE e.title LIKE ? OR e.text LIKE ? ORDER BY e.created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$likeSearch = '%' . $search . '%';
$stmt->bind_param("ssii", $likeSearch, $likeSearch, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
$entries = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Total entry count for pagination
$totalEntriesResult = $conn->query("SELECT COUNT(*) AS total FROM entries");
$totalEntries = $totalEntriesResult ? $totalEntriesResult->fetch_assoc()['total'] : 0;
$totalPages = ceil($totalEntries / $limit);

// Fetch dashboard stats
$totalEntriesResult = $conn->query("SELECT COUNT(*) AS total FROM entries");
$totalEntries = $totalEntriesResult ? $totalEntriesResult->fetch_assoc()['total'] : 0;

$totalVisibleResult = $conn->query("SELECT COUNT(*) AS total FROM entries WHERE is_visible = 1");
$totalVisible = $totalVisibleResult ? $totalVisibleResult->fetch_assoc()['total'] : 0;

$totalHiddenResult = $conn->query("SELECT COUNT(*) AS total FROM entries WHERE is_visible = 0");
$totalHidden = $totalHiddenResult ? $totalHiddenResult->fetch_assoc()['total'] : 0;

$totalViewsResult = $conn->query("SELECT SUM(view_count) AS total FROM entries");
$totalViews = $totalViewsResult ? $totalViewsResult->fetch_assoc()['total'] : 0;

$totalUsersResult = $conn->query("SELECT COUNT(*) AS total FROM users");
$totalUsers = $totalUsersResult ? $totalUsersResult->fetch_assoc()['total'] : 0;

if (!$totalEntriesResult || !$totalVisibleResult || !$totalHiddenResult || !$totalViewsResult || !$totalUsersResult) {
    $notification = "Error fetching dashboard stats: " . $conn->error;
}

include '../header.php'; // Use new header
?>

<div class="main-wrapper">
    <div class="row g-0">
        <!-- Left Sidebar (Admin Panel Navigation) -->
        <div class="col-12 col-lg-2 d-none d-lg-block sidebar-left">
            <div class="p-3">
                <h5>Admin Panel</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item bg-transparent border-0"><a href="admin_panel.php" class="text-decoration-none text-white"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="manage_users.php" class="text-decoration-none text-white"><i class="fas fa-users me-2"></i> Manage Users</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="manage_entries.php" class="text-decoration-none text-white"><i class="fas fa-list me-2"></i> Manage Entries</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="../logout.php" class="text-decoration-none text-white"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="col-12 col-lg-8 main-content-area">
            <div class="container py-4">
                <h1 class="text-center mb-4">Admin Dashboard</h1>
                <?php if ($notification): ?>
                    <div class="alert alert-success text-center"><?= $notification ?></div>
                <?php endif; ?>

                <div class="stats row mb-4">
                    <div class="stat col-md-3 text-center">
                        <div class="card">
                            <div class="card-header">Total Entries</div>
                            <div class="card-body"><h3 class="card-title"><?= $totalEntries ?></h3></div>
                        </div>
                    </div>
                    <div class="stat col-md-3 text-center">
                        <div class="card">
                            <div class="card-header">Visible Entries</div>
                            <div class="card-body"><h3 class="card-title"><?= $totalVisible ?></h3></div>
                        </div>
                    </div>
                    <div class="stat col-md-3 text-center">
                        <div class="card">
                            <div class="card-header">Hidden Entries</div>
                            <div class="card-body"><h3 class="card-title"><?= $totalHidden ?></h3></div>
                        </div>
                    </div>
                    <div class="stat col-md-3 text-center">
                        <div class="card">
                            <div class="card-header">Total Views</div>
                            <div class="card-body"><h3 class="card-title"><?= $totalViews ?></h3></div>
                        </div>
                    </div>
                    <div class="stat col-md-3 text-center">
                        <div class="card">
                            <div class="card-header">Total Users</div>
                            <div class="card-body"><h3 class="card-title"><?= $totalUsers ?></h3></div>
                        </div>
                    </div>
                </div>

                <h2 class="mb-3">Manage Entries</h2>
                <div class="card mb-4">
                    <div class="card-header">Entry Actions</div>
                    <div class="card-body">
                        <form class="mb-3" method="GET">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Search entries..." value="<?= $search ?>">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                            </div>
                        </form>
                        <form method="POST" enctype="multipart/form-data">
                            <button type="submit" name="export_entries" class="btn btn-success mb-3"><i class="fas fa-file-export"></i> Export All Entries</button>
                            <!-- Import functionality is complex and needs re-work for new schema -->
                            <!-- <input type="file" name="import_file" class="form-control mb-3"> -->
                            <!-- <button type="submit" name="import_entries" class="btn btn-warning"><i class="fas fa-file-import"></i> Import Entries</button> -->
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-primary">
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>User</th>
                            <th>Visibility</th>
                            <th>Views</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($entries as $entry): ?>
                            <tr>
                                <td><?= $entry['id'] ?></td>
                                <td><?= htmlspecialchars($entry['title']) ?></td>
                                <td><?= htmlspecialchars($entry['type']) ?></td>
                                <td><?= htmlspecialchars($entry['username'] ?? 'Anonymous') ?></td>
                                <td><?= $entry['is_visible'] ? 'Visible' : 'Hidden' ?></td>
                                <td><?= $entry['view_count'] ?? 0 ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="entry_id" value="<?= $entry['id'] ?>">
                                        <input type="hidden" name="visibility" value="<?= $entry['is_visible'] ? 0 : 1 ?>">
                                        <button type="submit" name="toggle_visibility" class="btn btn-warning btn-sm">
                                            <?= $entry['is_visible'] ? '<i class="fas fa-eye-slash"></i> Hide' : '<i class="fas fa-eye"></i> Show' ?>
                                        </button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="entry_id" value="<?= $entry['id'] ?>">
                                        <button type="submit" name="delete_entry" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this entry?');">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                    <button class="btn btn-primary btn-sm edit-btn" 
                                            data-id="<?= $entry['id'] ?>" 
                                            data-title="<?= htmlspecialchars($entry['title']) ?>" 
                                            data-text="<?= htmlspecialchars($entry['text']) ?>" 
                                            data-lock="<?= htmlspecialchars($entry['lock_key'] ?? '') ?>" 
                                            data-language="<?= htmlspecialchars($entry['language'] ?? '') ?>" 
                                            data-slug="<?= htmlspecialchars($entry['slug'] ?? '') ?>" 
                                            data-visible="<?= $entry['is_visible'] ?>"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editModal">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Right Sidebar (Placeholder for now) -->
        <div class="col-12 col-lg-2 d-none d-lg-block sidebar-right">
            <div class="p-3">
                <h5>Quick Links</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item bg-transparent border-0"><a href="../index.php" class="text-decoration-none text-white">Go to Home</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="../register.php" class="text-decoration-none text-white">Register New User</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="entry_id" id="edit-id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit-title" class="form-label">Title</label>
                        <input type="text" name="title" id="edit-title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-text" class="form-label">Text</label>
                        <textarea name="text" id="edit-text" rows="4" class="form-control"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit-language" class="form-label">Language</label>
                        <select id="edit-language" name="language" class="form-select">
                            <option value="">None</option>
                            <option value="php">PHP</option>
                            <option value="javascript">JavaScript</option>
                            <option value="css">CSS</option>
                            <option value="html">HTML</option>
                            <option value="sql">SQL</option>
                            <option value="python">Python</option>
                            <option value="markup">Markup (XML/HTML)</option>
                            <option value="clike">C-like (C, C++, Java, C#)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit-lock" class="form-label">Lock Key</label>
                        <input type="text" name="lock_key" id="edit-lock" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="edit-slug" class="form-label">Slug</label>
                        <input type="text" name="slug" id="edit-slug" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="edit-visible" class="form-label">Visibility</label>
                        <select id="edit-visible" name="is_visible" class="form-select">
                            <option value="1">Visible</option>
                            <option value="0">Hidden</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit-file" class="form-label">Replace File (Optional)</label>
                        <input type="file" name="file" id="edit-file" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="edit_entry_modal" class="btn btn-success">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const title = this.dataset.title;
            const text = this.dataset.text;
            const lock = this.dataset.lock;
            const language = this.dataset.language;
            const slug = this.dataset.slug;
            const visible = this.dataset.visible;

            document.getElementById('edit-id').value = id;
            document.getElementById('edit-title').value = title;
            document.getElementById('edit-text').value = text;
            document.getElementById('edit-lock').value = lock;
            document.getElementById('edit-language').value = language;
            document.getElementById('edit-slug').value = slug;
            document.getElementById('edit-visible').value = visible;
        });
    });
</script>
<?php include '../footer.php'; ?>