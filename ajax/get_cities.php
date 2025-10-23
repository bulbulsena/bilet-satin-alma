<?php
// Set content type to JSON
header('Content-Type: application/json');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/auth.php';

// Get database connection
global $db;

// Allow logged in users
$auth->requireLogin();

// Get cities
$cities = $db->fetchAll("SELECT * FROM cities ORDER BY name");

echo json_encode($cities);
?>
