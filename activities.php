<?php
require_once 'includes/auth.php';
require_once 'includes/data/activities_data.php';

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
    <link rel="stylesheet" href="assets/css/notifications.css">
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
                const alertType = '<?= $messageType ?>';
                const alertMessage = <?= json_encode($message) ?>;
                
                // notification Berhasil/tidak
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