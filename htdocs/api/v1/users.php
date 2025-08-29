<?php

// users.php

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($id)) {
            // Get single user
            $user = $db->fetch("SELECT id, username, email, is_admin, created_at, avatar FROM users WHERE id = ?", [$id], "i");
            if ($user) {
                http_response_code(200);
                echo json_encode($user);
            } else {
                http_response_code(404);
                echo json_encode(['message' => 'User not found']);
            }
        } else {
            // Get all users
            $limit = $_GET['limit'] ?? 10;
            $offset = $_GET['offset'] ?? 0;
            $users = $db->fetchAll("SELECT id, username, email, is_admin, created_at, avatar FROM users LIMIT ? OFFSET ?", [$limit, $offset], "ii");
            http_response_code(200);
            echo json_encode($users);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
        break;
}
