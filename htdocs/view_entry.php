<?php
require_once 'db.php';

// Get entry ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$id) {
    // Redirect to home page if no ID is provided
    header('Location: index.php');
    exit();
}

// Fetch entry from the database
$stmt = $conn->prepare("SELECT * FROM entries WHERE id = ? AND is_visible = 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$entry = $result->fetch_assoc();
$stmt->close();

if (!$entry) {
    // Optionally, show a 'not found' message
    die("Entry not found.");
}

// Handle unlock request
$isUnlocked = false;
if (empty($entry['lock_key'])) {
    $isUnlocked = true;
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['unlock_key'])) {
    $unlockKey = htmlspecialchars($_POST['unlock_key']);
    if ($unlockKey === $entry['lock_key']) {
        $isUnlocked = true;
    } else {
        $error = "Incorrect unlock key.";
    }
}

require_once 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-file-alt"></i> <?= htmlspecialchars($entry['title']) ?></h3>
            </div>
            <div class="card-body">
                <p class="text-muted">Created: <?= date('F j, Y, g:i a', strtotime($entry['created_at'])) ?></p>
                <hr>

                <?php if (!$isUnlocked): ?>
                    <form method="post">
                        <div class="mb-3">
                            <label for="unlock_key" class="form-label"><i class="fas fa-key"></i> Enter Password to View</label>
                            <input type="password" id="unlock_key" name="unlock_key" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-lock-open"></i> Unlock</button>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger mt-3"><?= $error ?></div>
                        <?php endif; ?>
                    </form>
                <?php else: ?>
                    <div>
                        <h5>Content:</h5>
                        <textarea class="form-control bg-light" rows="10" readonly><?= htmlspecialchars($entry['text']) ?></textarea>
                        <button class="btn btn-outline-secondary mt-2" onclick="copyToClipboard('<?= htmlspecialchars(json_encode($entry['text'])) ?>')"><i class="fas fa-copy"></i> Copy Text</button>
                    </div>

                    <?php if ($entry['file_path']): ?>
                        <hr>
                        <h5><i class="fas fa-paperclip"></i> Attached File</h5>
                        <p><?= htmlspecialchars(basename($entry['file_path'])) ?></p>
                                                <a href="download.php?id=<?= $entry['id'] ?>" class="btn btn-success">
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="card-footer text-center">
                 <a href="index.php" class="btn btn-light"><i class="fas fa-arrow-left"></i> Back to All Entries</a>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    // The text is JSON encoded (e.g., "Hello\nWorld"), so we need to parse it.
    const decodedText = JSON.parse(text);
    navigator.clipboard.writeText(decodedText).then(() => {
        alert('Text copied to clipboard!');
    }).catch(err => {
        alert('Failed to copy text.');
    });
}
</script>

<?php require_once 'footer.php'; ?>
