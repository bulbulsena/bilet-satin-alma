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
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $contactPhone = $_POST['contact_phone'] ?? '';
    $contactEmail = $_POST['contact_email'] ?? '';
    $address = $_POST['address'] ?? '';
    
    // Validation
    if (!$name) {
        echo json_encode(['success' => false, 'message' => 'Firma adı gerekli.']);
        exit;
    }
    
    // Check if firm already exists
    $existingFirm = $db->fetchOne("SELECT id FROM firms WHERE name = ?", [$name]);
    if ($existingFirm) {
        echo json_encode(['success' => false, 'message' => 'Bu isimde bir firma zaten mevcut.']);
        exit;
    }
    
    try {
        $firmId = $db->insert('firms', [
            'name' => $name,
            'description' => $description,
            'contact_phone' => $contactPhone,
            'contact_email' => $contactEmail,
            'address' => $address
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Firma başarıyla eklendi.', 'firm_id' => $firmId]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Firma ekleme sırasında hata oluştu: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
}
?>
