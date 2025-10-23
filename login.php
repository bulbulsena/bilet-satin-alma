<?php
$pageTitle = "Giriş Yap";
require_once 'config/auth.php';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($email && $password) {
        $result = $auth->login($email, $password);
        
        if ($result['success']) {
            $_SESSION['success_message'] = 'Giriş başarılı!';
            
            // Redirect based on role
            if ($auth->isAdmin()) {
                header('Location: admin_panel.php');
            } elseif ($auth->isCompanyAdmin()) {
                header('Location: firm_admin_panel.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $error = $result['message'];
        }
    } else {
        $error = 'Lütfen tüm alanları doldurun.';
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card">
            <div class="card-header text-center">
                <h4><i class="fas fa-sign-in-alt"></i> Giriş Yap</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Şifre</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Giriş Yap
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <p class="mb-0">
                    Hesabınız yok mu? 
                    <a href="register.php" class="text-decoration-none">
                        <i class="fas fa-user-plus"></i> Kayıt Ol
                    </a>
                </p>
            </div>
        </div>
        
        <!-- Demo Accounts -->
        <div class="card mt-3">
            <div class="card-header">
                <h6><i class="fas fa-info-circle"></i> Demo Hesaplar</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12 mb-2">
                        <strong>Admin:</strong><br>
                        <small class="text-muted">admin@bilet.com / password</small>
                    </div>
                    <div class="col-12 mb-2">
                        <strong>Firma Admin:</strong><br>
                        <small class="text-muted">metro@admin.com / password</small>
                    </div>
                    <div class="col-12">
                        <strong>Normal Kullanıcı:</strong><br>
                        <small class="text-muted">user@test.com / password</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
