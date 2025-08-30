<?php
/**
 * Displays a single entry.
 * Handles unlocking of password-protected entries.
 */
include 'config.php'; // config.php now initializes $db
include 'includes/Parsedown.php';

// Include recommendation system
require_once 'includes/ContentRecommendation.php';
$recommendation = new ContentRecommendation($db);

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

// Record view for recommendations if user is logged in
if (isset($_SESSION['user_id'])) {
    $recommendation->recordView($_SESSION['user_id'], $entry['id']);
}

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
$comments = $db->fetchAll("SELECT c.*, u.username as mentioned_username FROM comments c LEFT JOIN users u ON c.user_id = u.id WHERE c.entry_id = ? ORDER BY c.created_at DESC", [$entry['id']], "i");

// Social sharing URLs
$entryUrl = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$twitterShareUrl = "https://twitter.com/intent/tweet?url=" . urlencode($entryUrl) . "&text=" . urlencode($entry['title']);
$facebookShareUrl = "https://www.facebook.com/sharer/sharer.php?u=" . urlencode($entryUrl);
$redditShareUrl = "https://www.reddit.com/submit?url=" . urlencode($entryUrl) . "&title=" . urlencode($entry['title']);

// SEO Meta Tags
$page_description = substr(strip_tags($entry['text']), 0, 160); // First 160 characters of the text
$page_keywords = $entry['title'] . ", " . $entry['type'] . ", " . ($entry['language'] ?? ''); // Example keywords

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
                                        <p>
                                            <?php
                                            $comment_text = htmlspecialchars($comment['comment']);
                                            // Replace @mentions with links to user profiles
                                            $comment_text = preg_replace_callback(
                                                '/@([a-zA-Z0-9_]+)/',
                                                function ($matches) use ($db) {
                                                    $mentioned_username = $matches[1];
                                                    $mentioned_user = $db->fetch("SELECT id FROM users WHERE username = ?", [$mentioned_username], "s");
                                                    if ($mentioned_user) {
                                                        return '<a href="profile.php?id=' . $mentioned_user['id'] . '">@' . htmlspecialchars($mentioned_username) . '</a>';
                                                    } else {
                                                        return '@' . htmlspecialchars($mentioned_username);
                                                    }
                                                },
                                                $comment_text
                                            );
                                            echo nl2br($comment_text);
                                            ?>
                                        </p>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recommendations Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-lightbulb"></i> Recommended Entries
                    </div>
                    <div class="card-body">
                        <?php
                        // Get similar entries
                        $similarEntries = $recommendation->getSimilarEntries($entry['id'], 3);
                        
                        // If not enough similar entries, get user recommendations
                        if (count($similarEntries) < 3 && isset($_SESSION['user_id'])) {
                            $userRecommendations = $recommendation->getRecommendationsForUser($_SESSION['user_id'], 3 - count($similarEntries));
                            $similarEntries = array_merge($similarEntries, $userRecommendations);
                        }
                        
                        // If still not enough, get popular entries
                        if (count($similarEntries) < 3) {
                            $popularEntries = $recommendation->getPopularEntries(3 - count($similarEntries));
                            $similarEntries = array_merge($similarEntries, $popularEntries);
                        }
                        
                        // Limit to 3 entries
                        $similarEntries = array_slice($similarEntries, 0, 3);
                        
                        if (empty($similarEntries)):
                        ?>
                            <p>No recommendations available at this time.</p>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($similarEntries as $recEntry): ?>
                                    <div class="col-md-4">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h6 class="card-title">
                                                    <?php
                                                    if ($recEntry['type'] === 'code') {
                                                        echo '<i class="fas fa-code"></i> ';
                                                    } elseif ($recEntry['type'] === 'file') {
                                                        echo '<i class="fas fa-file"></i> ';
                                                    } else {
                                                        echo '<i class="fas fa-align-left"></i> ';
                                                    }
                                                    echo htmlspecialchars(substr($recEntry['title'], 0, 30));
                                                    if (strlen($recEntry['title']) > 30) echo '...';
                                                    ?>
                                                </h6>
                                                <p class="card-text small text-muted">
                                                    <?= htmlspecialchars(substr($recEntry['text'], 0, 80)) ?>...
                                                </p>
                                                <a href="entry.php?<?= $recEntry['slug'] ? 'slug=' . htmlspecialchars($recEntry['slug']) : 'id=' . $recEntry['id'] ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    View Entry
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
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