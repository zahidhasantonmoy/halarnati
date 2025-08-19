<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

require_once '../db.php';

if (isset($_GET['id'])) {
    $entryId = (int)$_GET['id'];

    // First, get the file path to delete the physical file
    $stmt = $conn->prepare("SELECT file_path FROM entries WHERE id = ?");
    $stmt->bind_param("i", $entryId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $filePath = $result['file_path'];
    $stmt->close();

    // Delete the physical file if it exists
    if ($filePath && file_exists('../' . $filePath)) {
        unlink('../' . $filePath);
    }

    // Delete the entry from the database
    $deleteStmt = $conn->prepare("DELETE FROM entries WHERE id = ?");
    $deleteStmt->bind_param("i", $entryId);
    $deleteStmt->execute();
    $deleteStmt->close();
}

header("Location: admin_panel.php?action=deleted");
exit;
?>