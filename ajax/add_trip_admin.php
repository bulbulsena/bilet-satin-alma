<?php
require_once __DIR__ . '/../config/auth.php';

// Get database connection
global $db;

// Only allow admin users
// Check authentication
if (!$auth->isLoggedIn()) {
    echo json_encode(['error' => 'Giriş yapmanız gerekiyor']);
    exit;
}

if (!$auth->isAdmin()) {
    echo json_encode(['error' => 'Bu işlem için admin yetkisi gerekiyor']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyId = $_POST['company_id'] ?? '';
    $departureCity = $_POST['departure_city'] ?? '';
    $destinationCity = $_POST['destination_city'] ?? '';
    $departureTime = $_POST['departure_time'] ?? '';
    $arrivalTime = $_POST['arrival_time'] ?? '';
    $price = $_POST['price'] ?? 0;
    $capacity = $_POST['capacity'] ?? 50;
    
    // Validation
    if (!$companyId || !$departureCity || !$destinationCity || !$departureTime || !$arrivalTime || !$price) {
        echo json_encode(['success' => false, 'message' => 'Lütfen tüm alanları doldurun.']);
        exit;
    }
    
    if ($departureCity === $destinationCity) {
        echo json_encode(['success' => false, 'message' => 'Kalkış ve varış şehirleri aynı olamaz.']);
        exit;
    }
    
    if ($price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Fiyat 0''dan büyük olmalıdır.']);
        exit;
    }
    
    if ($capacity <= 0 || $capacity > 100) {
        echo json_encode(['success' => false, 'message' => 'Kapasite 1-100 arasında olmalıdır.']);
        exit;
    }
    
    // Check if company exists
    $company = $db->fetchOne("SELECT id FROM bus_companies WHERE id = ?", [$companyId]);
    if (!$company) {
        echo json_encode(['success' => false, 'message' => 'Seçilen firma bulunamadı.']);
        exit;
    }
    
    try {
        $tripId = $db->insert('trips', [
            'company_id' => $companyId,
            'departure_city' => $departureCity,
            'destination_city' => $destinationCity,
            'departure_time' => $departureTime,
            'arrival_time' => $arrivalTime,
            'price' => $price,
            'capacity' => $capacity
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Sefer başarıyla eklendi.', 'trip_id' => $tripId]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Sefer ekleme sırasında hata oluştu: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
}
?>
