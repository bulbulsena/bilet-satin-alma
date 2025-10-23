<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/auth.php';

try {
    $auth->requireLogin();

    if (!$auth->isAdmin()) {
        // Güvenlik: sadece sistem admini asıl silme yetkisine sahip olsun
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Yetkiniz yok']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
        exit;
    }

    $couponId = $_POST['coupon_id'] ?? '';
    if (!$couponId) {
        echo json_encode(['success' => false, 'message' => 'Kupon ID gerekli']);
        exit;
    }

    // Eğer kupon biletlerde/ kullanıcı kupon ilişkilerinde kullanıldıysa silmeye izin verme
    $usedInTickets = $db->fetchOne("SELECT COUNT(*) as cnt FROM tickets WHERE coupon_id = ?", [$couponId])['cnt'] ?? 0;
    $usedInUserCoupons = $db->fetchOne("SELECT COUNT(*) as cnt FROM user_coupons WHERE coupon_id = ?", [$couponId])['cnt'] ?? 0;

    if ($usedInTickets > 0 || $usedInUserCoupons > 0) {
        echo json_encode(['success' => false, 'message' => 'Kupon kullanıldığı için silinemez. Pasifleştirmeyi deneyin.']);
        exit;
    }

    // Soft-delete / pasifleştir
    $db->update('coupons', ['is_active' => 0], 'id = ?', [$couponId]);

    echo json_encode(['success' => true, 'message' => 'Kupon pasifleştirildi (silindi)']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası: ' . $e->getMessage()]);
}
