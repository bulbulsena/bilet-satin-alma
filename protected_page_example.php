<?php
/**
 * Korumalı Sayfa Örneği
 * 
 * Bu dosya, her korumalı sayfanın en üstünde kullanılması gereken
 * yetkilendirme kodlarını gösterir.
 */

$pageTitle = "Korumalı Sayfa Örneği";
require_once 'config/auth.php';

// 1. SADECE GİRİŞ YAPMIŞ KULLANICILAR İÇİN
$auth->requireLogin();

// 2. SADECE ADMIN İÇİN
// $auth->requireRole('admin');

// 3. SADECE FİRMA ADMIN İÇİN  
// $auth->requireRole('company_admin');

// 4. ADMIN VEYA FİRMA ADMIN İÇİN
// $auth->requireAdminOrCompanyAdmin();

// 5. KULLANICI BİLGİLERİNİ AL
$user = $auth->getUser();
$company = $auth->getCompany(); // Sadece company_admin için dolu

require_once 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-shield-alt"></i> Korumalı Sayfa Örneği</h4>
                </div>
                <div class="card-body">
                    <h5>Kullanıcı Bilgileri:</h5>
                    <ul>
                        <li><strong>Ad:</strong> <?php echo htmlspecialchars($user['full_name']); ?></li>
                        <li><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></li>
                        <li><strong>Rol:</strong> <?php echo htmlspecialchars($user['role']); ?></li>
                        <?php if ($company): ?>
                        <li><strong>Firma:</strong> <?php echo htmlspecialchars($company['name']); ?></li>
                        <?php endif; ?>
                    </ul>
                    
                    <h5 class="mt-4">Yetki Kontrolleri:</h5>
                    <ul>
                        <li>Admin mi? <?php echo $auth->isAdmin() ? '✅ Evet' : '❌ Hayır'; ?></li>
                        <li>Firma Admin mi? <?php echo $auth->isCompanyAdmin() ? '✅ Evet' : '❌ Hayır'; ?></li>
                        <li>Normal Kullanıcı mı? <?php echo $auth->isUser() ? '✅ Evet' : '❌ Hayır'; ?></li>
                    </ul>
                    
                    <h5 class="mt-4">Firma Admin İçin Özel Bilgiler:</h5>
                    <?php if ($auth->isCompanyAdmin()): ?>
                        <p><strong>Firma Filtresi:</strong> <?php echo $auth->getCompanyFilter() ?: 'Yok (Tüm veriler)'; ?></p>
                        <p><strong>Firma Filtre Parametreleri:</strong> <?php echo json_encode($auth->getCompanyFilterParams()); ?></p>
                        
                        <div class="alert alert-info">
                            <strong>Firma Admin CRUD Örneği:</strong><br>
                            Firma Admin sadece kendi firmasının seferlerini görebilir ve yönetebilir.
                            SQL sorgularında WHERE koşulu: <code><?php echo $auth->getCompanyFilter() ?: 'company_id = ?'; ?></code>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
