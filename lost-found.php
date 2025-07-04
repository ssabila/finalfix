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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    if (!$auth->isLoggedIn()) {
        $message = 'Anda harus login terlebih dahulu';
        $messageType = 'error';
    } else {
        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'type' => $_POST['type'] ?? '',
            'category_id' => $_POST['category_id'] ?? '',
            'location' => trim($_POST['location'] ?? ''),
            'date_occurred' => $_POST['date_occurred'] ?? ''
        ];
        
        if (empty($data['title']) || empty($data['description']) || empty($data['type']) || 
            empty($data['category_id']) || empty($data['location']) || empty($data['date_occurred'])) {
            $message = 'Semua field wajib diisi';
            $messageType = 'error';
        } else {
            // Handle image upload
            $imagePath = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/lost-found/';
                
                // Buat direktori jika belum ada
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
                            
                            // Upload file langsung tanpa resize
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
                    $insertQuery = "INSERT INTO lost_found_items (user_id, category_id, title, description, type, location, date_occurred, contact_info, image) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($insertQuery);
                    
                    if ($stmt->execute([$user['id'], $data['category_id'], $data['title'], $data['description'], 
                                       $data['type'], $data['location'], $data['date_occurred'], $user['phone'], $imagePath])) {
                        $message = 'Laporan berhasil ditambahkan!';
                        $messageType = 'success';
                        
                        // Reset form data
                        $_POST = [];
                    } else {
                        $message = 'Gagal menambahkan laporan ke database.';
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
    $categoriesQuery = "SELECT * FROM categories WHERE type = 'lost_found' ORDER BY name ASC";
    $categoriesStmt = $db->prepare($categoriesQuery);
    $categoriesStmt->execute();
    $categories = $categoriesStmt->fetchAll();
} catch (Exception $e) {
    $categories = [];
    error_log("Error fetching categories: " . $e->getMessage());
}

// Get lost & found items with filters
$whereConditions = ['u.is_active = 1'];
$params = [];

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = '%' . trim($_GET['search']) . '%';
    $whereConditions[] = '(lf.title LIKE ? OR lf.description LIKE ? OR lf.location LIKE ?)';
    $params = array_merge($params, [$search, $search, $search]);
}

if (isset($_GET['category']) && !empty($_GET['category']) && is_numeric($_GET['category'])) {
    $whereConditions[] = 'lf.category_id = ?';
    $params[] = (int)$_GET['category'];
}

if (isset($_GET['type']) && !empty($_GET['type']) && in_array($_GET['type'], ['hilang', 'ditemukan'])) {
    $whereConditions[] = 'lf.type = ?';
    $params[] = $_GET['type'];
}

$whereClause = implode(' AND ', $whereConditions);

