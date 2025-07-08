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
    // Ambil data input (JSON atau form-data)
    $input = getJsonInput();
    if (!$input) {
        $input = $_POST;
    }
    
    // Validasi field yang wajib diisi
    $required = ['email', 'password'];
    $missing = validateRequired($input, $required);
    if (!empty($missing)) {
        sendJsonResponse(['error' => 'Missing required fields: ' . implode(', ', $missing)], 400);
    }
    
    $email = trim($input['email']);
    $password = $input['password'];
    
    // Koneksi ke database
    $database = new Database();
    $db = $database->getConnection();
    
    // Cari pengguna berdasarkan email atau NIM
    $query = "SELECT * FROM users WHERE (email = ? OR nim = ?) AND is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$email, $email]);
    $user = $stmt->fetch();
    
    // Cek jika pengguna tidak ditemukan
    if (!$user) {
        sendJsonResponse(['error' => 'Invalid credentials'], 401);
    }
    
    // Verifikasi password
    if (!password_verify($password, $user['password'])) {
        sendJsonResponse(['error' => 'Invalid credentials'], 401);
    }
    
    // Buat sesi dan token
    $auth = new Auth();
    $sessionToken = $auth->createSession($user['id']);
    
    // Atur cookie untuk sesi
    setcookie('session_token', $sessionToken, [
        'expires' => time() + (7 * 24 * 60 * 60), // 7 hari
        'path' => '/',
        'httponly' => true,
        'secure' => true,
        'samesite' => 'Lax'
    ]);
    
    // Pencatatan log aktivitas
    $logQuery = "INSERT INTO activity_logs (user_id, action, table_name, record_id, description) VALUES (?, ?, ?, ?, ?)";
    $logStmt = $db->prepare($logQuery);
    $logStmt->execute([$user['id'], 'LOGIN', 'users', $user['id'], 'User logged in']);
    
    // Kembalikan data pengguna (tanpa password) dan token
    unset($user['password']);
    sendJsonResponse([
        'success' => true,
        'message' => 'Login successful',
        'user' => $user,
        'session_token' => $sessionToken
    ]);
    
} catch (Exception $e) {
    // Handle error
    error_log("Login error: " . $e->getMessage());
    sendJsonResponse(['error' => 'Internal server error'], 500);
}
?>