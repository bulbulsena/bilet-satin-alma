<?php
require_once __DIR__ . '/../config/auth.php';

// Get database connection
global $db;

// Only allow firm admin users
// Check authentication
if (!$auth->isLoggedIn()) {
    echo json_encode(['error' => 'Giriş yapmanız gerekiyor']);
    exit;
}

if (!$auth->isAdmin()) {
    echo json_encode(['error' => 'Bu işlem için admin yetkisi gerekiyor']);
    exit;
}

$user = $auth->getCurrentUser();
$firmId = $user['firm_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tripId = $_POST['trip_id'] ?? 0;
    $departureCity = $_POST['departure_city'] ?? '';
    $arrivalCity = $_POST['arrival_city'] ?? '';
    $departureDate = $_POST['departure_date'] ?? '';
    $departureTime = $_POST['departure_time'] ?? '';
    $arrivalDate = $_POST['arrival_date'] ?? '';
    $arrivalTime = $_POST['arrival_time'] ?? '';
    $price = $_POST['price'] ?? 0;
    $totalSeats = $_POST['total_seats'] ?? 50;
    
    // Check if trip belongs to this firm
    $trip = $db->fetchOne("SELECT * FROM trips WHERE id = ? AND firm_id = ?", [$tripId, $firmId]);
    
    if (!$trip) {
        echo json_encode(['success' => false, 'message' => 'Sefer bulunamadı veya yetkiniz yok.']);
        exit;
    }
    
    // Check if there are active tickets
    $activeTickets = $db->fetchOne("SELECT COUNT(*) as count FROM tickets WHERE trip_id = ? AND status = 'active'", [$tripId]);
    
    if ($activeTickets['count'] > 0 && $totalSeats < $trip['total_seats']) {
        echo json_encode(['success' => false, 'message' => 'Aktif biletleri olan seferde koltuk sayısını azaltamazsınız.']);
        exit;
    }
    
    // Validation
    if (!$departureCity || !$arrivalCity || !$departureDate || !$departureTime || !$arrivalDate || !$arrivalTime || !$price) {
        echo json_encode(['success' => false, 'message' => 'Lütfen tüm alanları doldurun.']);
        exit;
    }
    
    if ($departureCity === $arrivalCity) {
        echo json_encode(['success' => false, 'message' => 'Kalkış ve varış şehirleri aynı olamaz.']);
        exit;
    }
    
    if ($price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Fiyat 0''dan büyük olmalıdır.']);
        exit;
    }
    
    if ($totalSeats <= 0 || $totalSeats > 100) {
        echo json_encode(['success' => false, 'message' => 'Koltuk sayısı 1-100 arasında olmalıdır.']);
        exit;
    }
    
    try {
        // Calculate new available seats
        $newAvailableSeats = $totalSeats - ($trip['total_seats'] - $trip['available_seats']);
        $newAvailableSeats = max(0, $newAvailableSeats);
        
        $db->update('trips', [
            'departure_city' => $departureCity,
            'arrival_city' => $arrivalCity,
            'departure_date' => $departureDate,
            'departure_time' => $departureTime,
            'arrival_date' => $arrivalDate,
            'arrival_time' => $arrivalTime,
            'price' => $price,
            'total_seats' => $totalSeats,
            'available_seats' => $newAvailableSeats
        ], 'id = ?', [$tripId]);
        
        echo json_encode(['success' => true, 'message' => 'Sefer başarıyla güncellendi.']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Sefer güncelleme sırasında hata oluştu: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
}
?>
