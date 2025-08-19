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
$port = 3306;
$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission for editing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_edit'])) {
    $entryId = (int)$_POST['entry_id'];
    $title = htmlspecialchars($_POST['title']);
    $text = htmlspecialchars($_POST['text']);
    $lockKey = !empty($_POST['lock_key']) ? htmlspecialchars($_POST['lock_key']) : null;
    $file = $_FILES['file'];

    if ($file['name']) {
        $stmt = $conn->prepare("SELECT file_path FROM entries WHERE id = ?");
        $stmt->bind_param("i", $entryId);
        $stmt->execute();
        $oldFilePath = $stmt->get_result()->fetch_assoc()['file_path'];
        $stmt->close();

        if ($oldFilePath && file_exists('../' . $oldFilePath)) {
            unlink('../' . $oldFilePath);
        }

        $uploadsDir = '../uploads/';
        $newFilePath = $uploadsDir . basename($file['name']);
        move_uploaded_file($file['tmp_name'], $newFilePath);
        $dbFilePath = 'uploads/' . basename($file['name']);

        $updateStmt = $conn->prepare("UPDATE entries SET title = ?, text = ?, file_path = ?, lock_key = ? WHERE id = ?");
        $updateStmt->bind_param("ssssi", $title, $text, $dbFilePath, $lockKey, $entryId);
    } else {
        $updateStmt = $conn->prepare("UPDATE entries SET title = ?, text = ?, lock_key = ? WHERE id = ?");
        $updateStmt->bind_param("sssi", $title, $text, $lockKey, $entryId);
    }

    $updateStmt->execute();
    $updateStmt->close();

    header("Location: admin_panel.php?action=edited");
    exit();
}

// Check if ID is provided for GET request
if (!isset($_GET['id'])) {
    header("Location: admin_panel.php");
    exit;
}

$entryId = (int)$_GET['id'];

// Fetch the entry data
$stmt = $conn->prepare("SELECT * FROM entries WHERE id = ?");
$stmt->bind_param("i", $entryId);
$stmt->execute();
$entry = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$entry) {
    die("Entry not found.");
}

require_once '../header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card mt-4">
                <div class="card-header">
                    <h3><i class="fas fa-edit"></i> Edit Entry</h3>
                </div>
                <div class="card-body">
                    <form action="edit_entry.php?id=<?= $entry['id'] ?>" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="entry_id" value="<?= $entry['id'] ?>">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" id="title" name="title" class="form-control" value="<?= htmlspecialchars($entry['title']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="text" class="form-label">Text Content</label>
                            <textarea id="text" name="text" rows="5" class="form-control" required><?= htmlspecialchars($entry['text']) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="lock_key" class="form-label">Password (Optional)</label>
                            <input type="text" id="lock_key" name="lock_key" class="form-control" value="<?= htmlspecialchars($entry['lock_key']) ?>" placeholder="Leave blank for no password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Current File</label>
                            <p><?= $entry['file_path'] ? htmlspecialchars(basename($entry['file_path'])) : 'None' ?></p>
                            <label for="file" class="form-label">Upload New File (Optional)</label>
                            <input type="file" id="file" name="file" class="form-control">
                            <small class="form-text text-muted">Uploading a new file will replace the current one.</small>
                        </div>
                        <a href="admin_panel.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                        <button type="submit" name="submit_edit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../footer.php'; ?>