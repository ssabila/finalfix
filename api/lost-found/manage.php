<?php
// Konfigurasi Header & CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, DELETE, OPTIONS');
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
    // Otentikasi pengguna (wajib untuk semua metode)
    $auth = new Auth();
    $user = $auth->requireAuth();
    
    // Koneksi ke database
    $database = new Database();
    $db = $database->getConnection();
    
    // Handle GET (ambil detail item)
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Validasi ID dari URL
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            sendJsonResponse(['error' => 'ID item tidak valid'], 400);
        }
        
        $itemId = (int)$_GET['id'];
        
        // Query untuk mengambil item spesifik milik pengguna
        $query = "SELECT lf.*, c.name as category_name 
                  FROM lost_found_items lf 
                  JOIN categories c ON lf.category_id = c.id 
                  WHERE lf.id = ? AND lf.user_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$itemId, $user['id']]);
        $item = $stmt->fetch();
        
        // Cek jika item ditemukan
        if (!$item) {
            sendJsonResponse(['error' => 'Item tidak ditemukan atau bukan milik Anda'], 404);
        }
        
        sendJsonResponse(['success' => true, 'item' => $item]);
        
    // Handle PUT (perbarui item)
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Ambil dan validasi input
        $input = getJsonInput();
        if (!isset($input['id']) || !is_numeric($input['id'])) {
            sendJsonResponse(['error' => 'ID item tidak valid'], 400);
        }
        
        $itemId = (int)$input['id'];
        
        // Verifikasi kepemilikan item
        $checkQuery = "SELECT id FROM lost_found_items WHERE id = ? AND user_id = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$itemId, $user['id']]);
        if (!$checkStmt->fetch()) {
            sendJsonResponse(['error' => 'Item tidak ditemukan atau bukan milik Anda'], 404);
        }
        
        // Validasi field wajib diisi
        $required = ['title', 'description', 'type', 'category_id', 'location', 'date_occurred'];
        $missing = validateRequired($input, $required);
        if (!empty($missing)) {
            sendJsonResponse(['error' => 'Field yang diperlukan: ' . implode(', ', $missing)], 400);
        }
        
        // Validasi tipe item
        if (!in_array($input['type'], ['hilang', 'ditemukan'])) {
            sendJsonResponse(['error' => 'Tipe harus "hilang" atau "ditemukan"'], 400);
        }
        
        // Query untuk update
        $updateQuery = "UPDATE lost_found_items 
                        SET title = ?, description = ?, type = ?, category_id = ?, 
                            location = ?, date_occurred = ?, status = ?
                        WHERE id = ? AND user_id = ?";
        
        // Tentukan status item
        $status = isset($input['status']) && in_array($input['status'], ['terbuka', 'selesai']) 
                  ? $input['status'] : 'terbuka';
        
        // Eksekusi query update
        $stmt = $db->prepare($updateQuery);
        $result = $stmt->execute([
            $input['title'], $input['description'], $input['type'],
            $input['category_id'], $input['location'], $input['date_occurred'],
            $status, $itemId, $user['id']
        ]);
        
        if ($result) {
            // Pencatatan log aktivitas
            $logQuery = "INSERT INTO activity_logs (user_id, action, table_name, record_id, description) VALUES (?, ?, ?, ?, ?)";
            $logStmt = $db->prepare($logQuery);
            $logStmt->execute([$user['id'], 'UPDATE', 'lost_found_items', $itemId, 'Updated lost & found item: ' . $input['title']]);
            
            sendJsonResponse(['success' => true, 'message' => 'Item berhasil diperbarui']);
        } else {
            sendJsonResponse(['error' => 'Gagal memperbarui item'], 500);
        }
        
    // Handle DELETE (hapus item)
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Ambil dan validasi input
        $input = getJsonInput();
        if (!isset($input['id']) || !is_numeric($input['id'])) {
            sendJsonResponse(['error' => 'ID item tidak valid'], 400);
        }
        
        $itemId = (int)$input['id'];
        
        // Ambil detail item sebelum dihapus (untuk log dan hapus file)
        $itemQuery = "SELECT title, image FROM lost_found_items WHERE id = ? AND user_id = ?";
        $itemStmt = $db->prepare($itemQuery);
        $itemStmt->execute([$itemId, $user['id']]);
        $item = $itemStmt->fetch();
        
        if (!$item) {
            sendJsonResponse(['error' => 'Item tidak ditemukan atau bukan milik Anda'], 404);
        }
        
        // Query untuk hapus data dari database
        $deleteQuery = "DELETE FROM lost_found_items WHERE id = ? AND user_id = ?";
        $deleteStmt = $db->prepare($deleteQuery);
        $result = $deleteStmt->execute([$itemId, $user['id']]);
        
        if ($result) {
            // Hapus file gambar terkait jika ada
            if ($item['image'] && file_exists($item['image'])) {
                unlink($item['image']);
            }
            
            // Pencatatan log aktivitas
            $logQuery = "INSERT INTO activity_logs (user_id, action, table_name, record_id, description) VALUES (?, ?, ?, ?, ?)";
            $logStmt = $db->prepare($logQuery);
            $logStmt->execute([$user['id'], 'DELETE', 'lost_found_items', $itemId, 'Deleted lost & found item: ' . $item['title']]);
            
            sendJsonResponse(['success' => true, 'message' => 'Item berhasil dihapus']);
        } else {
            sendJsonResponse(['error' => 'Gagal menghapus item'], 500);
        }
        
    } else {
        // Handle metode lain
        sendJsonResponse(['error' => 'Method tidak diizinkan'], 405);
    }
    
} catch (Exception $e) {
    // Handle error
    error_log("Lost & Found management error: " . $e->getMessage());
    sendJsonResponse(['error' => 'Terjadi kesalahan internal'], 500);
}
?>