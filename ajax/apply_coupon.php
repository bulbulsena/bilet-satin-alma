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

// Only allow logged in users
$auth->requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $couponCode = $input['coupon_code'] ?? '';
    $tripId = $input['trip_id'] ?? '';
    
    if (!$couponCode || !$tripId) {
        echo json_encode(['success' => false, 'message' => 'Kupon kodu ve sefer ID gerekli.']);
        exit;
    }
    
    // Kuponu kontrol et
    $coupon = $db->fetchOne("
        SELECT * FROM coupons 
        WHERE code = ? AND is_active = 1 
        AND (expires_at IS NULL OR expires_at > datetime('now'))
    ", [strtoupper($couponCode)]);
    
    if (!$coupon) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz veya süresi dolmuş kupon.']);
        exit;
    }
    
    // Kullanım limitini kontrol et
    if ($coupon['max_uses']) {
        $usedCount = $db->fetchOne("
            SELECT COUNT(*) as count 
            FROM tickets 
            WHERE coupon_id = ?
        ", [$coupon['id']])['count'];
        
        if ($usedCount >= $coupon['max_uses']) {
            echo json_encode(['success' => false, 'message' => 'Bu kuponun kullanım limiti dolmuş.']);
            exit;
        }
    }
    
    // Minimum tutar kontrolü
    if ($coupon['min_amount']) {
        $trip = $db->fetchOne("SELECT price FROM trips WHERE id = ?", [$tripId]);
        if ($trip['price'] < $coupon['min_amount']) {
            echo json_encode(['success' => false, 'message' => 'Bu kupon için minimum tutar: ' . $coupon['min_amount'] . ' ₺']);
            exit;
        }
    }
    
    echo json_encode(['success' => true, 'coupon' => $coupon]);
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
}
?>
