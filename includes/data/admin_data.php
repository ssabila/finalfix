<?php
require_once __DIR__ . '/../../config/database.php';

$auth = new Auth();
$user = $auth->getCurrentUser();

// Check if user is logged in and is admin
if (!$user) {
    header('Location: login.php?error=login_required');
    exit;
}

$isAdmin = isset($user['role']) && $user['role'] === 'admin';
if (!$isAdmin) {
    header('Location: index.php?error=access_denied');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Handle delete action
if ($_POST && isset($_POST['delete_post'])) {
    $postId = $_POST['post_id'];
    $postType = $_POST['post_type'];
    $reason = $_POST['reason'] ?? 'Tidak ada alasan';
    
    try {
        if ($postType === 'lost-found') {
            // Get post info before delete
            $titleQuery = "SELECT title, user_id FROM lost_found_items WHERE id = ?";
            $titleStmt = $db->prepare($titleQuery);
            $titleStmt->execute([$postId]);
            $postInfo = $titleStmt->fetch();
            
            // Delete post
            $deleteQuery = "DELETE FROM lost_found_items WHERE id = ?";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->execute([$postId]);
            
        } else {
            // Get post info before delete
            $titleQuery = "SELECT title, user_id FROM activities WHERE id = ?";
            $titleStmt = $db->prepare($titleQuery);
            $titleStmt->execute([$postId]);
            $postInfo = $titleStmt->fetch();
            
            // Delete post
            $deleteQuery = "DELETE FROM activities WHERE id = ?";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->execute([$postId]);
        }
        
        // Log admin activity
        try {
            $logQuery = "INSERT INTO admin_logs (admin_user_id, action, target_id, target_title, reason) VALUES (?, ?, ?, ?, ?)";
            $logStmt = $db->prepare($logQuery);
            $logStmt->execute([$user['id'], 'DELETE_POST', $postId, $postInfo['title'], $reason]);
        } catch (Exception $e) {
            // Ignore logging errors
        }
        
        $success = "Postingan '{$postInfo['title']}' berhasil dihapus!";
    } catch (Exception $e) {
        $error = "Gagal menghapus postingan: " . $e->getMessage();
    }
}

// Get all posts (lost-found + activities)
$allPosts = [];

// Get lost & found posts
$lfQuery = "SELECT 'lost-found' as type, lf.id, lf.title, lf.description, lf.created_at, 
                   CONCAT(u.first_name, ' ', u.last_name) as author, u.nim, u.email
            FROM lost_found_items lf 
            JOIN users u ON lf.user_id = u.id 
            WHERE u.is_active = 1
            ORDER BY lf.created_at DESC";
$lfStmt = $db->prepare($lfQuery);
$lfStmt->execute();
$lostFoundPosts = $lfStmt->fetchAll();

// Get activity posts
$actQuery = "SELECT 'activity' as type, a.id, a.title, a.description, a.created_at,
                    CONCAT(u.first_name, ' ', u.last_name) as author, u.nim, u.email
             FROM activities a 
             JOIN users u ON a.user_id = u.id 
             WHERE a.is_active = 1 AND u.is_active = 1
             ORDER BY a.created_at DESC";
$actStmt = $db->prepare($actQuery);
$actStmt->execute();
$activityPosts = $actStmt->fetchAll();

// Combine and sort by date
$allPosts = array_merge($lostFoundPosts, $activityPosts);
usort($allPosts, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Get basic stats
$statsQuery = "SELECT 
    (SELECT COUNT(*) FROM lost_found_items) as total_lf,
    (SELECT COUNT(*) FROM activities WHERE is_active = 1) as total_activities,
    (SELECT COUNT(*) FROM users WHERE is_active = 1) as total_users";
$statsStmt = $db->prepare($statsQuery);
$statsStmt->execute();
$stats = $statsStmt->fetch();
?>
