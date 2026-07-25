<?php
// api/config.php

// Database Configuration
// CHANGE THESE TO MATCH YOUR ACTUAL DATABASE DESIGN
$host = 'localhost';
$db_name = 'hotel_db'; // Your database name
$username = 'root'; // Your database username
$password = ''; // Your database password

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Setting default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // If the connection fails, send a JSON error so the frontend knows
    // NOTE: In production, do not expose raw error messages.
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}
?>
