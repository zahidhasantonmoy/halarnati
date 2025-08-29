<?php
/**
 * Displays a single entry.
 * Handles unlocking of password-protected entries.
 */
include 'config.php'; // config.php now initializes $db
include 'includes/Parsedown.php';

// Get entry ID or slug from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$slug = isset($_GET['slug']) ? htmlspecialchars($_GET['slug']) : null;

$entry = null;
if ($id) {
    $entry = $db->fetch("SELECT * FROM entries WHERE id = ?", [$id], "i");
} elseif ($slug) {
    $entry = $db->fetch("SELECT * FROM entries WHERE slug = ?", [$slug], "s");
}

if (!$entry) {
    trigger_error("Entry not found.", E_USER_ERROR);
}

// Increment view count
$db->update("UPDATE entries SET view_count = view_count + 1 WHERE id = ?", [$entry['id']], "i");

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

$Parsedown = new Parsedown();

// Fetch comments
$comments = $db->fetchAll("SELECT * FROM comments WHERE entry_id = ? ORDER BY created_at DESC", [$entry['id']], "i");

// Social sharing URLs
$entryUrl = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$twitterShareUrl = "https://twitter.com/intent/tweet?url=" . urlencode($entryUrl) . "&text=" . urlencode($entry['title']);
$facebookShareUrl = "https://www.facebook.com/sharer/sharer.php?u=" . urlencode($entryUrl);
$redditShareUrl = "https://www.reddit.com/submit?url=" . urlencode($entryUrl) . "&title=" . urlencode($entry['title']);

include 'header.php';
?>

<div class="main-wrapper">
    <div class="row g-0 justify-content-center">
        <div class="col-12 col-lg-8 main-content-area">
            <div class="container py-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-eye"></i> Viewing Entry: <?= htmlspecialchars($entry['title']) ?>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-3">
                            <p><strong>Instructions:</strong></p>
                            <ul>
                                <li>"Unlock Key" enter kore content unlock korun.</li>
                                <li>Text copy korar jonno "Copy Content" button click korun.</li>
                                <li>Download button diye file download korun.</li>
                            </ul>
                        </div>
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
                                    <a href="download.php?file=<?= urlencode(basename($entry['file_path'] ?? '')) ?>" class="btn btn-secondary">
                                        <i class="fas fa-download"></i> Download File
                                    </a>
                                <?php else: // Default to text ?>
                                    <?php if ($entry['is_markdown']): ?>
                                        <?= $Parsedown->text($entry['text']) ?>
                                    <?php else: ?>
                                        <p><?= nl2br(htmlspecialchars($entry['text'])) ?></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <button class="btn btn-info mt-2" onclick="copyToClipboard('entry-content-display')">
                                <i class="fas fa-copy"></i> Copy Content
                            </button>
                            <textarea id="entry-content-display" style="position: absolute; left: -9999px;" readonly><?= htmlspecialchars($entry['text']) ?></textarea>
                        <?php endif; ?>

                        <div class="mt-3">
                            <a href="<?= $twitterShareUrl ?>" target="_blank" class="btn btn-info"><i class="fab fa-twitter"></i> Share on Twitter</a>
                            <a href="<?= $facebookShareUrl ?>" target="_blank" class="btn btn-primary"><i class="fab fa-facebook"></i> Share on Facebook</a>
                            <a href="<?= $redditShareUrl ?>" target="_blank" class="btn btn-danger"><i class="fab fa-reddit"></i> Share on Reddit</a>
                        </div>

                        <a href="index.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left"></i> Back to Home</a>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-comments"></i> Comments
                    </div>
                    <div class="card-body">
                        <?php if (empty($comments)): ?>
                            <p>No comments yet.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($comments as $comment): ?>
                                    <li class="list-group-item">
                                        <strong><?= htmlspecialchars($comment['name']) ?></strong>
                                        <small class="text-muted"><?= $comment['created_at'] ?></small>
                                        <p><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-comment-dots"></i> Leave a Comment
                    </div>
                    <div class="card-body">
                        <form action="add_comment.php" method="post">
                            <input type="hidden" name="entry_id" value="<?= $entry['id'] ?>">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" id="name" name="name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="comment" class="form-label">Comment</label>
                                <textarea id="comment" name="comment" rows="4" class="form-control" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>