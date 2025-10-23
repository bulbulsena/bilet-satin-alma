<?php
$pageTitle = "Admin Girişi";
require_once 'config/auth.php';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    if ($auth->isAdmin()) {
        header('Location: admin_panel.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($email && $password) {
        $result = $auth->login($email, $password);
        
        if ($result['success']) {
            if ($auth->isAdmin()) {
                $_SESSION['success_message'] = 'Admin girişi başarılı!';
                header('Location: admin_panel.php');
                exit;
            } else {
                $error = 'Bu sayfa sadece admin kullanıcıları içindir.';
                $auth->logout();
            }
        } else {
            $error = $result['message'];
        }
    } else {
        $error = 'Lütfen tüm alanları doldurun.';
    }
}

require_once 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow">
                <div class="card-header bg-dark text-white text-center">
                    <h4><i class="fas fa-crown"></i> Admin Girişi</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
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
                            <button type="submit" class="btn btn-dark">
                                <i class="fas fa-sign-in-alt"></i> Admin Girişi
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <small class="text-muted">
                        <a href="login.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left"></i> Normal Giriş
                        </a>
                    </small>
                </div>
            </div>
            
            <!-- Admin Bilgileri -->
            <div class="card mt-3">
                <div class="card-header bg-info text-white">
                    <h6><i class="fas fa-info-circle"></i> Test Admin Bilgileri</h6>
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong>Email:</strong> admin@bilet.com</p>
                    <p class="mb-0"><strong>Şifre:</strong> password</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
