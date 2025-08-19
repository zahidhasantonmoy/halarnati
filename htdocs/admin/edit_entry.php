<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

require_once '../db.php';

// Check if ID is provided
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
                    <form action="edit_entry_handler.php" method="post" enctype="multipart/form-data">
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
