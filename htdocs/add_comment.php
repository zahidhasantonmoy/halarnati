<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entry_id = (int)$_POST['entry_id'];
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $comment = htmlspecialchars($_POST['comment']);
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    if (empty($name) || empty($email) || empty($comment)) {
        // Handle error
        header("Location: entry.php?id=" . $entry_id . "&error=empty_fields");
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Handle error
        header("Location: entry.php?id=" . $entry_id . "&error=invalid_email");
        exit;
    }

    $db->insert("INSERT INTO comments (entry_id, user_id, name, email, comment) VALUES (?, ?, ?, ?, ?)", [$entry_id, $user_id, $name, $email, $comment], "iisss");

    // Get entry owner ID
    $entry_owner = $db->fetch("SELECT user_id FROM entries WHERE id = ?", [$entry_id], "i");

    if ($entry_owner && $entry_owner['user_id'] !== null && $entry_owner['user_id'] !== $user_id) {
        // Create notification for the entry owner
        $notification_message = "New comment on your entry \"" . $entry['title'] . "\" by " . $name . ".";
        $db->insert("INSERT INTO notifications (user_id, message) VALUES (?, ?)", [$entry_owner['user_id'], $notification_message], "is");
    }

    header("Location: entry.php?id=" . $entry_id);
    exit;
}