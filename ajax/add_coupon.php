<?php
// Set content type to JSON
header('Content-Type: application/json');

// Start session
session_start();

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';
    $discountType = $_POST['discount_type'] ?? '';
    $discountValue = $_POST['discount_value'] ?? '';
    $minAmount = $_POST['min_amount'] ?? '';
    $maxUses = $_POST['max_uses'] ?? '';
    $expiresAt = $_POST['expires_at'] ?? '';
    
    // Validation
    if (!$code || !$discountType || !$discountValue) {
        echo json_encode(['success' => false, 'message' => 'Kod, indirim türü ve değer gerekli.']);
        exit;
    }
    
    // Check if coupon code already exists
    $existingCoupon = $db->fetchOne("SELECT id FROM coupons WHERE code = ?", [$code]);
    if ($existingCoupon) {
        echo json_encode(['success' => false, 'message' => 'Bu kupon kodu zaten mevcut.']);
        exit;
    }
    
    try {
        $couponId = $db->insert('coupons', [
            'code' => strtoupper($code),
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'min_amount' => $minAmount ?: null,
            'max_uses' => $maxUses ?: null,
            'expires_at' => $expiresAt ?: null,
            'is_active' => 1
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Kupon başarıyla oluşturuldu.', 'coupon_id' => $couponId]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Kupon oluşturma sırasında hata oluştu: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
}
?>
