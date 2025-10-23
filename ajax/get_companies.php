<?php
// Set content type to JSON
header('Content-Type: application/json');

// Start session
session_start();

require_once __DIR__ . '/../config/auth.php';

// Get database connection
global $db;

// Check authentication
if (!$auth->isLoggedIn()) {
    echo json_encode(['error' => 'Giriş yapmanız gerekiyor']);
    exit;
}

if (!$auth->isAdmin()) {
    echo json_encode(['error' => 'Bu işlem için admin yetkisi gerekiyor']);
    exit;
}

// Get companies with trip counts
$companies = $db->fetchAll("
    SELECT bc.*, COUNT(t.id) as trip_count
    FROM bus_companies bc
    LEFT JOIN trips t ON bc.id = t.company_id
    GROUP BY bc.id
    ORDER BY bc.name
");

echo json_encode($companies);
?>
