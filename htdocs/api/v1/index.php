<?php
header('Content-Type: application/json');

include '../../config.php';

// API Key Authentication
$api_key = $_SERVER['HTTP_X_API_KEY'] ?? '';

if (empty($api_key)) {
    http_response_code(401);
    echo json_encode(['message' => 'API Key is missing']);
    exit;
}

$user = $db->fetch("SELECT id, username, is_admin FROM users WHERE api_key = ?", [$api_key], "s");

if (!$user) {
    http_response_code(401);
    echo json_encode(['message' => 'Invalid API Key']);
    exit;
}

// Basic routing
$request_uri = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
$api_path = array_slice($request_uri, array_search('api', $request_uri) + 2);

$resource = $api_path[0] ?? null;
$id = $api_path[1] ?? null;

switch ($resource) {
    case 'entries':
        include 'entries.php';
        break;
    case 'users':
        include 'users.php';
        break;
    case 'comments':
        include 'comments.php';
        break;
    default:
        http_response_code(404);
        echo json_encode(['message' => 'Resource not found']);
        break;
}