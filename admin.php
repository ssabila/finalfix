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
    <link rel="icon" href="assets/images/logo.png" type="image/png">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="preloader">
            <div class="preloader-container">
                <img src="assets/images/logo.png" alt="E-Statmad Logo" class="preloader-logo">
                <div class="preloader-spinner"></div>
            </div>
    </div>

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

    <script src="assets/js/main.js"></script>
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