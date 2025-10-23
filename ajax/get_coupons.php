<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/auth.php';

try {
    $auth->requireLogin();

    // Admin veya Firma Admin erişimi kabul ediliyor (UI her iki panele kupon yönetimi koymuş)
    if (!$auth->isAdmin() && !$auth->isCompanyAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Yetkiniz yok']);
        exit;
    }

    // Basit: tüm kuponları getir
    $coupons = $db->fetchAll("SELECT id, code,
                                   COALESCE(discount_type, CASE WHEN discount IS NOT NULL THEN 'percentage' ELSE 'fixed' END) AS discount_type,
                                   COALESCE(discount_value, discount) AS discount_value,
                                   min_amount, max_uses, used_count, expires_at, is_active, created_at
                            FROM coupons
                            ORDER BY created_at DESC");

    echo json_encode(['success' => true, 'coupons' => $coupons]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası: ' . $e->getMessage()]);
}
