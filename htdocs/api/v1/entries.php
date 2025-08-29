<?php

// entries.php

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($id)) {
            // Get single entry
            $entry = $db->fetch("SELECT * FROM entries WHERE id = ?", [$id], "i");
            if ($entry) {
                http_response_code(200);
                echo json_encode($entry);
            } else {
                http_response_code(404);
                echo json_encode(['message' => 'Entry not found']);
            }
        } else {
            // Get all entries
            $limit = $_GET['limit'] ?? 10;
            $offset = $_GET['offset'] ?? 0;
            $entries = $db->fetchAll("SELECT * FROM entries LIMIT ? OFFSET ?", [$limit, $offset], "ii");
            http_response_code(200);
            echo json_encode($entries);
        }
        break;
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $title = $data['title'] ?? null;
        $text = $data['text'] ?? null;
        $type = $data['type'] ?? 'text';
        $language = $data['language'] ?? null;
        $file_path = $data['file_path'] ?? null;
        $thumbnail = $data['thumbnail'] ?? null;
        $lock_key = $data['lock_key'] ?? null;
        $slug = $data['slug'] ?? null;
        $user_id = $data['user_id'] ?? null;
        $category_id = $data['category_id'] ?? null;
        $is_markdown = $data['is_markdown'] ?? 0;

        if (!$title || !$text) {
            http_response_code(400);
            echo json_encode(['message' => 'Title and text are required']);
            break;
        }

        $insert_id = $db->insert("INSERT INTO entries (title, text, type, language, file_path, thumbnail, lock_key, slug, user_id, category_id, is_markdown) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [$title, $text, $type, $language, $file_path, $thumbnail, $lock_key, $slug, $user_id, $category_id, $is_markdown], "ssssssssiii");

        if ($insert_id) {
            http_response_code(201);
            echo json_encode(['message' => 'Entry created', 'id' => $insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to create entry']);
        }
        break;
    case 'PUT':
        if (!isset($id)) {
            http_response_code(400);
            echo json_encode(['message' => 'Entry ID is required']);
            break;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $title = $data['title'] ?? null;
        $text = $data['text'] ?? null;
        $type = $data['type'] ?? null;
        $language = $data['language'] ?? null;
        $file_path = $data['file_path'] ?? null;
        $thumbnail = $data['thumbnail'] ?? null;
        $lock_key = $data['lock_key'] ?? null;
        $slug = $data['slug'] ?? null;
        $user_id = $data['user_id'] ?? null;
        $category_id = $data['category_id'] ?? null;
        $is_markdown = $data['is_markdown'] ?? null;

        $update_fields = [];
        $params = [];
        $types = "";

        if ($title !== null) { $update_fields[] = "title = ?"; $params[] = $title; $types .= "s"; }
        if ($text !== null) { $update_fields[] = "text = ?"; $params[] = $text; $types .= "s"; }
        if ($type !== null) { $update_fields[] = "type = ?"; $params[] = $type; $types .= "s"; }
        if ($language !== null) { $update_fields[] = "language = ?"; $params[] = $language; $types .= "s"; }
        if ($file_path !== null) { $update_fields[] = "file_path = ?"; $params[] = $file_path; $types .= "s"; }
        if ($thumbnail !== null) { $update_fields[] = "thumbnail = ?"; $params[] = $thumbnail; $types .= "s"; }
        if ($lock_key !== null) { $update_fields[] = "lock_key = ?"; $params[] = $lock_key; $types .= "s"; }
        if ($slug !== null) { $update_fields[] = "slug = ?"; $params[] = $slug; $types .= "s"; }
        if ($user_id !== null) { $update_fields[] = "user_id = ?"; $params[] = $user_id; $types .= "i"; }
        if ($category_id !== null) { $update_fields[] = "category_id = ?"; $params[] = $category_id; $types .= "i"; }
        if ($is_markdown !== null) { $update_fields[] = "is_markdown = ?"; $params[] = $is_markdown; $types .= "i"; }

        if (empty($update_fields)) {
            http_response_code(400);
            echo json_encode(['message' => 'No fields to update']);
            break;
        }

        $sql = "UPDATE entries SET " . implode(", ", $update_fields) . " WHERE id = ?";
        $params[] = $id;
        $types .= "i";

        $affected_rows = $db->update($sql, $params, $types);

        if ($affected_rows > 0) {
            http_response_code(200);
            echo json_encode(['message' => 'Entry updated']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to update entry']);
        }
        break;
    case 'DELETE':
        if (!isset($id)) {
            http_response_code(400);
            echo json_encode(['message' => 'Entry ID is required']);
            break;
        }

        $affected_rows = $db->delete("DELETE FROM entries WHERE id = ?", [$id], "i");

        if ($affected_rows > 0) {
            http_response_code(200);
            echo json_encode(['message' => 'Entry deleted']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to delete entry']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
        break;
}
