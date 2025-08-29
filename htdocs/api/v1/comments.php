<?php

// comments.php

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['entry_id'])) {
            $entry_id = (int)$_GET['entry_id'];
            $comments = $db->fetchAll("SELECT * FROM comments WHERE entry_id = ? ORDER BY created_at DESC", [$entry_id], "i");
            http_response_code(200);
            echo json_encode($comments);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Entry ID is required to fetch comments']);
        }
        break;
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $entry_id = $data['entry_id'] ?? null;
        $name = $data['name'] ?? null;
        $email = $data['email'] ?? null;
        $comment_text = $data['comment'] ?? null;
        $user_id = $data['user_id'] ?? null;

        if (!$entry_id || !$name || !$email || !$comment_text) {
            http_response_code(400);
            echo json_encode(['message' => 'Entry ID, name, email, and comment are required']);
            break;
        }

        $insert_id = $db->insert("INSERT INTO comments (entry_id, user_id, name, email, comment) VALUES (?, ?, ?, ?, ?)", [$entry_id, $user_id, $name, $email, $comment_text], "iisss");

        if ($insert_id) {
            http_response_code(201);
            echo json_encode(['message' => 'Comment created', 'id' => $insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to create comment']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
        break;
}
