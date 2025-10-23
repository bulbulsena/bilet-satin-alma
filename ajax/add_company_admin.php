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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $companyId = $_POST['company_id'] ?? '';
    
    // Validation
    if (!$fullName || !$email || !$password || !$companyId) {
        echo json_encode(['success' => false, 'message' => 'Tüm alanlar gerekli.']);
        exit;
    }
    
    // Check if email already exists
    $existingUser = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
    if ($existingUser) {
        echo json_encode(['success' => false, 'message' => 'Bu email adresi zaten kullanılıyor.']);
        exit;
    }
    
    try {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $userId = $db->insert('users', [
            'full_name' => $fullName,
            'email' => $email,
            'password' => $hashedPassword,
            'role' => 'company_admin',
            'company_id' => $companyId,
            'balance' => 0.00
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Firma admin başarıyla oluşturuldu.', 'user_id' => $userId]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Firma admin oluşturma sırasında hata oluştu: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
}
?>
