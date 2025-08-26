<?php
/**
 * Deletes an entry.
 * Only the owner of the entry can delete it.
 */
include 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$entry_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$entry_id) {
    die("Invalid entry ID.");
}

// Fetch entry to get file_path before deleting
$stmt = $conn->prepare("SELECT file_path FROM entries WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $entry_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$entry = $result->fetch_assoc();
$stmt->close();

if (!$entry) {
    die("Entry not found or you don't have permission to delete this entry.");
}

// Delete associated file if it exists
if ($entry['file_path'] && file_exists($entry['file_path'])) {
    unlink($entry['file_path']);
}

// Delete entry from database
$stmt = $conn->prepare("DELETE FROM entries WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $entry_id, $user_id);

if ($stmt->execute()) {
    header("Location: my_entries.php?status=deleted");
    exit;
} else {
    die("Error deleting entry: " . $stmt->error);
}
$stmt->close();
?>