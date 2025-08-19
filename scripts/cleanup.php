<?php
require_once __DIR__ . '/../htdocs/config.php';

// Select expired pastes
$stmt = $conn->prepare("SELECT id, file_path FROM entries WHERE expires_at IS NOT NULL AND expires_at < NOW()");
$stmt->execute();
$result = $stmt->get_result();
$expired_pastes = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($expired_pastes as $paste) {
    // Delete associated file if any (not implemented in this version, but good practice)
    // if (!empty($paste['file_path']) && file_exists(__DIR__ . '/../htdocs/' . $paste['file_path'])) {
    //     unlink(__DIR__ . '/../htdocs/' . $paste['file_path']);
    // }

    // Delete paste from database
    $delete_stmt = $conn->prepare("DELETE FROM entries WHERE id = ?");
    $delete_stmt->bind_param("i", $paste['id']);
    $delete_stmt->execute();
    $delete_stmt->close();
}

// Log or output for cron job debugging
echo "Cleanup script ran at " . date('Y-m-d H:i:s') . " - " . count($expired_pastes) . " pastes cleaned.
";

$conn->close();
?>