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
// Check authentication
if (!$auth->isLoggedIn()) {
    echo json_encode(['error' => 'Giriş yapmanız gerekiyor']);
    exit;
}

if (!$auth->isUser() && !$auth->isAdmin()) {
    echo json_encode(['error' => 'Bu işlem için kullanıcı yetkisi gerekiyor']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tripId = $_POST['trip_id'] ?? '';
    $passengerName = $_POST['passenger_name'] ?? '';
    $passengerEmail = $_POST['passenger_email'] ?? '';
    $passengerPhone = $_POST['passenger_phone'] ?? '';
    $selectedSeats = json_decode($_POST['selected_seats'] ?? '[]', true);
    $couponId = $_POST['coupon_id'] ?? null;
    
    // Validation
    if (!$tripId || !$passengerName || !$passengerEmail || !$passengerPhone || empty($selectedSeats)) {
        echo json_encode(['success' => false, 'message' => 'Lütfen tüm alanları doldurun ve koltuk seçin.']);
        exit;
    }
    
    // Sefer kontrolü
    $trip = $db->fetchOne("SELECT * FROM trips WHERE id = ?", [$tripId]);
    if (!$trip) {
        echo json_encode(['success' => false, 'message' => 'Sefer bulunamadı.']);
        exit;
    }
    
    // Koltukların müsait olup olmadığını kontrol et
    foreach ($selectedSeats as $seatNumber) {
        if ($seatNumber < 1 || $seatNumber > $trip['capacity']) {
            echo json_encode(['success' => false, 'message' => 'Geçersiz koltuk numarası.']);
            exit;
        }
        
        $existingReservation = $db->fetchOne("
            SELECT id FROM booked_seats 
            WHERE trip_id = ? AND seat_number = ?
        ", [$tripId, $seatNumber]);
        
        if ($existingReservation) {
            echo json_encode(['success' => false, 'message' => 'Koltuk ' . $seatNumber . ' zaten rezerve edilmiş.']);
            exit;
        }
    }
    
    // Kupon kontrolü
    $coupon = null;
    if ($couponId) {
        $coupon = $db->fetchOne("SELECT * FROM coupons WHERE id = ? AND is_active = 1", [$couponId]);
        if (!$coupon) {
            echo json_encode(['success' => false, 'message' => 'Geçersiz kupon.']);
            exit;
        }
    }
    
    try {
        $db->query("PRAGMA foreign_keys = OFF");
        $db->query("BEGIN TRANSACTION");
        
        // Fiyat hesaplama
        $basePrice = $trip['price'] * count($selectedSeats);
        $discountAmount = 0;
        
        if ($coupon) {
            if ($coupon['discount_type'] === 'percentage') {
                $discountAmount = ($basePrice * $coupon['discount_value']) / 100;
            } else {
                $discountAmount = $coupon['discount_value'];
            }
        }
        
        $totalPrice = max(0, $basePrice - $discountAmount);
        
        // Bilet oluştur
        $ticketId = $db->insert('tickets', [
            'user_id' => $_SESSION['user_id'],
            'trip_id' => $tripId,
            'total_price' => $totalPrice,
            'coupon_id' => $couponId,
            'status' => 'active'
        ]);
        
        // Koltukları rezerve et
        foreach ($selectedSeats as $seatNumber) {
            $db->insert('booked_seats', [
                'trip_id' => $tripId,
                'ticket_id' => $ticketId,
                'seat_number' => $seatNumber
            ]);
        }
        
        $db->query("COMMIT");
        $db->query("PRAGMA foreign_keys = ON");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Bilet başarıyla satın alındı.',
            'ticket_id' => $ticketId,
            'total_price' => $totalPrice
        ]);
        
    } catch (Exception $e) {
        $db->query("ROLLBACK");
        $db->query("PRAGMA foreign_keys = ON");
        echo json_encode(['success' => false, 'message' => 'Bilet satın alma sırasında hata oluştu: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
}
?>
