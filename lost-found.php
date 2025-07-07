<?php
require_once 'includes/auth.php';
require_once 'includes/data/lost-found_data.php';

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
    <link rel="stylesheet" href="assets/css/notifications.css">
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
                        <option value="kehilangan" <?= (($_GET['type'] ?? '') === 'kehilangan') ? 'selected' : '' ?>>Kehilangan</option>
                        <option value="penemuan" <?= (($_GET['type'] ?? '') === 'penemuan') ? 'selected' : '' ?>>Penemuan</option>
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
                                    <?= $item['type'] === 'kehilangan' ? 'KEHILANGAN' : 'PENEMUAN' ?>
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
                        <option value="kehilangan">Kehilangan Barang</option>
                        <option value="penemuan">Penemuan Barang</option>
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
                
                // notifikasi berhasil/tidak
                if (alertType === 'success') {
                    showCustomNotification('✅ ' + alertMessage, 'success');
                } else {
                    showCustomNotification('❌ ' + alertMessage, 'error');
                }
            });
        </script>
    <?php endif; ?>
</body>
</html>
