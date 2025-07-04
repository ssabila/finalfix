<?php
// admin.php - Buat file baru di root directory
require_once 'includes/auth.php';
require_once 'config/database.php';

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
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - E-Statmad</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
            text-align: center;
            margin-top: 80px;
        }
        .admin-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .admin-content {
            max-width: 1200px;
            margin: -20px auto 0;
            padding: 0 20px;
            min-height: 70vh;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #6c757d;
            font-weight: 500;
        }
        .posts-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        .section-header {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 20px 25px;
            font-weight: bold;
            font-size: 1.2rem;
            color: #495057;
            border-bottom: 1px solid #dee2e6;
        }
        .posts-table {
            width: 100%;
            border-collapse: collapse;
        }
        .posts-table th, .posts-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f1f3f4;
            vertical-align: top;
        }
        .posts-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        .posts-table tr:hover {
            background: #f8f9fa;
        }
        .post-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .post-excerpt {
            color: #6c757d;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        .author-info {
            font-weight: 500;
        }
        .author-meta {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .post-type {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .type-lost-found {
            background: #e3f2fd;
            color: #1976d2;
        }
        .type-activity {
            background: #e8f5e8;
            color: #388e3c;
        }
        .btn-delete {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220,53,69,0.4);
        }
        .alert {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 10px;
            font-weight: 500;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(5px);
        }
        .modal-content {
            background: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        .modal-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .modal-header h3 {
            color: #dc3545;
            margin-bottom: 10px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
        }
        .form-group select:focus {
            border-color: #667eea;
            outline: none;
        }
        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: transform 0.3s ease;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
        }
        .btn-primary:hover, .btn-secondary:hover {
            transform: translateY(-2px);
        }
        .delete-preview {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #dc3545;
        }
        .delete-preview h4 {
            color: #dc3545;
            margin-bottom: 5px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <!-- Include navigation yang sudah diupdate -->
    <?php include 'assets/php/navbar.php'; ?>

    <!-- Admin Header -->
    <div class="admin-header">
        <h1><i class="fas fa-shield-alt"></i> Admin Dashboard</h1>
        <p>Kelola semua postingan dan monitor aktivitas platform E-Statmad</p>
    </div>

    <!-- Admin Content -->
    <div class="admin-content">
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_lf'] ?></div>
                <div class="stat-label">Total Lost & Found</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_activities'] ?></div>
                <div class="stat-label">Total Aktivitas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_users'] ?></div>
                <div class="stat-label">Total Pengguna</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count($allPosts) ?></div>
                <div class="stat-label">Total Postingan</div>
            </div>
        </div>

        <!-- Posts Section -->
        <div class="posts-section">
            <div class="section-header">
                <i class="fas fa-list"></i> Semua Postingan - Kontrol Admin
            </div>
            
            <?php if (count($allPosts) > 0): ?>
                <table class="posts-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;">ID</th>
                            <th style="width: 35%;">Judul & Konten</th>
                            <th style="width: 20%;">Penulis</th>
                            <th style="width: 15%;">Jenis</th>
                            <th style="width: 15%;">Tanggal</th>
                            <th style="width: 10%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allPosts as $post): ?>
                        <tr>
                            <td><strong><?= $post['id'] ?></strong></td>
                            <td>
                                <div class="post-title"><?= htmlspecialchars($post['title']) ?></div>
                                <div class="post-excerpt">
                                    <?= htmlspecialchars(substr($post['description'], 0, 100)) ?><?= strlen($post['description']) > 100 ? '...' : '' ?>
                                </div>
                            </td>
                            <td>
                                <div class="author-info"><?= htmlspecialchars($post['author']) ?></div>
                                <div class="author-meta">
                                    NIM: <?= htmlspecialchars($post['nim']) ?><br>
                                    <?= htmlspecialchars($post['email']) ?>
                                </div>
                            </td>
                            <td>
                                <span class="post-type <?= $post['type'] === 'lost-found' ? 'type-lost-found' : 'type-activity' ?>">
                                    <?= $post['type'] === 'lost-found' ? 'Lost & Found' : 'Aktivitas' ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($post['created_at'])) ?></td>
                            <td>
                                <button class="btn-delete" onclick="deletePost(<?= $post['id'] ?>, '<?= $post['type'] ?>', '<?= htmlspecialchars($post['title'], ENT_QUOTES) ?>', '<?= htmlspecialchars($post['author'], ENT_QUOTES) ?>')">
                                    <i class="fas fa-trash"></i> Hapus
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>Tidak Ada Postingan</h3>
                    <p>Belum ada postingan yang dapat dikelola.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Konfirmasi Penghapusan</h3>
                <p>Tindakan ini tidak dapat dibatalkan!</p>
            </div>
            
            <div class="delete-preview">
                <h4 id="deleteTitle">Judul Postingan</h4>
                <p>Oleh: <span id="deleteAuthor">Nama Penulis</span></p>
            </div>
            
            <form method="POST">
                <input type="hidden" id="deletePostId" name="post_id">
                <input type="hidden" id="deletePostType" name="post_type">
                <input type="hidden" name="delete_post" value="1">
                
                <div class="form-group">
                    <label for="reason">Alasan Penghapusan: *</label>
                    <select name="reason" id="reason" required>
                        <option value="">Pilih alasan penghapusan...</option>
                        <option value="Melanggar aturan komunitas">Melanggar aturan komunitas</option>
                        <option value="Spam atau tidak relevan">Spam atau tidak relevan</option>
                        <option value="Konten tidak pantas">Konten tidak pantas</option>
                        <option value="Informasi palsu atau menyesatkan">Informasi palsu atau menyesatkan</option>
                        <option value="Duplikat postingan">Duplikat postingan</option>
                        <option value="Pelanggaran hak cipta">Pelanggaran hak cipta</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal()">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-trash"></i> Hapus Postingan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function deletePost(id, type, title, author) {
            document.getElementById('deletePostId').value = id;
            document.getElementById('deletePostType').value = type;
            document.getElementById('deleteTitle').textContent = title;
            document.getElementById('deleteAuthor').textContent = author;
            document.getElementById('deleteModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target == modal) {
                closeModal();
            }
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>