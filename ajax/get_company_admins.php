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

// Get company admins
$admins = $db->fetchAll("
    SELECT u.*, bc.name as company_name
    FROM users u
    LEFT JOIN bus_companies bc ON u.company_id = bc.id
    WHERE u.role = 'company_admin'
    ORDER BY u.full_name
");

echo json_encode($admins);
?>
