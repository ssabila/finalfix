<?php
// Konfigurasi Header & CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle pre-flight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Memuat file dependensi
require_once '../config/database.php';
require_once '../utils/auth.php';

// Pastikan metode adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['error' => 'Method not allowed'], 405);
}

try {
    // Inisialisasi dan dapatkan data pengguna saat ini
    $auth = new Auth();
    $user = $auth->getCurrentUser();
    
    // Jika pengguna terautentikasi, proses logout di sisi server
    if ($user) {
        // Ambil token dari cookie atau header Authorization
        $token = isset($_COOKIE['session_token']) ? $_COOKIE['session_token'] : null;
        if (!$token) {
            $headers = getallheaders();
            if (isset($headers['Authorization']) && strpos($headers['Authorization'], 'Bearer ') === 0) {
                $token = substr($headers['Authorization'], 7);
            }
        }
        
        // Jika token ditemukan, hapus dari DB dan catat log
        if ($token) {
            // Hapus sesi dari database
            $auth->deleteSession($token);
            
            // Pencatatan log aktivitas
            $database = new Database();
            $db = $database->getConnection();
            $logQuery = "INSERT INTO activity_logs (user_id, action, table_name, record_id, description) VALUES (?, ?, ?, ?, ?)";
            $logStmt = $db->prepare($logQuery);
            $logStmt->execute([$user['id'], 'LOGOUT', 'users', $user['id'], 'User logged out']);
        }
    }
    
    // Hapus cookie sesi di browser dengan mengatur waktu kedaluwarsa ke masa lalu
    setcookie('session_token', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'httponly' => true,
        'secure' => true, 
        'samesite' => 'Lax'
    ]);
    
    // Kirim respons sukses
    sendJsonResponse([
        'success' => true,
        'message' => 'Logout successful'
    ]);
    
} catch (Exception $e) {
    // Handle error
    error_log("Logout error: " . $e->getMessage());
    sendJsonResponse(['error' => 'Internal server error'], 500);
}
?>