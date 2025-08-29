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
    $entry_owner = $db->fetch("SELECT user_id, title FROM entries WHERE id = ?", [$entry_id], "i");

    if ($entry_owner && $entry_owner['user_id'] !== null && $entry_owner['user_id'] !== $user_id) {
        // Create notification for the entry owner
        $notification_message = "New comment on your entry \"" . htmlspecialchars($entry_owner['title']) . "\" by " . $name . ".";
        $db->insert("INSERT INTO notifications (user_id, message) VALUES (?, ?)", [$entry_owner['user_id'], $notification_message], "is");
    }

    // Detect and notify mentioned users
    preg_match_all('/@([a-zA-Z0-9_]+)/', $comment, $matches);
    $mentioned_usernames = array_unique($matches[1]);

    if (!empty($mentioned_usernames)) {
        foreach ($mentioned_usernames as $mentioned_username) {
            $mentioned_user = $db->fetch("SELECT id FROM users WHERE username = ?", [$mentioned_username], "s");
            if ($mentioned_user && $mentioned_user['id'] !== $user_id) {
                $mention_notification_message = "You were mentioned in a comment on entry \"" . htmlspecialchars($entry_owner['title']) . "\" by " . $name . ".";
                $db->insert("INSERT INTO notifications (user_id, message) VALUES (?, ?)", [$mentioned_user['id'], $mention_notification_message], "is");
            }
        }
    }

    header("Location: entry.php?id=" . $entry_id);
    exit;
}
