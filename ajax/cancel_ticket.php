<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/auth.php';

try {
    $auth->requireLogin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
        exit;
    }

    $ticketId = $_POST['ticket_id'] ?? '';
    if (!$ticketId) {
        echo json_encode(['success' => false, 'message' => 'Ticket ID gerekli']);
        exit;
    }

    // Get ticket with trip info
    $ticket = $db->fetchOne("
        SELECT t.*, tr.departure_time, tr.company_id
        FROM tickets t
        JOIN trips tr ON t.trip_id = tr.id
        WHERE t.id = ?
    ", [$ticketId]);

    if (!$ticket) {
        echo json_encode(['success' => false, 'message' => 'Bilet bulunamadı']);
        exit;
    }

    // Authorization: ticket sahibi ya da admin
    $currentUserId = $_SESSION['user_id'] ?? null;
    $isAdmin = $auth->isAdmin();
    if (!$isAdmin && $ticket['user_id'] !== $currentUserId) {
        echo json_encode(['success' => false, 'message' => 'Bu bileti iptal etmeye yetkiniz yok']);
        exit;
    }

    // Check status
    if ($ticket['status'] !== 'active') {
        echo json_encode(['success' => false, 'message' => 'Sadece aktif biletler iptal edilebilir']);
        exit;
    }

    // 1 saat kuralı: kalkış zamanından en az 1 saat önce iptal olmalı
    $departureTs = strtotime($ticket['departure_time']);
    if ($departureTs !== false) {
        if (($departureTs - time()) < 3600) {
            echo json_encode(['success' => false, 'message' => 'Kalkışa 1 saatten az kaldığı için iptal edilemez']);
            exit;
        }
    }

    // Transaction: update ticket, delete booked_seats, refund balance, update coupon used_count
    $db->query("BEGIN TRANSACTION");

    // Mark ticket cancelled
    $db->update('tickets', ['status' => 'cancelled'], 'id = ?', [$ticketId]);

    // Refund to user balance (add total_price)
    $ticketRow = $db->fetchOne("SELECT total_price, user_id, coupon_id FROM tickets WHERE id = ?", [$ticketId]);
    $totalPrice = floatval($ticketRow['total_price'] ?? 0);
    $userId = $ticketRow['user_id'];

    if ($totalPrice > 0) {
        // increase user balance
        $db->query("UPDATE users SET balance = COALESCE(balance,0) + ? WHERE id = ?", [$totalPrice, $userId]);
    }

    // Delete booked seats for this ticket
    $db->query("DELETE FROM booked_seats WHERE ticket_id = ?", [$ticketId]);

    // If coupon was used, decrement used_count (if >0)
    if (!empty($ticketRow['coupon_id'])) {
        $couponId = $ticketRow['coupon_id'];
        $db->query("UPDATE coupons SET used_count = CASE WHEN used_count > 0 THEN used_count - 1 ELSE 0 END WHERE id = ?", [$couponId]);
    }

    $db->query("COMMIT");

    echo json_encode(['success' => true, 'message' => 'Bilet başarıyla iptal edildi']);

} catch (Exception $e) {
    if (isset($db)) {
        $db->query("ROLLBACK");
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası: ' . $e->getMessage()]);
}
