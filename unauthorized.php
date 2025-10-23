<?php
$pageTitle = "Yetkisiz Erişim";
require_once 'config/auth.php';
require_once 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white text-center">
                    <h4><i class="fas fa-exclamation-triangle"></i> Yetkisiz Erişim</h4>
                </div>
                <div class="card-body text-center">
                    <i class="fas fa-lock fa-5x text-danger mb-4"></i>
                    <h5>Bu sayfaya erişim yetkiniz bulunmamaktadır.</h5>
                    <p class="text-muted">
                        Bu sayfayı görüntülemek için gerekli yetkiye sahip değilsiniz.
                    </p>
                    
                    <div class="mt-4">
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-home"></i> Ana Sayfaya Dön
                        </a>
                        <?php if ($auth->isLoggedIn()): ?>
                            <a href="logout.php" class="btn btn-outline-secondary">
                                <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-outline-secondary">
                                <i class="fas fa-sign-in-alt"></i> Giriş Yap
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
