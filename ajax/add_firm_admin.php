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
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $firmId = $_POST['firm_id'] ?? 0;
    
    // Validation
    if (!$firstName || !$lastName || !$username || !$email || !$password || !$firmId) {
        echo json_encode(['success' => false, 'message' => 'Lütfen tüm alanları doldurun.']);
        exit;
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Şifre en az 6 karakter olmalıdır.']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Geçerli bir email adresi girin.']);
        exit;
    }
    
    // Check if user already exists
    $existingUser = $db->fetchOne("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
    if ($existingUser) {
        echo json_encode(['success' => false, 'message' => 'Bu kullanıcı adı veya email zaten kullanılıyor.']);
        exit;
    }
    
    // Check if firm exists
    $firm = $db->fetchOne("SELECT id FROM firms WHERE id = ?", [$firmId]);
    if (!$firm) {
        echo json_encode(['success' => false, 'message' => 'Seçilen firma bulunamadı.']);
        exit;
    }
    
    try {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $userId = $db->insert('users', [
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role' => 'firm_admin',
            'firm_id' => $firmId,
            'credit' => 0.00
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Firma admin başarıyla eklendi.', 'user_id' => $userId]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Firma admin ekleme sırasında hata oluştu: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
}
?>
