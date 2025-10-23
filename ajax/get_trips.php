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

// Get trips with company names
$trips = $db->fetchAll("
    SELECT t.*, bc.name as company_name
    FROM trips t
    JOIN bus_companies bc ON t.company_id = bc.id
    ORDER BY t.departure_time DESC
");

echo json_encode($trips);
?>
