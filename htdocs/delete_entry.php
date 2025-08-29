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
    trigger_error("Invalid entry ID.", E_USER_ERROR);
}

// Fetch entry to get file_path before deleting
$entry = $db->fetch("SELECT file_path FROM entries WHERE id = ? AND user_id = ?", [$entry_id, $user_id], "ii");

if (!$entry) {
    trigger_error("Entry not found or you don't have permission to delete this entry.", E_USER_ERROR);
}

// Delete associated file if it exists
if ($entry['file_path'] && file_exists($entry['file_path'])) {
    unlink($entry['file_path']);
}

// Delete entry from database
$affected_rows = $db->delete("DELETE FROM entries WHERE id = ? AND user_id = ?", [$entry_id, $user_id], "ii");

if ($affected_rows > 0) {
    header("Location: my_entries.php?status=deleted");
    exit;
} else {
    trigger_error("Error deleting entry.", E_USER_ERROR);
}
?>
