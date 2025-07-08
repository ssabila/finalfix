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
    // Otentikasi pengguna (wajib untuk semua metode di file ini)
    $auth = new Auth();
    $user = $auth->requireAuth();
    
    // Koneksi database
    $database = new Database();
    $db = $database->getConnection();
    
    // Handle GET (ambil detail kegiatan)
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Validasi ID dari URL
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            sendJsonResponse(['error' => 'ID aktivitas tidak valid'], 400);
        }
        
        $activityId = (int)$_GET['id'];
        
        // Query untuk mengambil kegiatan spesifik milik pengguna
        $query = "SELECT a.*, c.name as category_name 
                  FROM activities a 
                  JOIN categories c ON a.category_id = c.id 
                  WHERE a.id = ? AND a.user_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$activityId, $user['id']]);
        $activity = $stmt->fetch();
        
        // Cek jika kegiatan ditemukan
        if (!$activity) {
            sendJsonResponse(['error' => 'Aktivitas tidak ditemukan atau bukan milik Anda'], 404);
        }
        
        sendJsonResponse(['success' => true, 'activity' => $activity]);
        
    // Handle PUT (perbarui kegiatan)
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Ambil dan validasi input
        $input = getJsonInput();
        if (!isset($input['id']) || !is_numeric($input['id'])) {
            sendJsonResponse(['error' => 'ID aktivitas tidak valid'], 400);
        }
        
        $activityId = (int)$input['id'];
        
        // Verifikasi kepemilikan kegiatan
        $checkQuery = "SELECT id FROM activities WHERE id = ? AND user_id = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$activityId, $user['id']]);
        if (!$checkStmt->fetch()) {
            sendJsonResponse(['error' => 'Aktivitas tidak ditemukan atau bukan milik Anda'], 404);
        }
        
        // Validasi field yang wajib diisi
        $required = ['title', 'description', 'category_id', 'event_date', 'event_time', 'location', 'organizer'];
        $missing = validateRequired($input, $required);
        if (!empty($missing)) {
            sendJsonResponse(['error' => 'Field yang diperlukan: ' . implode(', ', $missing)], 400);
        }
        
        // Query untuk update
        $updateQuery = "UPDATE activities 
                        SET title = ?, description = ?, category_id = ?, 
                            event_date = ?, event_time = ?, location = ?, 
                            organizer = ?, is_active = ?
                        WHERE id = ? AND user_id = ?";
        
        $isActive = isset($input['is_active']) ? (bool)$input['is_active'] : true;
        
        $stmt = $db->prepare($updateQuery);
        $result = $stmt->execute([
            $input['title'], $input['description'], $input['category_id'],
            $input['event_date'], $input['event_time'], $input['location'],
            $input['organizer'], $isActive, $activityId, $user['id']
        ]);
        
        if ($result) {
            // Pencatatan log
            $logQuery = "INSERT INTO activity_logs (user_id, action, table_name, record_id, description) VALUES (?, ?, ?, ?, ?)";
            $logStmt = $db->prepare($logQuery);
            $logStmt->execute([$user['id'], 'UPDATE', 'activities', $activityId, 'Updated activity: ' . $input['title']]);
            
            sendJsonResponse(['success' => true, 'message' => 'Aktivitas berhasil diperbarui']);
        } else {
            sendJsonResponse(['error' => 'Gagal memperbarui aktivitas'], 500);
        }
        
    // Handle DELETE (hapus kegiatan)
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Ambil dan validasi input
        $input = getJsonInput();
        if (!isset($input['id']) || !is_numeric($input['id'])) {
            sendJsonResponse(['error' => 'ID aktivitas tidak valid'], 400);
        }
        
        $activityId = (int)$input['id'];
        
        // Ambil detail untuk log & hapus file
        $activityQuery = "SELECT title, image FROM activities WHERE id = ? AND user_id = ?";
        $activityStmt = $db->prepare($activityQuery);
        $activityStmt->execute([$activityId, $user['id']]);
        $activity = $activityStmt->fetch();
        
        if (!$activity) {
            sendJsonResponse(['error' => 'Aktivitas tidak ditemukan atau bukan milik Anda'], 404);
        }
        
        // Query untuk hapus data
        $deleteQuery = "DELETE FROM activities WHERE id = ? AND user_id = ?";
        $deleteStmt = $db->prepare($deleteQuery);
        $result = $deleteStmt->execute([$activityId, $user['id']]);
        
        if ($result) {
            // Hapus file gambar terkait
            if ($activity['image'] && file_exists($activity['image'])) {
                unlink($activity['image']);
            }
            
            // Pencatatan log
            $logQuery = "INSERT INTO activity_logs (user_id, action, table_name, record_id, description) VALUES (?, ?, ?, ?, ?)";
            $logStmt = $db->prepare($logQuery);
            $logStmt->execute([$user['id'], 'DELETE', 'activities', $activityId, 'Deleted activity: ' . $activity['title']]);
            
            sendJsonResponse(['success' => true, 'message' => 'Aktivitas berhasil dihapus']);
        } else {
            sendJsonResponse(['error' => 'Gagal menghapus aktivitas'], 500);
        }
        
    } else {
        // Handle metode lain
        sendJsonResponse(['error' => 'Method tidak diizinkan'], 405);
    }
    
} catch (Exception $e) {
    // Handle error
    error_log("Activities management error: " . $e->getMessage());
    sendJsonResponse(['error' => 'Terjadi kesalahan internal'], 500);
}
?>