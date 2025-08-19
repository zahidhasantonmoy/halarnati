<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

require_once '../db.php';

if (isset($_GET['id'])) {
    $entryId = (int)$_GET['id'];

    // Get current visibility
    $stmt = $conn->prepare("SELECT is_visible FROM entries WHERE id = ?");
    $stmt->bind_param("i", $entryId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $currentVisibility = $result['is_visible'];
    $stmt->close();

    // Toggle visibility
    $newVisibility = $currentVisibility ? 0 : 1;
    $updateStmt = $conn->prepare("UPDATE entries SET is_visible = ? WHERE id = ?");
    $updateStmt->bind_param("ii", $newVisibility, $entryId);
    $updateStmt->execute();
    $updateStmt->close();
}

header("Location: admin_panel.php");
exit;
?>