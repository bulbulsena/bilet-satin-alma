<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/auth.php';

try {
    // Anyone logged-in or guest validation may be allowed — burada login gerektirmiyoruz
    // $auth->requireLogin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
        exit;
    }

    $code = trim($_POST['coupon_code'] ?? $_POST['code'] ?? '');

    if ($code === '') {
        echo json_encode(['success' => false, 'message' => 'Kupon kodu gerekli']);
        exit;
    }

    // Get coupon: accommodate old/new column names
    $coupon = $db->fetchOne("
        SELECT id, code,
               COALESCE(discount_type, CASE WHEN discount IS NOT NULL THEN 'percentage' ELSE 'fixed' END) AS discount_type,
               COALESCE(discount_value, discount) AS discount_value,
               COALESCE(max_uses, usage_limit) AS max_uses,
               COALESCE(used_count, 0) AS used_count,
               COALESCE(expires_at, expire_date) AS expires_at,
               COALESCE(is_active, 1) AS is_active,
               min_amount
        FROM coupons
        WHERE code = ?
        LIMIT 1
    ", [$code]);

    if (!$coupon) {
        echo json_encode(['success' => false, 'message' => 'Kupon bulunamadı']);
        exit;
    }

    // Active?
    if (!$coupon['is_active']) {
        echo json_encode(['success' => false, 'message' => 'Kupon pasif']);
        exit;
    }

    // Expiry?
    if (!empty($coupon['expires_at']) && strtotime($coupon['expires_at']) < time()) {
        echo json_encode(['success' => false, 'message' => 'Kuponun süresi dolmuş']);
        exit;
    }

    // Usage limit
    if (!empty($coupon['max_uses']) && intval($coupon['used_count']) >= intval($coupon['max_uses'])) {
        echo json_encode(['success' => false, 'message' => 'Kupon kullanım limiti dolmuş']);
        exit;
    }

    // Return coupon details (front-end hesaplama için)
    echo json_encode(['success' => true, 'coupon' => [
        'id' => $coupon['id'],
        'code' => $coupon['code'],
        'discount_type' => $coupon['discount_type'],
        'discount_value' => (float)$coupon['discount_value'],
        'min_amount' => $coupon['min_amount'] !== null ? (float)$coupon['min_amount'] : null,
        'max_uses' => $coupon['max_uses'] !== null ? (int)$coupon['max_uses'] : null,
        'used_count' => (int)$coupon['used_count'],
        'expires_at' => $coupon['expires_at'],
    ]]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası: ' . $e->getMessage()]);
}
