<?php
/**
 * Main API Entry Point
 */
include '../config.php';

// Set CORS headers for API access
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include API controller
require_once 'includes/api/ApiController.php';

// Create API controller and process request
$api = new ApiController($db);
$api->processRequest();
?>