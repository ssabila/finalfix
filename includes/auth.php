<?php
session_start();
require_once 'config/database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function login($email, $password) {
        $query = "SELECT * FROM users WHERE (email = ? OR nim = ?) AND is_active = 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$email, $email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_data'] = [
            'id' => $user['id'],
            'nim' => $user['nim'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'role' => $user['role'] // <-- TAMBAHKAN BARIS INI
            ];
        return true;
        }
        return false;
    }
    
    public function register($data) {
        // Check if email or NIM already exists
        $checkQuery = "SELECT id FROM users WHERE email = ? OR nim = ?";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->execute([$data['email'], $data['nim']]);
        
        if ($checkStmt->fetch()) {
            return ['error' => 'Email atau NIM sudah terdaftar'];
        }
        
        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Insert new user
        $insertQuery = "INSERT INTO users (nim, first_name, last_name, email, phone, password) VALUES (?, ?, ?, ?, ?, ?)";
        $insertStmt = $this->db->prepare($insertQuery);
        
        try {
            $insertStmt->execute([
                $data['nim'],
                $data['firstName'],
                $data['lastName'],
                $data['email'],
                $data['phone'],
                $hashedPassword
            ]);
            return ['success' => true];
        } catch (Exception $e) {
            return ['error' => 'Gagal mendaftar'];
        }
    }
    
    public function logout() {
        session_destroy();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function getCurrentUser() {
        return isset($_SESSION['user_data']) ? $_SESSION['user_data'] : null;
    }
    
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }

    /**
 * Check if current user is admin
 */
public function isAdmin() {
    $user = $this->getCurrentUser();
    return $user && isset($user['role']) && $user['role'] === 'admin';
}

/**
 * Require admin access
 */
public function requireAdmin() {
    if (!$this->isLoggedIn()) {
        header('Location: login.php?error=login_required');
        exit;
    }
    
    if (!$this->isAdmin()) {
        header('Location: index.php?error=access_denied');
        exit;
    }
    
    return $this->getCurrentUser();
}

    /**
     * Log admin activity
     */
    public function logAdminActivity($adminId, $action, $targetId, $targetTitle, $reason = '') {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "INSERT INTO admin_logs (admin_user_id, action, target_id, target_title, reason) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$adminId, $action, $targetId, $targetTitle, $reason]);
        } catch (Exception $e) {
            error_log("Failed to log admin activity: " . $e->getMessage());
        }
    }
}

// Helper functions
function redirect($url) {
    header("Location: $url");
    exit;
}

function showAlert($message, $type = 'info') {
    return "<script>
        document.addEventListener('DOMContentLoaded', function() {
            const alert = document.createElement('div');
            alert.className = 'alert alert-{$type}';
            alert.innerHTML = '<i class=\"fas fa-" . ($type === 'success' ? 'check-circle' : 'exclamation-triangle') . "\"></i> {$message}';
            document.body.insertBefore(alert, document.body.firstChild);
            setTimeout(() => alert.remove(), 5000);
        });
    </script>";
}
?>
