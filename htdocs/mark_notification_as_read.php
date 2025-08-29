<?php
include 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['notification_id'])) {
        $notification_id = (int)$_POST['notification_id'];
        $db->update("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?", [$notification_id, $user_id], "ii");
    } elseif (isset($_POST['mark_all_as_read'])) {
        $db->update("UPDATE notifications SET is_read = TRUE WHERE user_id = ?", [$user_id], "i");
    }
}

header("Location: user_panel.php");
exit;
