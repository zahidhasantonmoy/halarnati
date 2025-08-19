<?php
include 'config.php';

// Get entry ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$id) {
    die("Invalid entry ID.");
}

// Fetch entry from the database
$stmt = $conn->prepare("SELECT * FROM entries WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$entry = $result->fetch_assoc();
$stmt->close();

if (!$entry) {
    die("Entry not found.");
}

// Increment view count only if not already viewed in this session (optional, for more accurate counts)
// For simplicity, we'll increment on every page load for now.
$conn->query("UPDATE entries SET view_count = view_count + 1 WHERE id = " . $id);

// Handle unlock request
$isUnlocked = false;
$error = "";
if ($entry['lock_key']) { // Only check if there's a lock key set
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['unlock_key'])) {
        $unlockKey = htmlspecialchars($_POST['unlock_key']);
        if ($unlockKey === $entry['lock_key']) {
            $isUnlocked = true;
        } else {
            $error = "Incorrect unlock key.";
        }
    }
} else { // No lock key, so it's always unlocked
    $isUnlocked = true;
}

include 'header.php';
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-eye"></i> Viewing Entry: <?= htmlspecialchars($entry['title']) ?>
            </div>
            <div class="card-body">
                <p class="card-text entry-meta">
                    <small class="text-muted">Created: <?= $entry['created_at'] ?></small>
                    <span class="view-count"><i class="fas fa-eye"></i> Views: <?= $entry['view_count'] ?? 0 ?></span>
                </p>

                <?php if ($entry['lock_key'] && !$isUnlocked): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-lock"></i> This entry is password protected. Please enter the key to view.
                    </div>
                    <form method="post" class="mt-4">
                        <div class="mb-3">
                            <label for="unlock_key" class="form-label">Unlock Key:</label>
                            <input type="password" id="unlock_key" name="unlock_key" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-warning w-100"><i class="fas fa-lock-open"></i> Unlock</button>
                        <?php if ($error): ?>
                            <p class="text-danger mt-3"><?= $error ?></p>
                        <?php endif; ?>
                    </form>
                <?php else: ?>
                    <div class="entry-content">
                        <?php if ($entry['type'] === 'code'): ?>
                            <pre><code class="language-<?= htmlspecialchars($entry['language'] ?? 'markup') ?>"><?= htmlspecialchars($entry['text']) ?></code></pre>
                        <?php elseif ($entry['type'] === 'file'): ?>
                            <p><strong>Attached File:</strong> <?= htmlspecialchars(basename($entry['file_path'] ?? '')) ?></p>
                            <a href="uploads/<?= htmlspecialchars(basename($entry['file_path'] ?? '')) ?>" class="btn btn-secondary" download>
                                <i class="fas fa-download"></i> Download File
                            </a>
                        <?php else: // Default to text ?>
                            <p><?= nl2br(htmlspecialchars($entry['text'])) ?></p>
                        <?php endif; ?>
                    </div>
                    <button class="btn btn-info mt-2" onclick="copyToClipboard('entry-content-display')">
                        <i class="fas fa-copy"></i> Copy Content
                    </button>
                    <textarea id="entry-content-display" style="position: absolute; left: -9999px;" readonly><?= htmlspecialchars($entry['text']) ?></textarea>
                <?php endif; ?>

                <a href="index.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left"></i> Back to Home</a>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>