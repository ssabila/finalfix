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
    // Otentikasi pengguna
    $auth = new Auth();
    $user = $auth->requireAuth();
    
    // Ambil data input
    $input = getJsonInput();
    if (!$input) {
        $input = $_POST;
    }
    
    // Koneksi ke database
    $database = new Database();
    $db = $database->getConnection();
    
    // Validasi field wajib diisi
    $required = ['current_password', 'new_password', 'confirm_password'];
    $missing = validateRequired($input, $required);
    if (!empty($missing)) {
        sendJsonResponse(['error' => 'Missing required fields'], 400);
    }
    
    // Validasi konfirmasi password baru
    if ($input['new_password'] !== $input['confirm_password']) {
        sendJsonResponse(['error' => 'Password confirmation does not match'], 400);
    }
    
    // Validasi kekuatan password baru
    if (strlen($input['new_password']) < 6) {
        sendJsonResponse(['error' => 'Password must be at least 6 characters long'], 400);
    }
    
    // Ambil password saat ini dari database
    $currentUserQuery = "SELECT password FROM users WHERE id = ?";
    $currentUserStmt = $db->prepare($currentUserQuery);
    $currentUserStmt->execute([$user['id']]);
    $currentUserData = $currentUserStmt->fetch();
    
    // Verifikasi kecocokan password saat ini
    if (!password_verify($input['current_password'], $currentUserData['password'])) {
        sendJsonResponse(['error' => 'Current password is incorrect'], 400);
    }
    
    // Enkripsi password baru
    $hashedPassword = password_hash($input['new_password'], PASSWORD_DEFAULT);
    
    // Update password di database
    $updateQuery = "UPDATE users SET password = ? WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);
    
    if ($updateStmt->execute([$hashedPassword, $user['id']])) {
        // Pencatatan log aktivitas
        $logQuery = "INSERT INTO activity_logs (user_id, action, table_name, record_id, description) VALUES (?, ?, ?, ?, ?)";
        $logStmt = $db->prepare($logQuery);
        $logStmt->execute([$user['id'], 'UPDATE', 'users', $user['id'], 'Password changed']);
        
        // Kirim respons sukses
        sendJsonResponse([
            'success' => true,
            'message' => 'Password berhasil diubah'
        ]);
    } else {
        sendJsonResponse(['error' => 'Gagal mengubah password'], 500);
    }
    
} catch (Exception $e) {
    // Handle error
    error_log("Change password error: " . $e->getMessage());
    sendJsonResponse(['error' => 'Internal server error'], 500);
}
?>