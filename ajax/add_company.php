<?php
// Set content type to JSON
header('Content-Type: application/json');

// Start session
session_start();

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

// Debug: POST verilerini logla
error_log('POST verileri: ' . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $logoPath = $_POST['logo_path'] ?? '';
    
    error_log('Name: ' . $name . ', LogoPath: ' . $logoPath);
    
    // Validation
    if (!$name) {
        echo json_encode(['success' => false, 'message' => 'Firma adı gerekli.']);
        exit;
    }
    
    // Check if company already exists
    $existingCompany = $db->fetchOne("SELECT id FROM bus_companies WHERE name = ?", [$name]);
    if ($existingCompany) {
        echo json_encode(['success' => false, 'message' => 'Bu isimde bir firma zaten mevcut.']);
        exit;
    }
    
    try {
        $companyId = $db->insert('bus_companies', [
            'name' => $name,
            'logo_path' => $logoPath
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Firma başarıyla eklendi.', 'company_id' => $companyId]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Firma ekleme sırasında hata oluştu: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
}
?>
