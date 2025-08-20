<?php
/**
 * Handles file downloads.
 * Prevents directory traversal attacks.
 */
if (isset($_GET['file'])) {
    $fileName = urldecode($_GET['file']);
    $filePath = 'uploads/' . basename($fileName);

    if (file_exists($filePath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    } else {
        echo "File not found.";
    }
} else {
    echo "Invalid request.";
}
?>