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
    // Ambil data input
    $input = getJsonInput();
    if (!$input) {
        $input = $_POST;
    }
    
    // Validasi field wajib diisi
    $required = ['firstName', 'lastName', 'nim', 'email', 'phone', 'password', 'confirmPassword'];
    $missing = validateRequired($input, $required);
    if (!empty($missing)) {
        sendJsonResponse(['error' => 'Missing required fields: ' . implode(', ', $missing)], 400);
    }
    
    // Validasi konfirmasi password
    if ($input['password'] !== $input['confirmPassword']) {
        sendJsonResponse(['error' => 'Password confirmation does not match'], 400);
    }
    
    // Validasi kekuatan password
    if (strlen($input['password']) < 6) {
        sendJsonResponse(['error' => 'Password must be at least 6 characters long'], 400);
    }
    
    // Validasi format email
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        sendJsonResponse(['error' => 'Format email tidak valid.'], 400);
    }

    // Ekstrak dan validasi domain email
    $email_domain = substr(strrchr($input['email'], "@"), 1);
    if ($email_domain !== 'stis.ac.id' && $email_domain !== 'bps.go.id') {
        sendJsonResponse(['error' => 'Email harus berakhiran @stis.ac.id atau @bps.go.id.'], 400);
    }
    
    // Koneksi ke database
    $database = new Database();
    $db = $database->getConnection();
    
    // Cek apakah email atau NIM sudah terdaftar
    $checkQuery = "SELECT id FROM users WHERE email = ? OR nim = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$input['email'], $input['nim']]);
    
    if ($checkStmt->fetch()) {
        sendJsonResponse(['error' => 'Email or NIM already registered'], 409); // 409 Conflict
    }
    
    // Enkripsi password
    $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
    
    // Masukkan pengguna baru ke database
    $insertQuery = "INSERT INTO users (nim, first_name, last_name, email, phone, password) VALUES (?, ?, ?, ?, ?, ?)";
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->execute([
        $input['nim'],
        $input['firstName'],
        $input['lastName'],
        $input['email'],
        $input['phone'],
        $hashedPassword
    ]);
    
    $userId = $db->lastInsertId();
    
    // Pencatatan log aktivitas
    $logQuery = "INSERT INTO activity_logs (user_id, action, table_name, record_id, description) VALUES (?, ?, ?, ?, ?)";
    $logStmt = $db->prepare($logQuery);
    $logStmt->execute([$userId, 'REGISTER', 'users', $userId, 'New user registered']);
    
    // Kirim respons sukses
    sendJsonResponse([
        'success' => true,
        'message' => 'Registration successful',
        'user_id' => $userId
    ]);
    
} catch (Exception $e) {
    // Handle error
    error_log("Registration error: " . $e->getMessage());
    sendJsonResponse(['error' => 'Internal server error'], 500);
}
?>