try {
    $itemsQuery = "SELECT lf.*, u.first_name, u.last_name, u.phone as user_phone, c.name as category_name,
                   CONCAT(u.first_name, ' ', u.last_name) as user_name
                   FROM lost_found_items lf
                   JOIN users u ON lf.user_id = u.id
                   JOIN categories c ON lf.category_id = c.id
                   WHERE $whereClause
                   ORDER BY lf.created_at DESC";

    $itemsStmt = $db->prepare($itemsQuery);
    $itemsStmt->execute($params);
    $items = $itemsStmt->fetchAll();
} catch (Exception $e) {
    $items = [];
    error_log("Error fetching items: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost & Found - E-Statmad</title>
    <link rel="icon" href="assets/images/logo.png" type="image/png">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/lost-found.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="assets/js/main.js"></script>
    <script src="assets/js/lost-found.js"></script>
</head>
<body>
    <!-- Navigation -->
    <?php include('assets/php/navbar.php'); ?>

    <!-- Header -->
    <section class="page-header">
        <div class="container">
            <h1><i class="fas fa-search"></i> Lost & Found</h1>
            <p>Temukan barang hilang atau laporkan barang yang Anda temukan</p>
        </div>
    </section>

    <!-- Filters -->
    <section class="filters">
        <div class="container">
            <form method="GET" class="filter-bar">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Cari barang..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
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
                    <select name="type">
                        <option value="">Semua Status</option>
                        <option value="hilang" <?= (($_GET['type'] ?? '') === 'hilang') ? 'selected' : '' ?>>Hilang</option>
                        <option value="ditemukan" <?= (($_GET['type'] ?? '') === 'ditemukan') ? 'selected' : '' ?>>Ditemukan</option>
                    </select>
                </div>
            </form>
        </div>
    </section>

    <!-- Lost & Found Grid -->
    <section class="lost-found-grid">
        <div class="container">
            <div class="grid-container">
                <?php if (empty($items)): ?>
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h3>Tidak ada item ditemukan</h3>
                        <p>Belum ada laporan yang sesuai dengan filter Anda</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <div class="lost-found-item" data-id="<?= $item['id'] ?>" onclick="showItemDetail(<?= $item['id'] ?>)">
                            
                            <?php 
                            // Cek apakah ada gambar dan file exists
                            $hasImage = !empty($item['image']) && file_exists($item['image']);
                            $imageClass = $hasImage ? 'has-image' : '';
                            
                            // Tentukan icon berdasarkan kategori
                            $iconMap = [
                                'elektronik' => 'laptop',
                                'aksesoris' => 'glasses', 
                                'pakaian' => 'tshirt',
                                'buku' => 'book',
                                'alat tulis' => 'pen',
                                'tas' => 'briefcase',
                                'sepatu' => 'shoe-prints',
                                'perhiasan' => 'gem',
                                'kendaraan' => 'car'
                            ];
                            
                            $categoryLower = strtolower($item['category_name']);
                            $icon = isset($iconMap[$categoryLower]) ? $iconMap[$categoryLower] : 'box';
                            ?>
                            
                            <div class="item-image <?= $imageClass ?>">
                                <?php if ($hasImage): ?>
                                    <img src="<?= htmlspecialchars($item['image']) ?>" 
                                         alt="<?= htmlspecialchars($item['title']) ?>" 
                                         loading="lazy"
                                         class="item-img"
                                         onerror="this.parentElement.classList.remove('has-image'); this.style.display='none';">
                                <?php else: ?>
                                    <i class="fas fa-<?= $icon ?> item-icon"></i>
                                <?php endif; ?>
                                
                                <div class="item-status status-<?= $item['type'] ?>">
                                    <?= $item['type'] === 'hilang' ? 'HILANG' : 'DITEMUKAN' ?>
                                </div>
                            </div>
                            
                            <div class="item-content">
                                <div class="item-category">
                                    <?= htmlspecialchars($item['category_name']) ?>
                                </div>
                                
                                <h3 class="item-title"><?= htmlspecialchars($item['title']) ?></h3>
                                
                                <div class="item-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-calendar"></i>
                                        <span><?= date('d M Y', strtotime($item['date_occurred'])) ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?= htmlspecialchars($item['location']) ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-user"></i>
                                        <span><?= htmlspecialchars($item['user_name']) ?></span>
                                    </div>
                                </div>
                                
                                <p class="item-description">
                                    <?= htmlspecialchars(strlen($item['description']) > 100 ? substr($item['description'], 0, 100) . '...' : $item['description']) ?>
                                </p>
                                
                                <div class="item-actions">
                                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $item['contact_info']) ?>" 
                                       target="_blank" 
                                       class="contact-btn"
                                       onclick="event.stopPropagation();">
                                        <i class="fab fa-whatsapp"></i>
                                        Hubungi
                                    </a>
                                    <div class="item-owner">
                                        oleh <?= htmlspecialchars($item['user_name']) ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Hidden data untuk modal -->
                            <script type="application/json" class="item-data">
                                {
                                    "id": <?= $item['id'] ?>,
                                    "title": <?= json_encode($item['title']) ?>,
                                    "description": <?= json_encode($item['description']) ?>,
                                    "type": <?= json_encode($item['type']) ?>,
                                    "category_name": <?= json_encode($item['category_name']) ?>,
                                    "location": <?= json_encode($item['location']) ?>,
                                    "date_occurred": <?= json_encode($item['date_occurred']) ?>,
                                    "user_name": <?= json_encode($item['user_name']) ?>,
                                    "contact_info": <?= json_encode($item['contact_info']) ?>,
                                    "image": <?= json_encode($item['image'] ?? '') ?>,
                                    "created_at": <?= json_encode($item['created_at']) ?>
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
        <div class="modal-content detail-modal-content">
            <div class="modal-header">
                <h2 id="detail-modal-title">Detail Barang</h2>
                <button onclick="closeModal('detail-modal')" class="close-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="detail-modal-body" id="detail-modal-body">
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
                <h2>Tambah Laporan Lost & Found</h2>
                <button onclick="closeModal('add-modal')" class="close-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form class="modal-form" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="add_item" value="1">
                
                <div class="form-group">
                    <label for="type">Jenis Laporan</label>
                    <select id="type" name="type" required>
                        <option value="">Pilih jenis laporan</option>
                        <option value="hilang">Barang Hilang</option>
                        <option value="ditemukan">Barang Ditemukan</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="title">Nama Barang</label>
                    <input type="text" id="title" name="title" placeholder="Contoh: Laptop ASUS ROG" required maxlength="255">
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
                    <textarea id="description" name="description" rows="4" placeholder="Jelaskan ciri-ciri barang secara detail..." required maxlength="1000"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="location">Lokasi</label>
                    <input type="text" id="location" name="location" placeholder="Contoh: Perpustakaan Lantai 2" required maxlength="255">
                </div>
                
                <div class="form-group">
                    <label for="date_occurred">Tanggal Kejadian</label>
                    <input type="date" id="date_occurred" name="date_occurred" required max="<?= date('Y-m-d') ?>">
                </div>
                
                <div class="form-group">
                    <label for="image" class="optional">Foto Barang</label>
                    <input type="file" id="image" name="image" accept="image/jpeg,image/jpg,image/png,image/gif" onchange="previewImage(this)">
                    <small>Format: JPG, PNG, GIF. Maksimal 5MB.</small>
                    <div class="image-preview" id="image-preview" style="display: none;">
                        <img id="preview-img" alt="Preview" class="preview-image">
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
                        Tambah Laporan
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <?php include('assets/php/footer.php'); ?>

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
