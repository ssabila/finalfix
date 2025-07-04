<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

$auth = new Auth();
$user = $auth->getCurrentUser();
$message = '';
$messageType = '';

$database = new Database();
$db = $database->getConnection();

// Handle form submission 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_activity'])) {
    if (!$auth->isLoggedIn()) {
        $message = 'Anda harus login terlebih dahulu';
        $messageType = 'error';
    } else {
        $data = [
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'category_id' => $_POST['category_id'] ?? '',
            'event_date' => $_POST['event_date'] ?? '',
            'event_time' => $_POST['event_time'] ?? '',
            'location' => $_POST['location'] ?? '',
            'organizer' => $_POST['organizer'] ?? ''
        ];
        
        if (empty($data['title']) || empty($data['description']) || empty($data['category_id']) || 
            empty($data['event_date']) || empty($data['event_time']) || empty($data['location']) || empty($data['organizer'])) {
            $message = 'Semua field wajib diisi';
            $messageType = 'error';
        } else {
            // Handle image upload
            $imagePath = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/activities/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($fileExtension, $allowedExtensions)) {
                    // Validasi ukuran file (max 5MB)
                    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                        $message = 'Ukuran file terlalu besar! Maksimal 5MB.';
                        $messageType = 'error';
                    } else {
                        // Validasi adalah gambar yang valid
                        $imageInfo = getimagesize($_FILES['image']['tmp_name']);
                        if ($imageInfo === false) {
                            $message = 'File yang diupload bukan gambar yang valid.';
                            $messageType = 'error';
                        } else {
                            $fileName = uniqid() . '.' . $fileExtension;
                            $targetPath = $uploadDir . $fileName;
                            
                            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                                $imagePath = $targetPath;
                            } else {
                                $message = 'Gagal mengupload file gambar.';
                                $messageType = 'error';
                            }
                        }
                    }
                } else {
                    $message = 'Format file tidak didukung. Hanya JPG, PNG, dan GIF yang diperbolehkan.';
                    $messageType = 'error';
                }
            }
            
            // Lanjutkan insert jika tidak ada error
            if (empty($message)) {
                try {
                    $insertQuery = "INSERT INTO activities (user_id, category_id, title, description, event_date, event_time, location, organizer, contact_info, image) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($insertQuery);
                    
                    if ($stmt->execute([$user['id'], $data['category_id'], $data['title'], $data['description'], 
                                       $data['event_date'], $data['event_time'], $data['location'], $data['organizer'], $user['phone'], $imagePath])) {
                        $message = 'Kegiatan berhasil ditambahkan!';
                        $messageType = 'success';
                        
                        // Reset form data
                        $_POST = [];
                    } else {
                        $message = 'Gagal menambahkan kegiatan ke database.';
                        $messageType = 'error';
                        
                        // Hapus file jika insert gagal
                        if ($imagePath && file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                    }
                } catch (Exception $e) {
                    $message = 'Terjadi kesalahan: ' . $e->getMessage();
                    $messageType = 'error';
                    
                    // Hapus file jika terjadi error
                    if ($imagePath && file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }
            }
        }
    }
}

// Get categories
try {
    $categoriesQuery = "SELECT * FROM categories WHERE type = 'activity' ORDER BY name ASC";
    $categoriesStmt = $db->prepare($categoriesQuery);
    $categoriesStmt->execute();
    $categories = $categoriesStmt->fetchAll();
} catch (Exception $e) {
    $categories = [];
    error_log("Error fetching categories: " . $e->getMessage());
}

// Get activities with filters
$whereConditions = ['a.is_active = 1', 'u.is_active = 1'];
$params = [];

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = '%' . trim($_GET['search']) . '%';
    $whereConditions[] = '(a.title LIKE ? OR a.description LIKE ? OR a.organizer LIKE ?)';
    $params = array_merge($params, [$search, $search, $search]);
}

