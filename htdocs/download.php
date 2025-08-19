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


if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Fetch entry from the database
    $stmt = $conn->prepare("SELECT file_path FROM entries WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $entry = $result->fetch_assoc();
    $stmt->close();

    if ($entry && !empty($entry['file_path']) && file_exists($entry['file_path'])) {
        // Increment download count
        $updateStmt = $conn->prepare("UPDATE entries SET download_count = download_count + 1 WHERE id = ?");
        $updateStmt->bind_param("i", $id);
        $updateStmt->execute();
        $updateStmt->close();

        // Serve the file
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($entry['file_path']) . '"');
        header('Content-Length: ' . filesize($entry['file_path']));
        readfile($entry['file_path']);
        exit;
    } else {
        die("File not found or invalid entry.");
    }
} else {
    die("Invalid request.");
}
?>