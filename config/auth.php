<?php
// Session management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include Authorization helper
require_once __DIR__ . '/authorization.php';

// User authentication class
class Auth {
    private $db;
    private $authorization;
    
    public function __construct($database) {
        $this->db = $database;
        $this->authorization = new Authorization($database);
    }
    
    /**
     * Authorization helper'a erişim sağla
     */
    public function getAuthorization() {
        return $this->authorization;
    }
    
    public function register($username, $email, $password, $firstName, $lastName, $phone = '') {
        // Check if user already exists by email
        $existingUser = $this->db->fetchOne(
            "SELECT id FROM users WHERE email = ?", 
            [$email]
        );
        
        if ($existingUser) {
            return ['success' => false, 'message' => 'Email zaten kullanılıyor'];
        }
        
        // Check if username already exists
        $existingUsername = $this->db->fetchOne(
            "SELECT id FROM users WHERE full_name = ?", 
            [$username]
        );
        
        if ($existingUsername) {
            return ['success' => false, 'message' => 'Kullanıcı adı zaten kullanılıyor'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Combine first and last name
        $fullName = trim($firstName . ' ' . $lastName);
        
        // Insert user
        $userId = $this->db->insert('users', [
            'email' => $email,
            'password' => $hashedPassword,
            'full_name' => $fullName,
            'role' => 'user',
            'balance' => 800.00, // Starting balance
            'is_active' => 1
        ]);
        
        if ($userId) {
            return ['success' => true, 'message' => 'Kayıt başarılı', 'user_id' => $userId];
        }
        
        return ['success' => false, 'message' => 'Kayıt sırasında hata oluştu'];
    }
    
    public function login($email, $password) {
        try {
            // Input validation
            if (empty($email) || empty($password)) {
                return ['success' => false, 'message' => 'Email ve şifre gereklidir'];
            }
            
            // Email format validation
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Geçerli bir email adresi giriniz'];
            }
            
            // Debug: Log login attempt
            error_log("Login attempt for email: " . $email);
            
            // Get user from database
            $user = $this->db->fetchOne(
                "SELECT u.*, bc.name as company_name 
                 FROM users u 
                 LEFT JOIN bus_companies bc ON u.company_id = bc.id 
                 WHERE u.email = ? AND u.is_active = 1", 
                [$email]
            );
            
            if (!$user) {
                error_log("User not found for email: " . $email);
                return ['success' => false, 'message' => 'Email veya şifre hatalı'];
            }
            
            // Debug: Log user found
            error_log("User found: " . $user['full_name'] . " (ID: " . $user['id'] . ")");
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                error_log("Password verification failed for user: " . $user['email']);
                return ['success' => false, 'message' => 'Email veya şifre hatalı'];
            }
            
            // Debug: Log successful login
            error_log("Login successful for user: " . $user['email']);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['company_id'] = $user['company_id'];
            $_SESSION['user'] = $user;
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Reinitialize authorization
            $this->authorization = new Authorization($this->db);
            
            return ['success' => true, 'message' => 'Giriş başarılı', 'user' => $user];
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Giriş sırasında bir hata oluştu'];
        }
    }
    
    public function logout() {
        try {
            // Clear all session variables
            $_SESSION = array();
            
            // Destroy session cookie
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            
            // Destroy session
            session_destroy();
            
            return ['success' => true, 'message' => 'Çıkış yapıldı'];
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Çıkış sırasında bir hata oluştu'];
        }
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        // Return user from session if available
        if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
            return $_SESSION['user'];
        }
        
        // Otherwise fetch from database
        try {
            return $this->db->fetchOne(
                "SELECT u.*, bc.name as company_name 
                 FROM users u 
                 LEFT JOIN bus_companies bc ON u.company_id = bc.id 
                 WHERE u.id = ? AND u.is_active = 1", 
                [$_SESSION['user_id']]
            );
        } catch (Exception $e) {
            error_log("Get current user error: " . $e->getMessage());
            return null;
        }
    }
    
    public function hasRole($role) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $userRole = $_SESSION['role'] ?? null;
        return $userRole === $role;
    }
    
    public function isAdmin() {
        return $this->hasRole('admin');
    }
    
    public function isCompanyAdmin() {
        return $this->hasRole('company_admin');
    }
    
    public function isUser() {
        return $this->hasRole('user');
    }
    
    public function isFirmAdmin() {
        return $this->isCompanyAdmin();
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }
    
    public function requireRole($role) {
        $this->authorization->requireRole($role);
    }
    
    public function getCompany() {
        return $this->authorization->getCompany();
    }
    
    public function canManageTrip($tripId) {
        return $this->authorization->canManageTrip($tripId);
    }
    
    public function getCompanyFilter() {
        return $this->authorization->getCompanyFilter();
    }
    
    public function getCompanyFilterParams() {
        return $this->authorization->getCompanyFilterParams();
    }
    
}

// Global auth instance
require_once __DIR__ . '/database.php';
$auth = new Auth($db);
?>
