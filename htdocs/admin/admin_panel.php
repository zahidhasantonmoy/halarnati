<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

// Database connection
$host = 'sql203.infinityfree.com';
$user = 'if0_37868453';
$pass = 'Yho7V4gkz6bP1';
$db = 'if0_37868453_halarnati';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
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

    if ($filePath && file_exists($filePath)) {
        unlink($filePath); // Delete the file from storage
    }

    $stmt = $conn->prepare("DELETE FROM entries WHERE id = ?");
    $stmt->bind_param("i", $entryId);
    $stmt->execute();
    $stmt->close();

    $notification = "Entry and associated file successfully deleted.";
}

// Handle Edit Entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_entry'])) {
    $entryId = (int)$_POST['entry_id'];
    $title = htmlspecialchars($_POST['title']);
    $text = htmlspecialchars($_POST['text']);
    $lockKey = htmlspecialchars($_POST['lock_key'] ?? null);
    $file = $_FILES['file'];

    if ($file['name']) {
        $uploadsDir = 'uploads/';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0777, true);
        }
        $filePath = $uploadsDir . basename($file['name']);
        move_uploaded_file($file['tmp_name'], $filePath);

        $stmt = $conn->prepare("UPDATE entries SET title = ?, text = ?, file_path = ?, lock_key = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $title, $text, $filePath, $lockKey, $entryId);
    } else {
        $stmt = $conn->prepare("UPDATE entries SET title = ?, text = ?, lock_key = ? WHERE id = ?");
        $stmt->bind_param("sssi", $title, $text, $lockKey, $entryId);
    }
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

// Handle Export Entries
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_entries'])) {
    $result = $conn->query("SELECT id, title, text, created_at, is_visible, lock_key FROM entries");
    $filename = "entries_" . date('Ymd') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Title', 'Text', 'Created At', 'Visibility', 'Lock Key']);
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Handle Import Entries
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_entries'])) {
    $file = $_FILES['import_file'];
    if ($file['name']) {
        $fileHandle = fopen($file['tmp_name'], 'r');
        while (($row = fgetcsv($fileHandle)) !== false) {
            if (count($row) === 6) {
                $stmt = $conn->prepare("INSERT INTO entries (id, title, text, created_at, is_visible, lock_key) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE title=VALUES(title), text=VALUES(text), created_at=VALUES(created_at), is_visible=VALUES(is_visible), lock_key=VALUES(lock_key)");
                $stmt->bind_param("isssis", $row[0], $row[1], $row[2], $row[3], $row[4], $row[5]);
                $stmt->execute();
            }
        }
        fclose($fileHandle);
        $notification = "Entries successfully imported.";
    }
}

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$search = htmlspecialchars($_GET['search'] ?? '');
$query = "SELECT * FROM entries WHERE title LIKE ? OR text LIKE ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$likeSearch = '%' . $search . '%';
$stmt->bind_param("ssii", $likeSearch, $likeSearch, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
$entries = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Total entry count for pagination
$totalEntries = $conn->query("SELECT COUNT(*) AS total FROM entries")->fetch_assoc()['total'];
$totalPages = ceil($totalEntries / $limit);

// Fetch dashboard stats
$totalVisible = $conn->query("SELECT COUNT(*) AS total FROM entries WHERE is_visible = 1")->fetch_assoc()['total'];
$totalHidden = $conn->query("SELECT COUNT(*) AS total FROM entries WHERE is_visible = 0")->fetch_assoc()['total'];
$totalDownloads = $conn->query("SELECT SUM(download_count) AS total FROM entries")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f9f9f9, #e3f2fd);
            min-height: 100vh;
        }
        .navbar {
            background: #007bff;
            color: white;
            padding: 10px 20px;
        }
        .navbar a {
            color: white;
            text-decoration: none;
        }
        h1 {
            text-align: center;
            color: #007bff;
            font-weight: bold;
            margin-top: 20px;
        }
       .stats {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
        }
        .stat {
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            background: white;
        }
        .table-container {
            margin-top: 20px;
        }
        .table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .btn {
            border-radius: 20px;
            transition: transform 0.2s ease, background-color 0.2s ease;
        }
        .btn:hover {
            transform: scale(1.1);
            background-color: #0056b3 !important;
        }
        .pagination a {
            text-decoration: none;
        }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="container-fluid">
        <span>Admin Panel</span>
        <a href="logout.php" class="float-end">Logout</a>
    </div>
</nav>
<div class="container">
    <h1>Manage Entries</h1>
    <?php if ($notification): ?>
        <div class="alert alert-success"><?= $notification ?></div>
    <?php endif; ?>
    <div class="stats">
        <div class="stat">
            <h5>Total Entries</h5>
            <p><?= $totalEntries ?></p>
        </div>
        <div class="stat">
            <h5>Visible Entries</h5>
            <p><?= $totalVisible ?></p>
        </div>
        <div class="stat">
            <h5>Hidden Entries</h5>
            <p><?= $totalHidden ?></p>
        </div>
        <div class="stat">
            <h5>Total Downloads</h5>
            <p><?= $totalDownloads ?></p>
        </div>
    </div>
    <form class="mb-3" method="GET">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Search entries..." value="<?= $search ?>">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
        </div>
    </form>
    <form method="POST" enctype="multipart/form-data">
        <button type="submit" name="export_entries" class="btn btn-success mb-3"><i class="fas fa-file-export"></i> Export All Entries</button>
        <input type="file" name="import_file" class="form-control mb-3">
        <button type="submit" name="import_entries" class="btn btn-warning"><i class="fas fa-file-import"></i> Import Entries</button>
    </form>
    <div class="table-container">
        <table class="table table-hover">
            <thead class="table-primary">
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Visibility</th>
                <th>Download Count</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($entries as $entry): ?>
                <tr>
                    <td><?= $entry['id'] ?></td>
                    <td><?= htmlspecialchars($entry['title']) ?></td>
                    <td><?= $entry['is_visible'] ? 'Visible' : 'Hidden' ?></td>
                    <td><?= $entry['download_count'] ?? 0 ?></td>
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
                            <button type="submit" name="delete_entry" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                        <button class="btn btn-primary btn-sm edit-btn" 
                                data-id="<?= $entry['id'] ?>" 
                                data-title="<?= htmlspecialchars($entry['title']) ?>" 
                                data-text="<?= htmlspecialchars($entry['text']) ?>" 
                                data-lock="<?= htmlspecialchars($entry['lock_key'] ?? '') ?>" 
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
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
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
                        <textarea name="text" id="edit-text" rows="4" class="form-control" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit-lock" class="form-label">Lock Key</label>
                        <input type="text" name="lock_key" id="edit-lock" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="edit-file" class="form-label">Replace File (Optional)</label>
                        <input type="file" name="file" id="edit-file" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="edit_entry" class="btn btn-success">Save Changes</button>
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

            document.getElementById('edit-id').value = id;
            document.getElementById('edit-title').value = title;
            document.getElementById('edit-text').value = text;
            document.getElementById('edit-lock').value = lock;
        });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>