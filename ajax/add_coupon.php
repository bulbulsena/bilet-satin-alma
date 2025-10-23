<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/auth.php';

try {
    $auth->requireLogin();

    // Only admin or company_admin can create coupons from UI
    if (!$auth->isAdmin() && !$auth->isCompanyAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Yetkiniz yok']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
        exit;
    }

    $code = trim($_POST['code'] ?? '');
    $discount_type = $_POST['discount_type'] ?? 'percentage';
    $discount_value = floatval($_POST['discount_value'] ?? 0);
    $min_amount = isset($_POST['min_amount']) && $_POST['min_amount'] !== '' ? floatval($_POST['min_amount']) : null;
    $max_uses = isset($_POST['max_uses']) && $_POST['max_uses'] !== '' ? intval($_POST['max_uses']) : null;
    $expires_at = !empty($_POST['expires_at']) ? trim($_POST['expires_at']) : null;

    if ($code === '' || ($discount_value <= 0 && $discount_value !== 0.0)) {
        echo json_encode(['success' => false, 'message' => 'Kod ve indirim değeri gerekli']);
        exit;
    }

    // Kodun benzersizliği
    $exists = $db->fetchOne("SELECT id FROM coupons WHERE code = ?", [$code]);
    if ($exists) {
        echo json_encode(['success' => false, 'message' => 'Bu kupon kodu zaten mevcut']);
        exit;
    }

    $insertData = [
        'code' => $code,
        'discount_type' => in_array($discount_type, ['percentage','fixed']) ? $discount_type : 'percentage',
        'discount_value' => $discount_value,
        'min_amount' => $min_amount,
        'max_uses' => $max_uses,
        'used_count' => 0,
        'expires_at' => $expires_at,
        'is_active' => 1,
        'created_at' => date('Y-m-d H:i:s')
    ];

    $id = $db->insert('coupons', array_filter($insertData, function($v){ return $v !== ''; }));

    if ($id) {
        echo json_encode(['success' => true, 'message' => 'Kupon oluşturuldu', 'coupon_id' => $id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Kupon oluşturulamadı']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası: ' . $e->getMessage()]);
}