if (isset($_GET['category']) && !empty($_GET['category']) && is_numeric($_GET['category'])) {
    $whereConditions[] = 'a.category_id = ?';
    $params[] = (int)$_GET['category'];
}

$whereClause = implode(' AND ', $whereConditions);

try {
    $activitiesQuery = "SELECT a.*, u.first_name, u.last_name, u.phone as user_phone, c.name as category_name,
                        CONCAT(u.first_name, ' ', u.last_name) as user_name
                        FROM activities a
                        JOIN users u ON a.user_id = u.id
                        JOIN categories c ON a.category_id = c.id
                        WHERE $whereClause
                        ORDER BY a.event_date ASC, a.event_time ASC";

    $activitiesStmt = $db->prepare($activitiesQuery);
    $activitiesStmt->execute($params);
    $activities = $activitiesStmt->fetchAll();
} catch (Exception $e) {
    $activities = [];
    error_log("Error fetching activities: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kegiatan Mahasiswa - E-Statmad</title>
    <link rel="icon" href="assets/images/logo.png" type="image/png">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/lost-found.css">
    <link rel="stylesheet" href="assets/css/activities.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <?php include('assets/php/navbar.php'); ?>

    <!-- Header -->
    <section class="page-header">
        <div class="container">
            <h1><i class="fas fa-calendar-alt"></i> Kegiatan Mahasiswa</h1>
            <p>Ikuti berbagai kegiatan menarik di kampus</p>
        </div>
    </section>

    <!-- Filters -->
    <section class="filters">
        <div class="container">
            <form method="GET" class="filter-bar">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Cari kegiatan..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </div>
                <div class="filter-options">
                    <select name="category">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= (($_GET['category'] ?? '') == $category['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </section>

    <!-- Activities Grid -->
    <section class="activities-grid">
        <div class="container">
            <div class="grid-container">
                <?php if (empty($activities)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <h3>Tidak ada kegiatan ditemukan</h3>
                        <p>Belum ada kegiatan yang sesuai dengan filter Anda</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($activities as $activity): ?>
                        <div class="activity-item" data-id="<?= $activity['id'] ?>" onclick="showActivityDetail(<?= $activity['id'] ?>)">
                            <div class="activity-image">
                                <?php if ($activity['image'] && file_exists($activity['image'])): ?>
                                    <img src="<?= htmlspecialchars($activity['image']) ?>" alt="<?= htmlspecialchars($activity['title']) ?>">
                                <?php else: ?>
                                    <i class="fas fa-calendar-alt"></i>
                                <?php endif; ?>
                                <div class="activity-date">
                                    <span class="day"><?= date('d', strtotime($activity['event_date'])) ?></span>
                                    <span class="month"><?= date('M', strtotime($activity['event_date'])) ?></span>
                                </div>
                            </div>
                            <div class="activity-content">
                                <div class="activity-category">
                                    <?= htmlspecialchars($activity['category_name']) ?>
                                </div>
                                <h3 class="activity-title"><?= htmlspecialchars($activity['title']) ?></h3>
                                <div class="activity-meta">
                                    <div class="meta-row">
                                        <i class="fas fa-clock"></i>
                                        <span><?= date('d M Y', strtotime($activity['event_date'])) ?>, <?= date('H:i', strtotime($activity['event_time'])) ?></span>
                                    </div>
                                    <div class="meta-row">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?= htmlspecialchars($activity['location']) ?></span>
                                    </div>
                                    <div class="meta-row">
                                        <i class="fas fa-user"></i>
                                        <span><?= htmlspecialchars($activity['organizer']) ?></span>
                                    </div>
                                </div>
                                <p class="activity-description"><?= htmlspecialchars(strlen($activity['description']) > 150 ? substr($activity['description'], 0, 150) . '...' : $activity['description']) ?></p>
                                <div class="activity-organizer">oleh <?= htmlspecialchars($activity['user_name']) ?></div>
                            </div>

                            <!-- Hidden data untuk modal -->
                            <script type="application/json" class="activity-data">
                                {
                                    "id": <?= $activity['id'] ?>,
                                    "title": <?= json_encode($activity['title']) ?>,
                                    "description": <?= json_encode($activity['description']) ?>,
                                    "category_name": <?= json_encode($activity['category_name']) ?>,
                                    "event_date": <?= json_encode($activity['event_date']) ?>,
                                    "event_time": <?= json_encode($activity['event_time']) ?>,
                                    "location": <?= json_encode($activity['location']) ?>,
                                    "organizer": <?= json_encode($activity['organizer']) ?>,
                                    "user_name": <?= json_encode($activity['user_name']) ?>,
                                    "contact_info": <?= json_encode($activity['contact_info']) ?>,
                                    "image": <?= json_encode($activity['image'] ?? '') ?>,
                                    "created_at": <?= json_encode($activity['created_at']) ?>
                                }
                            </script>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Detail Modal -->
    <div class="modal" id="detail-modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2 id="detail-modal-title">Detail Kegiatan</h2>
                <button onclick="closeModal('detail-modal')" class="close-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="detail-modal-body">
                <!-- Content akan diisi oleh JavaScript -->
            </div>
        </div>
    </div>

    <!-- Add Button (only for logged in users) -->
    <?php if ($user): ?>
    <div class="add-button" onclick="openModal('add-modal')">
        <i class="fas fa-plus"></i>
    </div>

    <!-- Add Modal -->
    <div class="modal" id="add-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Tambah Kegiatan</h2>
                <button onclick="closeModal('add-modal')" class="close-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form class="modal-form" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="add_activity" value="1">
                
                <div class="form-group">
                    <label for="title">Judul Kegiatan</label>
                    <input type="text" id="title" name="title" placeholder="Contoh: Workshop Python Programming" required>
                </div>
                
                <div class="form-group">
                    <label for="category_id">Kategori</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Pilih kategori</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="description">Deskripsi</label>
                    <textarea id="description" name="description" rows="4" placeholder="Jelaskan detail kegiatan, materi yang akan dibahas, dan informasi penting lainnya..." required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="event_date">Tanggal</label>
                        <input type="date" id="event_date" name="event_date" required>
                    </div>
                    <div class="form-group">
                        <label for="event_time">Waktu</label>
                        <input type="time" id="event_time" name="event_time" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="location">Lokasi</label>
                    <input type="text" id="location" name="location" placeholder="Contoh: Auditorium Utama STIS" required>
                </div>
                
                <div class="form-group">
                    <label for="organizer">Penyelenggara</label>
                    <input type="text" id="organizer" name="organizer" placeholder="Contoh: Himpunan Mahasiswa Statistika" required>
                </div>
                
                <div class="form-group">
                    <label for="image" class="optional">Poster/Gambar Kegiatan</label>
                    <input type="file" id="image" name="image" accept="image/jpeg,image/jpg,image/png,image/gif" onchange="previewImage(this)">
                    <small>Format: JPG, PNG, GIF. Maksimal 5MB.</small>
                    <div class="image-preview" id="image-preview" style="display: none;">
                        <img id="preview-img" alt="Preview">
                        <br>
                        <button type="button" class="remove-image" onclick="removeImage()">Hapus Foto</button>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" onclick="closeModal('add-modal')" class="btn-secondary">
                        Batal
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-plus"></i>
                        Tambah Kegiatan
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <?php include('assets/php/footer.php'); ?>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/activities.js"></script>
    <?php if ($message): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const alertType = '<?= $messageType ?>' === 'success' ? 'success' : 'error';
                const alertMessage = <?= json_encode($message) ?>;
                
                // Simple alert - replace with your preferred notification system
                if (alertType === 'success') {
                    alert('✅ ' + alertMessage);
                } else {
                    alert('❌ ' + alertMessage);
                }
            });
        </script>
    <?php endif; ?>
</body>
</html>