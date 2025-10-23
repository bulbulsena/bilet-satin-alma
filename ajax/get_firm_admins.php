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

// Get firm admins with firm names
$firmAdmins = $db->fetchAll("
    SELECT u.*, f.name as firm_name
    FROM users u
    LEFT JOIN firms f ON u.firm_id = f.id
    WHERE u.role = 'firm_admin'
    ORDER BY u.username
");

echo json_encode($firmAdmins);
?>
