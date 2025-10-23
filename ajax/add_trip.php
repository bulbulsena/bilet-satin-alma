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
    $departureCity = $_POST['departure_city'] ?? '';
    $arrivalCity = $_POST['arrival_city'] ?? '';
    $departureDate = $_POST['departure_date'] ?? '';
    $departureTime = $_POST['departure_time'] ?? '';
    $arrivalDate = $_POST['arrival_date'] ?? '';
    $arrivalTime = $_POST['arrival_time'] ?? '';
    $price = $_POST['price'] ?? 0;
    $totalSeats = $_POST['total_seats'] ?? 50;
    
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
        $tripId = $db->insert('trips', [
            'firm_id' => $firmId,
            'departure_city' => $departureCity,
            'arrival_city' => $arrivalCity,
            'departure_date' => $departureDate,
            'departure_time' => $departureTime,
            'arrival_date' => $arrivalDate,
            'arrival_time' => $arrivalTime,
            'price' => $price,
            'total_seats' => $totalSeats,
            'available_seats' => $totalSeats
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Sefer başarıyla eklendi.', 'trip_id' => $tripId]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Sefer ekleme sırasında hata oluştu: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
}
?>
