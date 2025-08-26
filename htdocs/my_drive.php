<?php
/**
 * User's personal file storage (My Drive).
 * Allows uploading, viewing, and deleting personal files.
 */
include 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$notification = "";

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_file'])) {
    if (isset($_FILES['user_file']) && $_FILES['user_file']['error'] == 0) {
        $file = $_FILES['user_file'];
        $fileName = basename($file['name']);
        $fileSize = $file['size'];
        $user_dir = 'uploads/user_' . $user_id;

        if (!is_dir($user_dir)) {
            mkdir($user_dir, 0777, true);
        }

        $filePath = $user_dir . '/' . $fileName;

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            $insert_id = $db->insert("INSERT INTO user_files (user_id, file_name, file_path, file_size) VALUES (?, ?, ?, ?)", [$user_id, $fileName, $filePath, $fileSize], "issi");
            if ($insert_id) {
                $notification = "File uploaded successfully!";
            } else {
                $notification = "Error uploading file to database: " . $db->getConnection()->error;
            }
        } else {
            $notification = "Error moving uploaded file.";
        }
    } else {
        $notification = "Please select a file to upload.";
    }
}

// Handle file deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_file'])) {
    $file_id = (int)$_POST['file_id'];

    // Fetch file path to delete the file from storage
    $file_to_delete = $db->fetch("SELECT file_path FROM user_files WHERE id = ? AND user_id = ?", [$file_id, $user_id], "ii");

    if ($file_to_delete && file_exists($file_to_delete['file_path'])) {
        unlink($file_to_delete['file_path']);
    }

    // Delete file from database
    $affected_rows = $db->delete("DELETE FROM user_files WHERE id = ? AND user_id = ?", [$file_id, $user_id], "ii");
    if ($affected_rows > 0) {
        $notification = "File deleted successfully!";
    } else {
        $notification = "Error deleting file: " . $db->getConnection()->error;
    }
}

// Fetch user's files
$user_files = $db->fetchAll("SELECT * FROM user_files WHERE user_id = ? ORDER BY uploaded_at DESC", [$user_id], "i");

include 'header.php';
?>

<div class="main-wrapper">
    <div class="row g-0">
        <!-- Left Sidebar (User Panel Navigation) -->
        <div class="col-12 col-lg-2 d-none d-lg-block sidebar-left">
            <div class="p-3">
                <h5>User Panel</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item bg-transparent border-0"><a href="user_panel.php" class="text-decoration-none text-white"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="my_entries.php" class="text-decoration-none text-white"><i class="fas fa-list me-2"></i> My Entries</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="my_drive.php" class="text-decoration-none text-white"><i class="fas fa-hdd me-2"></i> My Drive</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="profile.php" class="text-decoration-none text-white"><i class="fas fa-user-cog me-2"></i> Profile Settings</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="logout.php" class="text-decoration-none text-white"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="col-12 col-lg-8 main-content-area">
            <div class="container py-4">
                <h1 class="text-center mb-4">My Drive</h1>

                <?php if ($notification): ?>
                    <div class="alert alert-info text-center"><?= $notification ?></div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-upload"></i> Upload New File
                    </div>
                    <div class="card-body">
                        <form action="my_drive.php" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="user_file" class="form-label">Select File</label>
                                <input type="file" id="user_file" name="user_file" class="form-control" required>
                            </div>
                            <button type="submit" name="upload_file" class="btn btn-primary w-100">Upload File</button>
                        </form>
                    </div>
                </div>

                <h2 class="mb-3">My Files</h2>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-primary">
                        <tr>
                            <th>File Name</th>
                            <th>File Size</th>
                            <th>Uploaded At</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($user_files as $file): ?>
                            <tr>
                                <td><?= htmlspecialchars($file['file_name']) ?></td>
                                <td><?= round($file['file_size'] / 1024, 2) ?> KB</td>
                                <td><?= $file['uploaded_at'] ?></td>
                                <td>
                                    <a href="<?= htmlspecialchars($file['file_path']) ?>" class="btn btn-primary btn-sm" download><i class="fas fa-download"></i> Download</a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this file?');">
                                        <input type="hidden" name="file_id" value="<?= $file['id'] ?>">
                                        <button type="submit" name="delete_file" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
