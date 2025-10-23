<?php
require_once __DIR__ . '/../config/auth.php';

// Get database connection
global $db;

header('Content-Type: application/json');

$couponCode = $_POST['coupon_code'] ?? '';

if (!$couponCode) {
    echo json_encode(['success' => false, 'message' => 'Kupon kodu gerekli.']);
    exit;
}

$coupon = $db->fetchOne("
    SELECT * FROM coupons 
    WHERE code = ? AND is_active = 1 
    AND (expires_at IS NULL OR expires_at >= datetime('now'))
    AND (max_uses IS NULL OR used_count < max_uses)
", [$couponCode]);

if (!$coupon) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz veya süresi dolmuş kupon kodu.']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Kupon kodu geçerli.',
    'coupon' => [
        'id' => $coupon['id'],
        'code' => $coupon['code'],
        'discount_type' => $coupon['discount_type'],
        'discount_value' => $coupon['discount_value'],
        'max_uses' => $coupon['max_uses'],
        'used_count' => $coupon['used_count']
    ]
]);
?>
