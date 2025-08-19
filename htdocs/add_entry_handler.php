<?php
require_once 'db.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_entry'])) {
    $title = htmlspecialchars($_POST['title']);
    $text = htmlspecialchars($_POST['text']);
    $file = $_FILES['file'];
    $lockKey = !empty($_POST['lock_key']) ? htmlspecialchars($_POST['lock_key']) : null;

    $filePath = null;

    // Handle file upload
    if ($file['name']) {
        // IMPORTANT: You should create this directory if it doesn't exist
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
} else {
    // If accessed directly, redirect to the form
    header('Location: entry.php');
    exit();
}
?>
