<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    log_activity($_SESSION['user_id'], 'Entry Deleted', 'Entry ID: ' . $entryId . ' and associated file deleted.');
}

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
    }
    elseif (!empty($language)) {
        $entry_type = 'code';
    }

    $stmt = $conn->prepare("UPDATE entries SET title = ?, text = ?, type = ?, language = ?, lock_key = ?, slug = ?, is_visible = ? WHERE id = ?");
    $stmt->bind_param("ssssssii", $title, $text, $entry_type, $language, $lockKey, $slug, $is_visible, $entryId);
    $stmt->execute();
    $stmt->close();

    $notification = "Entry successfully updated.";
    log_activity($_SESSION['user_id'], 'Entry Updated', 'Entry ID: ' . $entryId . ' updated. Title: ' . $title);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_visibility'])) {
    $entryId = (int)$_POST['entry_id'];
    $visibility = (int)$_POST['visibility']; // 0 = hidden, 1 = visible
    $stmt = $conn->prepare("UPDATE entries SET is_visible = ? WHERE id = ?");
    $stmt->bind_param("ii", $visibility, $entryId);
    $stmt->execute();
    $stmt->close();

    $notification = $visibility ? "Entry made visible." : "Entry hidden.";
    log_activity($_SESSION['user_id'], 'Entry Visibility Toggled', 'Entry ID: ' . $entryId . ' visibility set to: ' . ($visibility ? 'Visible' : 'Hidden'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_entries'])) {
    $result = $conn->query("SELECT id, title, text, type, language, file_path, lock_key, slug, user_id, created_at, view_count, is_visible FROM entries");
    $filename = "entries_" . date('Ymd') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="'. $filename . '"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Title', 'Text', 'Type', 'Language', 'File Path', 'Lock Key', 'Slug', 'User ID', 'Created At', 'View Count', 'Visibility']);
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
    fclose($output);
    log_activity($_SESSION['user_id'], 'Entries Exported', 'All entries exported to CSV.');
    exit;
}

// Handle Bulk Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_entries'])) {
    $selected_entries = $_POST['selected_entries']; // Array of entry IDs

    if (isset($_POST['bulk_delete'])) {
        foreach ($selected_entries as $entryId) {
            $entryId = (int)$entryId;
            // Fetch file path before deleting
            $stmt = $conn->prepare("SELECT file_path FROM entries WHERE id = ?");
            $stmt->bind_param("i", $entryId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $filePath = $result['file_path'];
            $stmt->close();

            if ($filePath && file_exists('../' . $filePath)) {
                unlink('../' . $filePath);
            }

            $stmt = $conn->prepare("DELETE FROM entries WHERE id = ?");
            $stmt->bind_param("i", $entryId);
            $stmt->execute();
            $stmt->close();
            log_activity($_SESSION['user_id'], 'Bulk Entry Deleted', 'Entry ID: ' . $entryId . ' and associated file deleted via bulk action.');
        }
        $notification = "Selected entries deleted successfully.";
    } elseif (isset($_POST['bulk_hide'])) {
        foreach ($selected_entries as $entryId) {
            $entryId = (int)$entryId;
            $stmt = $conn->prepare("UPDATE entries SET is_visible = 0 WHERE id = ?");
            $stmt->bind_param("i", $entryId);
            $stmt->execute();
            $stmt->close();
            log_activity($_SESSION['user_id'], 'Bulk Entry Hidden', 'Entry ID: ' . $entryId . ' hidden via bulk action.');
        }
        $notification = "Selected entries hidden successfully.";
    } elseif (isset($_POST['bulk_show'])) {
        foreach ($selected_entries as $entryId) {
            $entryId = (int)$entryId;
            $stmt = $conn->prepare("UPDATE entries SET is_visible = 1 WHERE id = ?");
            $stmt->bind_param("i", $entryId);
            $stmt->execute();
            $stmt->close();
            log_activity($_SESSION['user_id'], 'Bulk Entry Shown', 'Entry ID: ' . $entryId . ' shown via bulk action.');
        }
        $notification = "Selected entries shown successfully.";
    }
}

// Handle Import Entries (Needs significant re-work for new schema, skipping for now)
// if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_entries'])) {
//     $notification = "Import functionality needs to be updated for new schema.";
// }

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Filtering and Sorting parameters
$filterType = htmlspecialchars($_GET['filter_type'] ?? '');
$filterVisibility = isset($_GET['filter_visibility']) ? (int)$_GET['filter_visibility'] : '';
$sortBy = htmlspecialchars($_GET['sort_by'] ?? 'created_at');
$sortOrder = htmlspecialchars($_GET['sort_order'] ?? 'DESC');

// Validate sort_by and sort_order to prevent SQL injection
$allowedSortBy = ['created_at', 'title', 'view_count'];
if (!in_array($sortBy, $allowedSortBy)) {
    $sortBy = 'created_at';
}
$allowedSortOrder = ['ASC', 'DESC'];
if (!in_array($sortOrder, $allowedSortOrder)) {
    $sortOrder = 'DESC';
}

// Search functionality
$search = htmlspecialchars($_GET['search'] ?? '');

// Build dynamic query
$whereClauses = [];
$params = [];
$types = "";

if (!empty($search)) {
    $whereClauses[] = "(e.title LIKE ? OR e.text LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $types .= "ss";
}

if (!empty($filterType)) {
    $whereClauses[] = "e.type = ?";
    $params[] = $filterType;
    $types .= "s";
}

if ($filterVisibility !== '') {
    $whereClauses[] = "e.is_visible = ?";
    $params[] = $filterVisibility;
    $types .= "i";
}

$query = "SELECT e.id, e.title, e.type, e.language, e.file_path, e.lock_key, e.slug, e.user_id, e.created_at, e.view_count, e.is_visible, u.username FROM entries e LEFT JOIN users u ON e.user_id = u.id";

if (!empty($whereClauses)) {
    $query .= " WHERE " . implode(" AND ", $whereClauses);
}

$query .= " ORDER BY " . $sortBy . " " . $sortOrder . " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);

// Dynamically bind parameters
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$entries = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Total entry count for pagination (with filters)
$countQuery = "SELECT COUNT(*) AS total FROM entries e";
if (!empty($whereClauses)) {
    $countQuery .= " WHERE " . implode(" AND ", $whereClauses);
}

$countStmt = $conn->prepare($countQuery);

// Dynamically bind parameters for count query
$countParams = [];
$countTypes = "";

if (!empty($search)) {
    $countParams[] = '%' . $search . '%';
    $countParams[] = '%' . $search . '%';
    $countTypes .= "ss";
}

if (!empty($filterType)) {
    $countParams[] = $filterType;
    $countTypes .= "s";
}

if ($filterVisibility !== '') {
    $countParams[] = $filterVisibility;
    $countTypes .= "i";
}

if (!empty($countParams)) {
    $countStmt->bind_param($countTypes, ...$countParams);
}

$countStmt->execute();
$totalEntriesResult = $countStmt->get_result()->fetch_assoc();
$totalEntries = $totalEntriesResult['total'] ?? 0;
$totalPages = ceil($totalEntries / $limit);

// Fetch dashboard stats
$stats = [];
$queries = [
    'totalEntries' => "SELECT COUNT(*) AS total FROM entries",
    'totalVisible' => "SELECT COUNT(*) AS total FROM entries WHERE is_visible = 1",
    'totalHidden' => "SELECT COUNT(*) AS total FROM entries WHERE is_visible = 0",
    'totalViews' => "SELECT SUM(view_count) AS total FROM entries",
    'totalUsers' => "SELECT COUNT(*) AS total FROM users"
];

foreach ($queries as $key => $query) {
    $result = $conn->query($query);
    if ($result) {
        $stats[$key] = $result->fetch_assoc()['total'] ?? 0;
    } else {
        $stats[$key] = 0;
        $notification = "Error fetching dashboard stats: " . $conn->error;
    }
}

$totalEntries = $stats['totalEntries'];
$totalVisible = $stats['totalVisible'];
$totalHidden = $stats['totalHidden'];
$totalViews = $stats['totalViews'];
$totalUsers = $stats['totalUsers'];


include '../header.php'; // Use new header
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
                    <li class="list-group-item bg-transparent border-0"><a href="view_activity_logs.php" class="text-decoration-none text-white"><i class="fas fa-history me-2"></i> Activity Logs</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="error_log_viewer.php" class="text-decoration-none text-white"><i class="fas fa-bug me-2"></i> Error Log</a></li>
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
                            <div class="card-body"><h3 class="card-title"><?= $totalEntries ?? 0 ?></h3></div>
                        </div>
                    </div>
                    <div class="stat col-md-3 text-center">
                        <div class="card">
                            <div class="card-header">Visible Entries</div>
                            <div class="card-body"><h3 class="card-title"><?= $totalVisible ?? 0 ?></h3></div>
                        </div>
                    </div>
                    <div class="stat col-md-3 text-center">
                        <div class="card">
                            <div class="card-header">Hidden Entries</div>
                            <div class="card-body"><h3 class="card-title"><?= $totalHidden ?? 0 ?></h3></div>
                        </div>
                    </div>
                    <div class="stat col-md-3 text-center">
                        <div class="card">
                            <div class="card-header">Total Views</div>
                            <div class="card-body"><h3 class="card-title"><?= $totalViews ?? 0 ?></h3></div>
                        </div>
                    </div>
                    <div class="stat col-md-3 text-center">
                        <div class="card">
                            <div class="card-header">Total Users</div>
                            <div class="card-body"><h3 class="card-title"><?= $totalUsers ?? 0 ?></h3></div>
                        </div>
                    </div>
                </div>

                <h2 class="mb-3">Manage Entries</h2>
                <div class="card mb-4">
                    <div class="card-header">Entry Actions</div>
                    <div class="card-body">
                        <form class="mb-3" method="GET">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Search entries..." value="<?= $search ?? '' ?>">
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

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-filter"></i> Filter & Sort Entries
                    </div>
                    <div class="card-body">
                        <form action="admin_dashboard.php" method="GET">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="filter_type" class="form-label">Type</label>
                                    <select class="form-select" id="filter_type" name="filter_type">
                                        <option value="">All</option>
                                        <option value="text">Text</option>
                                        <option value="code">Code</option>
                                        <option value="file">File</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="filter_visibility" class="form-label">Visibility</label>
                                    <select class="form-select" id="filter_visibility" name="filter_visibility">
                                        <option value="">All</option>
                                        <option value="1">Visible</option>
                                        <option value="0">Hidden</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="sort_by" class="form-label">Sort By</label>
                                    <select class="form-select" id="sort_by" name="sort_by">
                                        <option value="created_at">Created At</option>
                                        <option value="title">Title</option>
                                        <option value="view_count">Views</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="sort_order" class="form-label">Order</label>
                                    <select class="form-select" id="sort_order" name="sort_order">
                                        <option value="DESC">Descending</option>
                                        <option value="ASC">Ascending</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Apply Filters</button>
                                    <a href="admin_dashboard.php" class="btn btn-secondary">Reset Filters</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <form method="POST" action="admin_dashboard.php">
                        <div class="mb-3">
                            <button type="submit" name="bulk_delete" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete selected entries?');"><i class="fas fa-trash"></i> Delete Selected</button>
                            <button type="submit" name="bulk_hide" class="btn btn-warning btn-sm"><i class="fas fa-eye-slash"></i> Hide Selected</button>
                            <button type="submit" name="bulk_show" class="btn btn-success btn-sm"><i class="fas fa-eye"></i> Show Selected</button>
                        </div>
                        <table class="table table-hover">
                            <thead class="table-primary">
                            <tr>
                                <th><input type="checkbox" id="selectAllEntries"></th>
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
                            <?php foreach ($entries as $entry):
                                // Ensure values are strings before passing to htmlspecialchars
                                $entryLockKey = (string)($entry['lock_key'] ?? '');
                                $entryLanguage = (string)($entry['language'] ?? '');
                                $entrySlug = (string)($entry['slug'] ?? '');

                                // Check for potential issues with data before displaying
                                $entryTitle = htmlspecialchars($entry['title']);
                                $entryType = htmlspecialchars($entry['type']);
                                $entryUser = htmlspecialchars($entry['username'] ?? 'Anonymous');
                                $entryVisibility = $entry['is_visible'] ? 'Visible' : 'Hidden';
                                $entryViewCount = $entry['view_count'] ?? 0;
                            ?>
                                <tr>
                                    <td><input type="checkbox" name="selected_entries[]" value="<?= $entry['id'] ?>"></td>
                                    <td><?= $entry['id'] ?></td>
                                    <td><?= $entryTitle ?></td>
                                    <td><?= $entryType ?></td>
                                    <td><?= $entryUser ?></td>
                                    <td><?= $entryVisibility ?></td>
                                    <td><?= $entryViewCount ?></td>
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
                                                data-lock="<?= htmlspecialchars($entryLockKey) ?>"
                                                data-language="<?= htmlspecialchars($entryLanguage) ?>"
                                                data-slug="<?= htmlspecialchars($entrySlug) ?>"
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
                    </form>
                </div>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++):
                            $pageClass = ($i === $page) ? 'active' : '';
                            $paginationLink = '?page=' . $i;

                            if (!empty($search)) {
                                $paginationLink .= '&search=' . htmlspecialchars($search);
                            }
                            if (!empty($filterType)) {
                                $paginationLink .= '&filter_type=' . htmlspecialchars($filterType);
                            }
                            if ($filterVisibility !== '') { // Check for empty string, not just empty()
                                $paginationLink .= '&filter_visibility=' . htmlspecialchars($filterVisibility);
                            }
                            if ($sortBy !== 'created_at') { // Only add if not default
                                $paginationLink .= '&sort_by=' . htmlspecialchars($sortBy);
                            }
                            if ($sortOrder !== 'DESC') { // Only add if not default
                                $paginationLink .= '&sort_order=' . htmlspecialchars($sortOrder);
                            }
                        ?>
                            <li class="page-item <?= $pageClass ?>">
                                <a class="page-link" href="<?= $paginationLink ?>"><?= $i ?></a>
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
                    <li class="list-group-item bg-transparent border-0"><a href="/index.php" class="text-decoration-none text-white">Go to Home</a></li>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAllCheckbox = document.getElementById('selectAllEntries');
        const entryCheckboxes = document.querySelectorAll('input[name="selected_entries[]"]');

        selectAllCheckbox.addEventListener('change', function() {
            entryCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
        });
    });
</script>
<?php include '../footer.php'; ?>