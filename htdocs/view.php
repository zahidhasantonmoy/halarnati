<?php
require_once 'config.php';

$paste = null;
$error = null;
$is_locked = false;
$show_content = false;

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Fetch paste data
    $stmt = $conn->prepare("SELECT * FROM entries WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $paste = $result->fetch_assoc();
    $stmt->close();

    if ($paste) {
        // Check for expiration
        if ($paste['expires_at'] && strtotime($paste['expires_at']) < time()) {
            $error = "This paste has expired.";
        } else {
            // Check for password protection
            if (!empty($paste['lock_key'])) {
                $is_locked = true;
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
                    if (password_verify($_POST['password'], $paste['lock_key'])) {
                        $show_content = true;
                    } else {
                        $error = "Incorrect password.";
                    }
                }
            } else {
                $show_content = true;
            }

            // Increment view count if content is shown
            if ($show_content) {
                $update_stmt = $conn->prepare("UPDATE entries SET view_count = view_count + 1 WHERE id = ?");
                $update_stmt->bind_param("i", $id);
                $update_stmt->execute();
                $update_stmt->close();
            }
        }
    } else {
        $error = "Paste not found.";
    }
} else {
    $error = "No paste ID provided.";
}

require_once 'header.php';
?>

<div class="paste-container">
    <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php elseif ($paste): ?>
        <h2 class="paste-title"><?= htmlspecialchars($paste['title']) ?></h2>
        <div class="paste-meta">
            <div class="meta-item"><i class="fas fa-calendar-alt"></i> Created: <strong><?= date('M d, Y H:i', strtotime($paste['created_at'])) ?></strong></div>
            <div class="meta-item"><i class="fas fa-language"></i> Language: <strong><?= htmlspecialchars(ucfirst($paste['language'])) ?></strong></div>
            <div class="meta-item"><i class="fas fa-eye"></i> Views: <strong><?= $paste['view_count'] + ($show_content ? 1 : 0) ?></strong></div>
            <?php if ($paste['expires_at']): ?>
                <div class="meta-item"><i class="fas fa-hourglass-end"></i> Expires: <strong><?= date('M d, Y H:i', strtotime($paste['expires_at'])) ?></strong></div>
            <?php endif; ?>
            <?php if ($is_locked): ?>
                <div class="meta-item"><i class="fas fa-lock"></i> Password Protected</div>
            <?php endif; ?>
        </div>

        <?php if ($is_locked && !$show_content): ?>
            <div class="password-form-container">
                <form method="post">
                    <div class="form-group">
                        <label for="password"><i class="fas fa-key"></i> Enter Password to View</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn-submit"><i class="fas fa-unlock"></i> Unlock Paste</button>
                </form>
            </div>
        <?php elseif ($show_content): ?>
            <div class="code-block-container">
                <button class="copy-button" data-clipboard-target="#paste-content"><i class="fas fa-copy"></i> Copy</button>
                <pre><code id="paste-content" class="language-<?= htmlspecialchars($paste['language']) ?>"><?= htmlspecialchars($paste['text']) ?></code></pre>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>
