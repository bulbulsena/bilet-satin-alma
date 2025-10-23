<?php
require_once __DIR__ . '/../config/auth.php';

// Get database connection
global $db;

// Only allow admin users
// Check authentication
if (!$auth->isLoggedIn()) {
    echo json_encode(['error' => 'Giriş yapmanız gerekiyor']);
    exit;
}

if (!$auth->isAdmin()) {
    echo json_encode(['error' => 'Bu işlem için admin yetkisi gerekiyor']);
    exit;
}

// Get firms with trip counts
$firms = $db->fetchAll("
    SELECT f.*, COUNT(t.id) as trip_count
    FROM firms f
    LEFT JOIN trips t ON f.id = t.firm_id
    GROUP BY f.id
    ORDER BY f.name
");

echo json_encode($firms);
?>
