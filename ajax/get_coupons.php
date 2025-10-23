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

if (!$auth->isAdmin() && !$auth->isCompanyAdmin()) {
    echo json_encode(['error' => 'Bu işlem için admin veya firma admin yetkisi gerekiyor']);
    exit;
}

// Get coupons with usage count
$coupons = $db->fetchAll("
    SELECT c.*, 
           COUNT(t.id) as used_count
    FROM coupons c
    LEFT JOIN tickets t ON c.id = t.coupon_id
    GROUP BY c.id
    ORDER BY c.created_at DESC
");

echo json_encode($coupons);
?>
