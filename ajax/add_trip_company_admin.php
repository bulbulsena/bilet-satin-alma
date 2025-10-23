<?php
// Set content type to JSON
header('Content-Type: application/json');

// Start session
session_start();

require_once __DIR__ . '/../config/auth.php';

// Get database connection
global $db;

// Check authentication
if (!$auth->isLoggedIn()) {
    echo json_encode(['error' => 'Giriş yapmanız gerekiyor']);
    exit;
}

if (!$auth->isCompanyAdmin()) {
    echo json_encode(['error' => 'Bu işlem için firma admin yetkisi gerekiyor']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departureCity = $_POST['departure_city'] ?? '';
    $destinationCity = $_POST['destination_city'] ?? '';
    $departureTime = $_POST['departure_time'] ?? '';
    $arrivalTime = $_POST['arrival_time'] ?? '';
    $price = $_POST['price'] ?? '';
    $capacity = $_POST['capacity'] ?? '';
    
    // Validation
    if (!$departureCity || !$destinationCity || !$departureTime || !$arrivalTime || !$price || !$capacity) {
        echo json_encode(['success' => false, 'message' => 'Tüm alanlar gerekli.']);
        exit;
    }
    
    // Get company ID
    $company = $auth->getCompany();
    if (!$company) {
        echo json_encode(['success' => false, 'message' => 'Firma bilgisi bulunamadı.']);
        exit;
    }
    
    try {
        $tripId = $db->insert('trips', [
            'company_id' => $company['id'],
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