<?php
// Memuat file dependensi
require_once __DIR__ . '/../../config/database.php';

// Otentikasi dan otorisasi admin
$auth = new Auth();
$user = $auth->getCurrentUser();

// Cek jika pengguna login
if (!$user) {
    header('Location: login.php?error=login_required');
    exit;
}

// Cek jika pengguna adalah admin
$isAdmin = isset($user['role']) && $user['role'] === 'admin';
if (!$isAdmin) {
    header('Location: index.php?error=access_denied');
    exit;
}

// Koneksi ke database
$database = new Database();
$db = $database->getConnection();

// Menangani aksi hapus postingan
if ($_POST && isset($_POST['delete_post'])) {
    $postId = $_POST['post_id'];
    $postType = $_POST['post_type'];
    $reason = $_POST['reason'] ?? 'Tidak ada alasan';
    
    try {
        // Jika tipenya 'lost-found'
        if ($postType === 'lost-found') {
            // Ambil info postingan sebelum dihapus (untuk log)
            $titleQuery = "SELECT title, user_id FROM lost_found_items WHERE id = ?";
            $titleStmt = $db->prepare($titleQuery);
            $titleStmt->execute([$postId]);
            $postInfo = $titleStmt->fetch();
            
            // Hapus postingan dari database
            $deleteQuery = "DELETE FROM lost_found_items WHERE id = ?";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->execute([$postId]);
            
        } else { // Jika tipenya 'activity'
            // Ambil info postingan sebelum dihapus (untuk log)
            $titleQuery = "SELECT title, user_id FROM activities WHERE id = ?";
            $titleStmt = $db->prepare($titleQuery);
            $titleStmt->execute([$postId]);
            $postInfo = $titleStmt->fetch();
            
            // Hapus postingan dari database
            $deleteQuery = "DELETE FROM activities WHERE id = ?";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->execute([$postId]);
        }
        
        // Catat aktivitas admin ke log
        try {
            $logQuery = "INSERT INTO admin_logs (admin_user_id, action, target_id, target_title, reason) VALUES (?, ?, ?, ?, ?)";
            $logStmt = $db->prepare($logQuery);
            $logStmt->execute([$user['id'], 'DELETE_POST', $postId, $postInfo['title'], $reason]);
        } catch (Exception $e) {
            // Abaikan jika pencatatan log gagal agar proses utama tidak terganggu
        }
        
        // Siapkan pesan sukses
        $success = "Postingan '{$postInfo['title']}' berhasil dihapus!";
    } catch (Exception $e) {
        // Siapkan pesan error
        $error = "Gagal menghapus postingan: " . $e->getMessage();
    }
}

// Mengambil semua postingan (kegiatan & lost-found)
$allPosts = [];

// Ambil semua postingan 'lost & found'
$lfQuery = "SELECT 'lost-found' as type, lf.id, lf.title, lf.description, lf.created_at, 
                   CONCAT(u.first_name, ' ', u.last_name) as author, u.nim, u.email
           FROM lost_found_items lf 
           JOIN users u ON lf.user_id = u.id 
           WHERE u.is_active = 1
           ORDER BY lf.created_at DESC";
$lfStmt = $db->prepare($lfQuery);
$lfStmt->execute();
$lostFoundPosts = $lfStmt->fetchAll();

// Ambil semua postingan 'kegiatan'
$actQuery = "SELECT 'activity' as type, a.id, a.title, a.description, a.created_at,
                    CONCAT(u.first_name, ' ', u.last_name) as author, u.nim, u.email
              FROM activities a 
              JOIN users u ON a.user_id = u.id 
              WHERE a.is_active = 1 AND u.is_active = 1
              ORDER BY a.created_at DESC";
$actStmt = $db->prepare($actQuery);
$actStmt->execute();
$activityPosts = $actStmt->fetchAll();

// Gabungkan dan urutkan semua postingan berdasarkan tanggal
$allPosts = array_merge($lostFoundPosts, $activityPosts);
usort($allPosts, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Mengambil statistik dasar
$statsQuery = "SELECT 
    (SELECT COUNT(*) FROM lost_found_items) as total_lf,
    (SELECT COUNT(*) FROM activities WHERE is_active = 1) as total_activities,
    (SELECT COUNT(*) FROM users WHERE is_active = 1) as total_users";
$statsStmt = $db->prepare($statsQuery);
$statsStmt->execute();
$stats = $statsStmt->fetch();
?>