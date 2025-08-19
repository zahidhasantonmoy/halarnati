<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $content = $_POST['content'];
    $title = !empty($_POST['title']) ? htmlspecialchars($_POST['title']) : 'Untitled Paste';
    $language = htmlspecialchars($_POST['language']);
    $expiration = $_POST['expiration'];
    $password = $_POST['password'];

    // Handle expiration
    $expires_at = null;
    if ($expiration !== 'never') {
        $expires_at = date('Y-m-d H:i:s', strtotime('+ ' . str_replace(['m','h','d','w'], ['minutes','hours','days','weeks'], $expiration)));
    }

    // Handle password
    $password_hash = null;
    if (!empty($password)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
    }

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO entries (title, text, language, lock_key, expires_at, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $title, $content, $language, $password_hash, $expires_at);
    $stmt->execute();
    $newId = $stmt->insert_id;
    $stmt->close();

    // Redirect to the new paste
    header("Location: view.php?id=" . $newId);
    exit();

} else {
    // Redirect to homepage if accessed directly
    header("Location: index.php");
    exit();
}
?>