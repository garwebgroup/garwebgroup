<?php
// api/index.php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Define a simple router
$action = $_GET['action'] ?? '';

if ($action === 'login' || $action === 'register') {
    require 'auth.php';
} elseif ($action === 'crud') {
    require 'records.php';
} elseif ($action === 'contact') {
    require 'contact.php';
} elseif ($action === 'csrf') {
    header('Content-Type: application/json');
    echo json_encode(['csrf_token' => 'dummy-csrf-token']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Action not found']);
}
?>
