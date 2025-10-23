<?php
$pageTitle = "Profil";
require_once 'config/auth.php';

// Only allow logged in users
$auth->requireLogin();

$user = $auth->getCurrentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (!$fullName || !$email) {
        $error = 'Ad soyad ve email gerekli.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçerli bir email adresi girin.';
    } elseif ($newPassword && $newPassword !== $confirmPassword) {
        $error = 'Yeni şifreler eşleşmiyor.';
    } elseif ($newPassword && strlen($newPassword) < 6) {
        $error = 'Yeni şifre en az 6 karakter olmalıdır.';
    } else {
        try {
            // Check if email is already used by another user
            if ($email !== $user['email']) {
                $existingUser = $db->fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $user['id']]);
                if ($existingUser) {
                    $error = 'Bu email adresi zaten kullanılıyor.';
                }
            }
            
            if (!$error) {
                // Check current password if changing password
                if ($newPassword && $currentPassword) {
                    if (!password_verify($currentPassword, $user['password'])) {
                        $error = 'Mevcut şifre hatalı.';
                    }
                }
                
                if (!$error) {
                    // Update user information
                    $updateData = [
                        'full_name' => $fullName,
                        'email' => $email,
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    if ($newPassword) {
                        $updateData['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                    }
                    
                    $db->update('users', $updateData, 'id = ?', [$user['id']]);
                    
                    // Update session
                    $_SESSION['user']['full_name'] = $fullName;
                    $_SESSION['user']['email'] = $email;
                    $_SESSION['email'] = $email;
                    
                    $success = 'Profil bilgileri başarıyla güncellendi.';
                    
                    // Refresh user data
                    $user = $auth->getCurrentUser();
                }
            }
        } catch (Exception $e) {
            $error = 'Profil güncelleme sırasında hata oluştu: ' . $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-user"></i> Profil Bilgileri</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">Ad Soyad</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Telefon</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Rol</label>
                            <input type="text" class="form-control" value="<?php 
                                echo $user['role'] === 'admin' ? 'Admin' : 
                                    ($user['role'] === 'company_admin' ? 'Firma Admin' : 'Kullanıcı'); 
                            ?>" readonly>
                        </div>
                    </div>
                    
                    <?php if ($user['role'] === 'company_admin'): ?>
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label for="company" class="form-label">Firma</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['company_name'] ?? 'Bilinmiyor'); ?>" readonly>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="balance" class="form-label">Bakiye</label>
                            <input type="text" class="form-control" value="<?php echo number_format($user['balance'] ?? 0, 2); ?> ₺" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="created_at" class="form-label">Kayıt Tarihi</label>
                            <input type="text" class="form-control" value="<?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?>" readonly>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    <h5 class="mb-3">Şifre Değiştir</h5>
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label for="current_password" class="form-label">Mevcut Şifre</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                            <div class="form-text">Şifre değiştirmek için mevcut şifrenizi girin.</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="new_password" class="form-label">Yeni Şifre</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                            <div class="form-text">En az 6 karakter olmalıdır.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Yeni Şifre Tekrar</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Profili Güncelle
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Statistics Card -->
        <div class="card mt-4">
            <div class="card-header">
                <h5><i class="fas fa-chart-bar"></i> İstatistikler</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h3 class="text-primary">
                                    <?php 
                                    $totalTickets = $db->fetchOne("SELECT COUNT(*) as count FROM tickets WHERE user_id = ?", [$user['id']])['count'];
                                    echo $totalTickets;
                                    ?>
                                </h3>
                                <p class="mb-0">Toplam Bilet</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h3 class="text-success">
                                    <?php 
                                    $activeTickets = $db->fetchOne("SELECT COUNT(*) as count FROM tickets WHERE user_id = ? AND status = 'active'", [$user['id']])['count'];
                                    echo $activeTickets;
                                    ?>
                                </h3>
                                <p class="mb-0">Aktif Bilet</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h3 class="text-info">
                                    <?php 
                                    $totalSpent = $db->fetchOne("SELECT SUM(total_price) as total FROM tickets WHERE user_id = ? AND status = 'active'", [$user['id']])['total'] ?? 0;
                                    echo number_format($totalSpent, 0);
                                    ?> ₺
                                </h3>
                                <p class="mb-0">Toplam Harcama</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>