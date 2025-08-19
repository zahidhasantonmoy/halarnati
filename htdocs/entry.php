<?php
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_entry'])) {
    $title = htmlspecialchars($_POST['title']);
    $text = htmlspecialchars($_POST['text']);
    $file = $_FILES['file'];
    $lockKey = !empty($_POST['lock_key']) ? htmlspecialchars($_POST['lock_key']) : null;

    $filePath = null;

    // Handle file upload
    if ($file['name']) {
        $uploadsDir = 'uploads/';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0777, true);
        }
        $filePath = $uploadsDir . basename($file['name']);
        move_uploaded_file($file['tmp_name'], $filePath);
    }

    // Insert entry into the database
    $stmt = $conn->prepare("INSERT INTO entries (title, text, file_path, lock_key, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $title, $text, $filePath, $lockKey);
    $stmt->execute();
    $newEntryId = $stmt->insert_id;
    $stmt->close();

    // Redirect to the new entry's page
    header("Location: view_entry.php?id=" . $newEntryId . "&status=success");
    exit();
}

require_once 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-plus-circle"></i> Add a New Entry</h3>
            </div>
            <div class="card-body">
                <form action="entry.php" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" id="title" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="text" class="form-label">Text Content</label>
                        <textarea id="text" name="text" rows="5" class="form-control" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="file" class="form-label">Upload File (Optional)</label>
                        <input type="file" id="file" name="file" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="lock_key" class="form-label">Set a Password (Optional)</label>
                        <input type="password" id="lock_key" name="lock_key" class="form-control" placeholder="Leave blank for no password">
                    </div>
                    <button type="submit" name="submit_entry" class="btn btn-primary"><i class="fas fa-upload"></i> Submit Entry</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
