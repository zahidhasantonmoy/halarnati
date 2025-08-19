<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_edit'])) {
    $entryId = (int)$_POST['entry_id'];
    $title = htmlspecialchars($_POST['title']);
    $text = htmlspecialchars($_POST['text']);
    $lockKey = !empty($_POST['lock_key']) ? htmlspecialchars($_POST['lock_key']) : null;
    $file = $_FILES['file'];

    // Check if a new file is uploaded
    if ($file['name']) {
        // A new file is being uploaded, so we replace the old one.

        // 1. Get the old file path to delete it
        $stmt = $conn->prepare("SELECT file_path FROM entries WHERE id = ?");
        $stmt->bind_param("i", $entryId);
        $stmt->execute();
        $oldFilePath = $stmt->get_result()->fetch_assoc()['file_path'];
        $stmt->close();

        // 2. Delete the old file if it exists
        if ($oldFilePath && file_exists('../' . $oldFilePath)) {
            unlink('../' . $oldFilePath);
        }

        // 3. Upload the new file
        $uploadsDir = '../uploads/';
        $newFilePath = $uploadsDir . basename($file['name']);
        move_uploaded_file($file['tmp_name'], $newFilePath);
        // Adjust path for database storage
        $dbFilePath = 'uploads/' . basename($file['name']);

        // 4. Update database with new file path
        $updateStmt = $conn->prepare("UPDATE entries SET title = ?, text = ?, file_path = ?, lock_key = ? WHERE id = ?");
        $updateStmt->bind_param("ssssi", $title, $text, $dbFilePath, $lockKey, $entryId);

    } else {
        // No new file, just update the text fields
        $updateStmt = $conn->prepare("UPDATE entries SET title = ?, text = ?, lock_key = ? WHERE id = ?");
        $updateStmt->bind_param("sssi", $title, $text, $lockKey, $entryId);
    }

    $updateStmt->execute();
    $updateStmt->close();

    header("Location: admin_panel.php?action=edited");
    exit();

} else {
    header("Location: admin_panel.php");
    exit();
}
?>