<?php
// Konfigurasi Header & CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle pre-flight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Memuat file dependensi
require_once '../config/database.php';
require_once '../utils/auth.php';

try {
    // Otentikasi pengguna
    $auth = new Auth();
    $user = $auth->requireAuth();
    
    // Koneksi ke database
    $database = new Database();
    $db = $database->getConnection();
    
    // Handle GET (ambil profil pengguna)
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Kirim data pengguna yang sedang login
        sendJsonResponse([
            'success' => true,
            'user' => $user
        ]);
        
    // Handle PUT (perbarui profil pengguna)
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Ambil data input
        $input = getJsonInput();
        
        // Siapkan untuk membangun query update secara dinamis
        $updateFields = [];
        $updateValues = [];
        
        // Tambahkan field ke query jika ada di input
        if (isset($input['first_name'])) {
            $updateFields[] = 'first_name = ?';
            $updateValues[] = $input['first_name'];
        }
        
        if (isset($input['last_name'])) {
            $updateFields[] = 'last_name = ?';
            $updateValues[] = $input['last_name'];
        }
        
        if (isset($input['email'])) {
            // Cek apakah email sudah dipakai pengguna lain
            $checkQuery = "SELECT id FROM users WHERE email = ? AND id != ?";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$input['email'], $user['id']]);
            
            if ($checkStmt->fetch()) {
                sendJsonResponse(['error' => 'Email already taken'], 409);
            }
            
            $updateFields[] = 'email = ?';
            $updateValues[] = $input['email'];
        }
        
        if (isset($input['phone'])) {
            $updateFields[] = 'phone = ?';
            $updateValues[] = $input['phone'];
        }
        
        // Cek jika tidak ada field untuk diupdate
        if (empty($updateFields)) {
            sendJsonResponse(['error' => 'No fields to update'], 400);
        }
        
        // Tambahkan ID pengguna untuk klausa WHERE
        $updateValues[] = $user['id'];
        
        // Bangun dan eksekusi query update
        $updateQuery = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute($updateValues);
        
        // Pencatatan log aktivitas
        $logQuery = "INSERT INTO activity_logs (user_id, action, table_name, record_id, description) VALUES (?, ?, ?, ?, ?)";
        $logStmt = $db->prepare($logQuery);
        $logStmt->execute([$user['id'], 'UPDATE', 'users', $user['id'], 'Profile updated']);
        
        // Kirim respons sukses
        sendJsonResponse([
            'success' => true,
            'message' => 'Profile updated successfully'
        ]);
        
    } else {
        // Handle metode lain
        sendJsonResponse(['error' => 'Method not allowed'], 405);
    }
    
} catch (Exception $e) {
    // Handle error
    error_log("Profile error: " . $e->getMessage());
    sendJsonResponse(['error' => 'Internal server error'], 500);
}
?>