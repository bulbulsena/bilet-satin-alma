<?php
/**
 * Authorization Helper - Yetkilendirme ve Güvenlik Sınıfı
 * 
 * Bu sınıf, kullanıcı rollerini kontrol etmek ve güvenli erişim sağlamak için kullanılır.
 * Her korumalı sayfanın en üstünde bu sınıfın metodları çağrılmalıdır.
 */

class Authorization {
    private $db;
    private $user;
    
    public function __construct($database) {
        $this->db = $database;
        $this->initializeSession();
    }
    
    /**
     * Session'ı başlat ve kullanıcı bilgilerini yükle
     */
    private function initializeSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Session'dan kullanıcı bilgilerini al
        if (isset($_SESSION['user_id'])) {
            $this->loadUserFromSession();
        }
    }
    
    /**
     * Session'dan kullanıcı bilgilerini yükle
     */
    private function loadUserFromSession() {
        // Önce session'dan kullanıcı bilgilerini al
        if (isset($_SESSION['user'])) {
            $this->user = $_SESSION['user'];
        } else if (isset($_SESSION['user_id'])) {
            // Session'da user bilgisi yoksa veritabanından yükle
            $userId = $_SESSION['user_id'];
            $this->user = $this->db->fetchOne(
                "SELECT u.*, bc.name as company_name, bc.id as company_id 
                 FROM users u 
                 LEFT JOIN bus_companies bc ON u.company_id = bc.id 
                 WHERE u.id = ? AND u.is_active = 1", 
                [$userId]
            );
            // Session'a kaydet
            if ($this->user) {
                $_SESSION['user'] = $this->user;
            }
        }
    }
    
    /**
     * Kullanıcının giriş yapıp yapmadığını kontrol et
     */
    public function isLoggedIn() {
        return isset($this->user) && $this->user !== null;
    }
    
    /**
     * Kullanıcının admin olup olmadığını kontrol et
     */
    public function isAdmin() {
        return $this->isLoggedIn() && $this->user['role'] === 'admin';
    }
    
    /**
     * Kullanıcının firma admin olup olmadığını kontrol et
     */
    public function isCompanyAdmin() {
        return $this->isLoggedIn() && $this->user['role'] === 'company_admin';
    }
    
    /**
     * Kullanıcının normal kullanıcı olup olmadığını kontrol et
     */
    public function isUser() {
        return $this->isLoggedIn() && $this->user['role'] === 'user';
    }
    
    /**
     * Kullanıcının belirli bir firmaya ait olup olmadığını kontrol et
     */
    public function belongsToCompany($companyId) {
        return $this->isCompanyAdmin() && $this->user['company_id'] === $companyId;
    }
    
    /**
     * Kullanıcının kendi firmasına ait bir seferi yönetip yönetemeyeceğini kontrol et
     */
    public function canManageTrip($tripId) {
        if ($this->isAdmin()) {
            return true; // Admin tüm seferleri yönetebilir
        }
        
        if ($this->isCompanyAdmin()) {
            // Firma admin sadece kendi firmasının seferlerini yönetebilir
            $trip = $this->db->fetchOne(
                "SELECT company_id FROM trips WHERE id = ?", 
                [$tripId]
            );
            return $trip && $this->user['company_id'] === $trip['company_id'];
        }
        
        return false;
    }
    
    /**
     * Kullanıcının kendi firmasının seferlerini getirmesi için WHERE koşulu
     */
    public function getCompanyFilter() {
        if ($this->isAdmin()) {
            return null; // Admin tüm seferleri görebilir
        }
        
        if ($this->isCompanyAdmin()) {
            return "company_id = ?";
        }
        
        return "1 = 0"; // Hiçbir sefer gösterilmez
    }
    
    /**
     * Kullanıcının kendi firmasının seferlerini getirmesi için parametreler
     */
    public function getCompanyFilterParams() {
        if ($this->isAdmin()) {
            return [];
        }
        
        if ($this->isCompanyAdmin()) {
            return [$this->user['company_id']];
        }
        
        return [];
    }
    
    /**
     * Belirli bir role sahip kullanıcıların erişimini zorunlu kıl
     */
    public function requireRole($requiredRole) {
        if (!$this->isLoggedIn()) {
            $this->redirectToLogin();
        }
        
        $hasAccess = false;
        switch ($requiredRole) {
            case 'admin':
                $hasAccess = $this->isAdmin();
                break;
            case 'company_admin':
                $hasAccess = $this->isCompanyAdmin();
                break;
            case 'user':
                $hasAccess = $this->isUser();
                break;
        }
        
        if (!$hasAccess) {
            $this->redirectToUnauthorized();
        }
    }
    
    /**
     * Admin veya Firma Admin erişimini zorunlu kıl
     */
    public function requireAdminOrCompanyAdmin() {
        if (!$this->isLoggedIn()) {
            $this->redirectToLogin();
        }
        
        if (!$this->isAdmin() && !$this->isCompanyAdmin()) {
            $this->redirectToUnauthorized();
        }
    }
    
    /**
     * Giriş yapmış kullanıcılar için erişimi zorunlu kıl
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            $this->redirectToLogin();
        }
    }
    
    /**
     * Kullanıcı bilgilerini getir
     */
    public function getUser() {
        return $this->user;
    }
    
    /**
     * Kullanıcının firma bilgilerini getir
     */
    public function getCompany() {
        if ($this->isCompanyAdmin()) {
            return [
                'id' => $this->user['company_id'],
                'name' => $this->user['company_name']
            ];
        }
        return null;
    }
    
    /**
     * Giriş sayfasına yönlendir
     */
    private function redirectToLogin() {
        header('Location: login.php');
        exit;
    }
    
    /**
     * Yetkisiz erişim sayfasına yönlendir
     */
    private function redirectToUnauthorized() {
        header('Location: unauthorized.php');
        exit;
    }
    
    /**
     * Güvenli çıkış yap
     */
    public function logout() {
        session_destroy();
        header('Location: index.php');
        exit;
    }
    
    /**
     * CSRF token oluştur
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * CSRF token doğrula
     */
    public function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
?>
