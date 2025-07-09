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
    
    // Siapkan untuk membangun query update secara dinamis
    $updateFields = [];
    $updateValues = [];
    
    // Cek dan tambahkan nama depan jika ada
    if (isset($input['first_name']) && !empty(trim($input['first_name']))) {
        $updateFields[] = 'first_name = ?';
        $updateValues[] = trim($input['first_name']);
    }
    
    // Cek dan tambahkan nama belakang jika ada
    if (isset($input['last_name']) && !empty(trim($input['last_name']))) {
        $updateFields[] = 'last_name = ?';
        $updateValues[] = trim($input['last_name']);
    }
    
    // Cek dan tambahkan email jika ada
    if (isset($input['email']) && !empty(trim($input['email']))) {
        // Validasi format email
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            sendJsonResponse(['error' => 'Format email tidak valid'], 400);
        }
        
        // Cek apakah email sudah dipakai pengguna lain
        $checkQuery = "SELECT id FROM users WHERE email = ? AND id != ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([trim($input['email']), $user['id']]);
        
        if ($checkStmt->fetch()) {
            sendJsonResponse(['error' => 'Email sudah digunakan pengguna lain'], 409);
        }
        
        $updateFields[] = 'email = ?';
        $updateValues[] = trim($input['email']);
    }
    
    // Cek dan tambahkan nomor telepon jika ada
    if (isset($input['phone']) && !empty(trim($input['phone']))) {
        $updateFields[] = 'phone = ?';
        $updateValues[] = trim($input['phone']);
    }
    
    // Cek jika tidak ada data valid untuk diupdate
    if (empty($updateFields)) {
        sendJsonResponse(['error' => 'Tidak ada data yang akan diupdate'], 400);
    }
    
    // Tambahkan ID pengguna untuk klausa WHERE
    $updateValues[] = $user['id'];
    
    // Bangun dan eksekusi query update
    $updateQuery = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);
    
    if ($updateStmt->execute($updateValues)) {
        // Pencatatan log aktivitas
        $logQuery = "INSERT INTO activity_logs (user_id, action, table_name, record_id, description) VALUES (?, ?, ?, ?, ?)";
        $logStmt = $db->prepare($logQuery);
        $logStmt->execute([$user['id'], 'UPDATE', 'users', $user['id'], 'Profile updated']);
        
        // Kirim respons sukses
        sendJsonResponse([
            'success' => true,
            'message' => 'Profil berhasil diperbarui'
        ]);
    } else {
        sendJsonResponse(['error' => 'Gagal memperbarui profil'], 500);
    }
    
} catch (Exception $e) {
    // Handle error
    error_log("Update profile error: " . $e->getMessage());
    sendJsonResponse(['error' => 'Terjadi kesalahan internal'], 500);
}
?>