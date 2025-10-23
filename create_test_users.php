<?php
// Test kullanıcıları oluşturma scripti
require_once 'config/database.php';

try {
    echo "Test kullanıcıları oluşturuluyor...\n";
    
    // Admin kullanıcısı oluştur
    $adminExists = $db->fetchOne("SELECT id FROM users WHERE email = 'admin@bilet.com'");
    if (!$adminExists) {
        $adminId = $db->insert('users', [
            'email' => 'admin@bilet.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'full_name' => 'Sistem Yöneticisi',
            'role' => 'admin',
            'balance' => 0.00,
            'is_active' => 1
        ]);
        echo "Admin kullanıcısı oluşturuldu: admin@bilet.com / password\n";
    } else {
        echo "Admin kullanıcısı zaten mevcut.\n";
    }
    
    // Firma oluştur
    $companyExists = $db->fetchOne("SELECT id FROM bus_companies WHERE name = 'Metro Turizm'");
    if (!$companyExists) {
        $companyId = $db->insert('bus_companies', [
            'name' => 'Metro Turizm',
            'logo_path' => '/assets/logos/metro.png',
            'is_active' => 1
        ]);
        echo "Metro Turizm firması oluşturuldu.\n";
    } else {
        $companyId = $companyExists['id'];
        echo "Metro Turizm firması zaten mevcut.\n";
    }
    
    // Firma Admin kullanıcısı oluştur
    $firmAdminExists = $db->fetchOne("SELECT id FROM users WHERE email = 'metro@admin.com'");
    if (!$firmAdminExists) {
        $firmAdminId = $db->insert('users', [
            'email' => 'metro@admin.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'full_name' => 'Metro Admin',
            'role' => 'company_admin',
            'company_id' => $companyId,
            'balance' => 0.00,
            'is_active' => 1
        ]);
        echo "Firma Admin kullanıcısı oluşturuldu: metro@admin.com / password\n";
    } else {
        echo "Firma Admin kullanıcısı zaten mevcut.\n";
    }
    
    // Normal kullanıcı oluştur
    $userExists = $db->fetchOne("SELECT id FROM users WHERE email = 'user@test.com'");
    if (!$userExists) {
        $userId = $db->insert('users', [
            'email' => 'user@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'full_name' => 'Test Kullanıcı',
            'role' => 'user',
            'balance' => 1000.00,
            'is_active' => 1
        ]);
        echo "Normal kullanıcı oluşturuldu: user@test.com / password\n";
    } else {
        echo "Normal kullanıcı zaten mevcut.\n";
    }
    
    echo "\nTest kullanıcıları:\n";
    echo "1. Admin: admin@bilet.com / password\n";
    echo "2. Firma Admin: metro@admin.com / password\n";
    echo "3. Normal Kullanıcı: user@test.com / password\n";
    
    echo "\nTüm kullanıcılar başarıyla oluşturuldu!\n";
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>
