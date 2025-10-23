<?php
// Debug script - Login sorununu tespit etmek için
require_once 'config/database.php';

echo "<h2>Veritabanı Debug Bilgileri</h2>";

try {
    // Veritabanı bağlantısını test et
    echo "<p>✓ Veritabanı bağlantısı başarılı</p>";
    
    // Kullanıcıları listele
    $users = $db->fetchAll("SELECT id, email, full_name, role, is_active FROM users");
    echo "<h3>Mevcut Kullanıcılar:</h3>";
    if (empty($users)) {
        echo "<p style='color: red;'>❌ Hiç kullanıcı bulunamadı!</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Email</th><th>Ad</th><th>Rol</th><th>Aktif</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td>" . ($user['is_active'] ? 'Evet' : 'Hayır') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test kullanıcısı oluştur
    echo "<h3>Test Kullanıcısı Oluşturma:</h3>";
    
    // Admin kullanıcısı kontrol et/oluştur
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
        echo "<p>✓ Admin kullanıcısı oluşturuldu: admin@bilet.com / password</p>";
    } else {
        echo "<p>✓ Admin kullanıcısı zaten mevcut</p>";
    }
    
    // Normal kullanıcı kontrol et/oluştur
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
        echo "<p>✓ Normal kullanıcı oluşturuldu: user@test.com / password</p>";
    } else {
        echo "<p>✓ Normal kullanıcı zaten mevcut</p>";
    }
    
    // Login testi
    echo "<h3>Login Testi:</h3>";
    require_once 'config/auth.php';
    
    // Test login with different accounts
    $testAccounts = [
        ['email' => 'admin@bilet.com', 'password' => 'password', 'name' => 'Admin'],
        ['email' => 'user@test.com', 'password' => 'password', 'name' => 'Normal Kullanıcı']
    ];
    
    foreach ($testAccounts as $account) {
        $testResult = $auth->login($account['email'], $account['password']);
        if ($testResult['success']) {
            echo "<p style='color: green;'>✓ {$account['name']} login testi başarılı</p>";
            echo "<p>Kullanıcı bilgileri: " . json_encode($testResult['user'], JSON_UNESCAPED_UNICODE) . "</p>";
            $auth->logout();
        } else {
            echo "<p style='color: red;'>❌ {$account['name']} login testi başarısız: " . $testResult['message'] . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Hata: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Test Hesapları:</h3>";
echo "<ul>";
echo "<li><strong>Admin:</strong> admin@bilet.com / password</li>";
echo "<li><strong>Normal Kullanıcı:</strong> user@test.com / password</li>";
echo "</ul>";

echo "<p><a href='login.php'>Giriş sayfasına git</a></p>";
?>
