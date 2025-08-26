<?php
/**
 * Edits an entry.
 * Only the owner of the entry can edit it.
 */
include 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$entry_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$notification = "";

if (!$entry_id) {
    die("Invalid entry ID.");
}

// Fetch the entry from the database
$entry = $db->fetch("SELECT * FROM entries WHERE id = ? AND user_id = ?", [$entry_id, $user_id], "ii");

if (!$entry) {
    die("Entry not found or you don't have permission to edit this entry.");
}

// Fetch all categories
$categories = $db->fetchAll("SELECT id, name FROM categories ORDER BY name ASC");

// Handle form submission for updating the entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_entry'])) {
    $title = htmlspecialchars($_POST['title']);
    $text = htmlspecialchars($_POST['text']);
    $language = htmlspecialchars($_POST['language'] ?? '');
    $lockKey = htmlspecialchars($_POST['lock_key'] ?? null);
    $customSlug = htmlspecialchars($_POST['custom_slug'] ?? '');
    $customSlug = preg_replace('/[^a-z0-9-]+/', '', strtolower($customSlug));
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : null;

    // Determine entry type
    $entry_type = 'text';
    if (!empty($_FILES['file']['name'])) {
        $entry_type = 'file';
    } elseif (!empty($language)) {
        $entry_type = 'code';
    }

    $filePath = $entry['file_path']; // Keep the old file path by default

    // Define allowed file types and max size
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']; // Add more as needed
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'doc', 'docx']; // Add more as needed
    $maxFileSize = 5 * 1024 * 1024; // 5 MB

    // Handle file upload if a new file is provided
    if ($entry_type === 'file' && !empty($_FILES['file']['name'])) {
        // Validate file size
        if ($_FILES['file']['size'] > $maxFileSize) {
            $notification = "File size exceeds the maximum allowed limit (5MB).";
            $entry_type = 'text'; // Revert to text type if file upload fails
        } else {
            // Validate file type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $_FILES['file']['tmp_name']);
            finfo_close($finfo);

            $fileExtension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

            if (!in_array($mimeType, $allowedMimeTypes) || !in_array($fileExtension, $allowedExtensions)) {
                $notification = "Invalid file type. Only images (JPG, PNG, GIF), PDF, and text/document files are allowed.";
                $entry_type = 'text'; // Revert to text type if file upload fails
            } else {
                // Delete the old file if it exists
                if ($filePath && file_exists($filePath)) {
                    unlink($filePath);
                }
                $uploadsDir = 'uploads/';
                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0777, true);
                }
                // Generate a unique filename
                $newFileName = uniqid('file_', true) . '.' . $fileExtension;
                $filePath = $uploadsDir . $newFileName;

                if (!move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
                    $notification = "Error uploading file.";
                    $entry_type = 'text'; // Revert to text type if file upload fails
                }
            }
        }
    }

    // Update the entry in the database
    $affected_rows = $db->update("UPDATE entries SET title = ?, text = ?, type = ?, file_path = ?, lock_key = ?, slug = ?, category_id = ? WHERE id = ? AND user_id = ?", [$title, $text, $entry_type, $filePath, $lockKey, $customSlug, $category_id, $entry_id, $user_id], "ssssssiii");
    
    if ($affected_rows > 0) {
        $notification = "Entry updated successfully!";
        // Re-fetch the entry to show the updated data
        $entry = $db->fetch("SELECT * FROM entries WHERE id = ?", [$entry_id], "i");
    } else {
        $notification = "Error updating entry: " . $db->getConnection()->error;
    }
}

include 'header.php';
?>

<div class="main-wrapper">
    <div class="row g-0 justify-content-center">
        <div class="col-12 col-lg-8 main-content-area">
            <div class="container py-4">
                <h1 class="text-center mb-4">Edit Entry</h1>
                <?php if ($notification): ?>
                    <div class="alert alert-info text-center"><?= $notification ?></div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-edit"></i> Editing: <?= htmlspecialchars($entry['title']) ?>
                    </div>
                    <div class="card-body">
                        <form action="edit_entry.php?id=<?= $entry_id ?>" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" id="title" name="title" class="form-control" value="<?= htmlspecialchars($entry['title']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select id="category" name="category_id" class="form-select">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>" <?= ($entry['category_id'] == $category['id']) ? 'selected' : '' ?>><?= htmlspecialchars($category['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            
                            <div class="mb-3" id="file_upload_field">
                                <label for="file" class="form-label">Replace File (Optional)</label>
                                <input type="file" id="file" name="file" class="form-control">
                                <?php if ($entry['file_path']): ?>
                                    <small class="form-text text-muted">Current file: <?= htmlspecialchars(basename($entry['file_path'])) ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="text" class="form-label">Content (Text or Code)</label>
                                <textarea id="text" name="text" rows="8" class="form-control"><?= htmlspecialchars($entry['text']) ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="lock_key" class="form-label">Password (Optional)</label>
                                <input type="text" id="lock_key" name="lock_key" class="form-control" value="<?= htmlspecialchars($entry['lock_key']) ?>" placeholder="Set a password to lock">
                            </div>
                            <div class="mb-3">
                                <label for="custom_slug" class="form-label">Custom Link (Optional)</label>
                                <input type="text" id="custom_slug" name="custom_slug" class="form-control" value="<?= htmlspecialchars($entry['slug']) ?>" placeholder="e.g., my-awesome-paste">
                            </div>
                            <button type="submit" name="update_entry" class="btn btn-primary w-100"><i class="fas fa-save"></i> Save Changes</button>
                        </form>
                        <a href="my_entries.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left"></i> Back to My Entries</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
