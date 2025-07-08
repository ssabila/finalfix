<?php
require_once 'includes/auth.php';
require_once 'includes/data/profile_data.php';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - E-Statmad</title>
    <link rel="icon" href="assets/images/logo.png" type="image/png">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/profile.css">
    <link rel="stylesheet" href="assets/css/notifications.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <?php include('assets/php/navbar.php'); ?>

    <!-- Profile Header -->
    <section class="profile-header">
        <div class="container">
            <div class="profile-info">
                <div class="profile-avatar">
                    <?php 
                    $avatarUrl = $auth->getAvatarUrl($user);
                    ?>
                    
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Profile Avatar" id="current-avatar">
                    <?php else: ?>
                        <img src="assets/images/default-avatar.png" alt="Profile Avatar" id="current-avatar" 
                            onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDEyMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMjAiIGhlaWdodD0iMTIwIiBmaWxsPSIjRjhGOUZBIi8+CjxjaXJjbGUgY3g9IjYwIiBjeT0iNDAiIHI9IjIwIiBmaWxsPSIjN0Y4QzhEIi8+CjxwYXRoIGQ9Ik0yMCA5NUMyMCA4MC4wODc2IDMyLjA4NzYgNjggNDcgNjhINzNDODcuOTEyNCA2OCAxMDAgODAuMDg3NiAxMDAgOTVWMTIwSDIwVjk1WiIgZmlsbD0iIzdGOEM4RCIvPgo8L3N2Zz4K';">
                    <?php endif; ?>
                    
                    <button class="change-avatar-btn" onclick="openAvatarModal()" title="Ubah Foto Profil">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
                <div class="profile-details">
                    <h1><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h1>
                    <p>NIM: <?= htmlspecialchars($user['nim']) ?></p>
                    <p><?= htmlspecialchars($user['email']) ?></p>
                    <button onclick="openEditProfileModal()" class="btn-primary edit-profile-btn">
                        <i class="fas fa-edit"></i>
                        Edit Profil
                    </button>
                </div>
            </div>
            <div class="profile-stats">
                <div class="stat-item">
                    <span class="stat-number"><?= $totalLostFound ?></span>
                    <span class="stat-label">Lost & Found</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= $totalActivities ?></span>
                    <span class="stat-label">Kegiatan</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= $resolvedItems ?></span>
                    <span class="stat-label">Terselesaikan</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Profile Tabs -->
    <section class="profile-tabs">
        <div class="container">
            <div class="tab-navigation">
                <button class="tab-btn active" data-tab="lost-found">
                    <i class="fas fa-search"></i>
                    Lost & Found Saya
                </button>
                <button class="tab-btn" data-tab="activities">
                    <i class="fas fa-calendar"></i>
                    Kegiatan Saya
                </button>
            </div>

            <!-- Lost & Found Tab -->
            <div class="tab-content active" id="lost-found-tab">
                <div class="tab-header">
                    <h2>Laporan Lost & Found Saya</h2>
                    <button onclick="openModal('lost-found-modal')" class="btn-primary">
                        <i class="fas fa-plus"></i>
                        Tambah Laporan
                    </button>
                </div>
                <div class="items-grid">
                    <?php if (empty($userLostFound)): ?>
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h3>Belum Ada Laporan</h3>
                            <p>Anda belum membuat laporan lost & found apapun</p>
                            <button onclick="openModal('lost-found-modal')" class="btn-primary">
                                <i class="fas fa-plus"></i>
                                Tambah Laporan
                            </button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($userLostFound as $item): ?>
                            <div class="profile-item" data-id="<?= $item['id'] ?>" data-type="lost-found">
                                <div class="item-actions-overlay">
                                    <button class="action-btn edit-btn" onclick="editItem(<?= $item['id'] ?>, 'lost-found')" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn delete-btn" onclick="deleteItem(<?= $item['id'] ?>, 'lost-found', '<?= htmlspecialchars($item['title']) ?>')" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                
                                <div class="item-image">
                                    <?php if ($item['image']): ?>
                                        <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                                    <?php else: ?>
                                        <i class="fas fa-<?= $item['type'] === 'kehilangan' ? 'exclamation-triangle' : 'check-circle' ?>"></i>
                                    <?php endif; ?>
                                    <div class="item-status status-<?= $item['type'] ?>">
                                        <?= $item['type'] === 'kehilangan' ? 'Kehilangan' : 'Penemuan' ?>
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
                                    </div>
                                    <p class="item-description"><?= htmlspecialchars(substr($item['description'], 0, 100)) ?>...</p>
                                </div>
                                
                                <!-- Hidden data for editing -->
                                <script type="application/json" class="item-edit-data">
                                    {
                                        "id": <?= $item['id'] ?>,
                                        "title": <?= json_encode($item['title']) ?>,
                                        "description": <?= json_encode($item['description']) ?>,
                                        "type": <?= json_encode($item['type']) ?>,
                                        "category_id": <?= $item['category_id'] ?>,
                                        "location": <?= json_encode($item['location']) ?>,
                                        "date_occurred": <?= json_encode($item['date_occurred']) ?>,
                                        "image": <?= json_encode($item['image'] ?? '') ?>
                                    }
                                </script>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Activities Tab -->
            <div class="tab-content" id="activities-tab">
                <div class="tab-header">
                    <h2>Kegiatan yang Saya Buat</h2>
                    <button onclick="openModal('activity-modal')" class="btn-primary">
                        <i class="fas fa-plus"></i>
                        Tambah Kegiatan
                    </button>
                </div>
                <div class="items-grid">
                    <?php if (empty($userActivities)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h3>Belum Ada Kegiatan</h3>
                            <p>Anda belum membuat kegiatan apapun</p>
                            <button onclick="openModal('activity-modal')" class="btn-primary">
                                <i class="fas fa-plus"></i>
                                Tambah Kegiatan
                            </button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($userActivities as $activity): ?>
                            <div class="profile-item" data-id="<?= $activity['id'] ?>" data-type="activity">
                                <div class="item-actions-overlay">
                                    <button class="action-btn edit-btn" onclick="editItem(<?= $activity['id'] ?>, 'activity')" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn delete-btn" onclick="deleteItem(<?= $activity['id'] ?>, 'activity', '<?= htmlspecialchars($activity['title']) ?>')" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                
                                <div class="item-image">
                                    <?php if ($activity['image']): ?>
                                        <img src="<?= htmlspecialchars($activity['image']) ?>" alt="<?= htmlspecialchars($activity['title']) ?>">
                                    <?php else: ?>
                                        <i class="fas fa-calendar-alt"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="item-content">
                                    <div class="item-category">
                                        <?= htmlspecialchars($activity['category_name']) ?>
                                    </div>
                                    <h3 class="item-title"><?= htmlspecialchars($activity['title']) ?></h3>
                                    <div class="item-meta">
                                        <div class="meta-item">
                                            <i class="fas fa-calendar"></i>
                                            <span><?= date('d M Y', strtotime($activity['event_date'])) ?> <?= date('H:i', strtotime($activity['event_time'])) ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?= htmlspecialchars($activity['location']) ?></span>
                                        </div>
                                    </div>
                                    <p class="item-description"><?= htmlspecialchars(substr($activity['description'], 0, 100)) ?>...</p>
                                </div>
                                
                                <!-- Hidden data for editing -->
                                <script type="application/json" class="item-edit-data">
                                    {
                                        "id": <?= $activity['id'] ?>,
                                        "title": <?= json_encode($activity['title']) ?>,
                                        "description": <?= json_encode($activity['description']) ?>,
                                        "category_id": <?= $activity['category_id'] ?>,
                                        "event_date": <?= json_encode($activity['event_date']) ?>,
                                        "event_time": <?= json_encode($activity['event_time']) ?>,
                                        "location": <?= json_encode($activity['location']) ?>,
                                        "organizer": <?= json_encode($activity['organizer']) ?>,
                                        "image": <?= json_encode($activity['image'] ?? '') ?>
                                    }
                                </script>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="delete-modal">
        <div class="modal-content delete-modal-content">
            <div class="modal-header">
                <h2>Konfirmasi Hapus</h2>
                <button onclick="closeModal('delete-modal')" class="close-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="delete-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Apakah Anda yakin?</h3>
                    <p>Anda akan menghapus "<span id="delete-item-title"></span>"</p>
                    <p><strong>Tindakan ini tidak dapat dibatalkan!</strong></p>
                </div>
                
                <form id="delete-form" action="profile.php" method="POST">
                    <input type="hidden" name="item_id" id="delete-item-id">
                    <input type="hidden" name="item_type" id="delete-item-type">
                    <input type="hidden" name="delete_item" value="1"> 
                </form>
                
                <div class="form-actions">
                    <button type="button" onclick="closeModal('delete-modal')" class="btn-secondary">
                        <i class="fas fa-times"></i>
                        Batal
                    </button>
                    <button type="button" onclick="confirmDelete()" class="btn-danger">
                        <i class="fas fa-trash"></i>
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Lost & Found Modal -->
    <div class="modal" id="edit-lost-found-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Laporan Lost & Found</h2>
                <button onclick="closeModal('edit-lost-found-modal')" class="close-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form class="modal-form" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="edit_lost_found" value="1">
                <input type="hidden" name="item_id" id="edit-lf-id" value="">
                
                <div class="form-group">
                    <label for="edit_lf_type">Jenis Laporan</label>
                    <select id="edit_lf_type" name="type" required>
                        <option value="">Pilih jenis laporan</option>
                        <option value="kehilangan">Kehilangan Barang</option>
                        <option value="penemuan">Penemuan Barang</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_lf_title">Nama Barang</label>
                    <input type="text" id="edit_lf_title" name="title" placeholder="Contoh: Laptop ASUS ROG" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_lf_category_id">Kategori</label>
                    <select id="edit_lf_category_id" name="category_id" required>
                        <option value="">Pilih kategori</option>
                        <?php foreach ($lostFoundCategories as $category): ?>
                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_lf_description">Deskripsi</label>
                    <textarea id="edit_lf_description" name="description" rows="4" placeholder="Jelaskan ciri-ciri barang secara detail..." required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit_lf_location">Lokasi</label>
                    <input type="text" id="edit_lf_location" name="location" placeholder="Contoh: Perpustakaan Lantai 2" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_lf_date_occurred">Tanggal Kejadian</label>
                    <input type="date" id="edit_lf_date_occurred" name="date_occurred" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_lf_image" class="optional">Foto Barang (Opsional - kosongkan jika tidak ingin mengubah)</label>
                    <input type="file" id="edit_lf_image" name="image" accept="image/*" onchange="previewImage(this, 'edit-lf-preview')">
                    <div class="current-image" id="edit-lf-current-image" style="display: none;">
                        <p>Gambar saat ini:</p>
                        <img id="edit-lf-current-img" src="" alt="Current image" style="max-width: 150px; max-height: 150px; border-radius: 8px;">
                    </div>
                    <div class="image-preview" id="edit-lf-preview" style="display: none;">
                        <img id="edit-lf-preview-img" src="" alt="Preview">
                        <br>
                        <button type="button" class="remove-image" onclick="removeImage('edit_lf_image', 'edit-lf-preview')">Hapus Foto</button>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" onclick="closeModal('edit-lost-found-modal')" class="btn-secondary">
                        Batal
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Activity Modal -->
    <div class="modal" id="edit-activity-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Kegiatan</h2>
                <button onclick="closeModal('edit-activity-modal')" class="close-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form class="modal-form" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="edit_activity" value="1">
                <input type="hidden" name="item_id" id="edit-act-id" value="">
                
                <div class="form-group">
                    <label for="edit_act_title">Judul Kegiatan</label>
                    <input type="text" id="edit_act_title" name="title" placeholder="Contoh: Workshop Python Programming" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_act_category_id">Kategori</label>
                    <select id="edit_act_category_id" name="category_id" required>
                        <option value="">Pilih kategori</option>
                        <?php foreach ($activityCategories as $category): ?>
                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_act_description">Deskripsi</label>
                    <textarea id="edit_act_description" name="description" rows="4" placeholder="Jelaskan detail kegiatan, materi yang akan dibahas, dan informasi penting lainnya..." required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_act_event_date">Tanggal</label>
                        <input type="date" id="edit_act_event_date" name="event_date" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_act_event_time">Waktu</label>
                        <input type="time" id="edit_act_event_time" name="event_time" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_act_location">Lokasi</label>
                    <input type="text" id="edit_act_location" name="location" placeholder="Contoh: Auditorium Utama STIS" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_act_organizer">Penyelenggara</label>
                    <input type="text" id="edit_act_organizer" name="organizer" placeholder="Contoh: Himpunan Mahasiswa Statistika" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_act_image" class="optional">Poster/Gambar Kegiatan (Opsional - kosongkan jika tidak ingin mengubah)</label>
                    <input type="file" id="edit_act_image" name="image" accept="image/*" onchange="previewImage(this, 'edit-act-preview')">
                    <div class="current-image" id="edit-act-current-image" style="display: none;">
                        <p>Gambar saat ini:</p>
                        <img id="edit-act-current-img" src="" alt="Current image" style="max-width: 150px; max-height: 150px; border-radius: 8px;">
                    </div>
                    <div class="image-preview" id="edit-act-preview" style="display: none;">
                        <img id="edit-act-preview-img" src="" alt="Preview">
                        <br>
                        <button type="button" class="remove-image" onclick="removeImage('edit_act_image', 'edit-act-preview')">Hapus Foto</button>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" onclick="closeModal('edit-activity-modal')" class="btn-secondary">
                        Batal
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Avatar Change Modal -->
    <div class="modal" id="avatar-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Ubah Foto Profil</h2>
                <button onclick="closeModal('avatar-modal')" class="close-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form class="modal-form" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="change_avatar" value="1">
                
                <div class="avatar-upload-section">
                    <div class="current-avatar-preview">
                        <?php if (!empty($user['avatar']) && file_exists('uploads/avatars/' . $user['avatar'])): ?>
                            <img src="uploads/avatars/<?= htmlspecialchars($user['avatar']) ?>" alt="Current Avatar" id="current-avatar-preview">
                        <?php else: ?>
                            <div class="no-avatar-placeholder" id="current-avatar-preview">
                                <i class="fas fa-user"></i>
                                <span>Belum ada foto</span>
                            </div>
                        <?php endif; ?>
                        <div class="avatar-overlay">
                            <i class="fas fa-camera"></i>
                            <span>Foto Saat Ini</span>
                        </div>
                    </div>
                    
                    <div class="arrow-separator">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                    
                    <div class="new-avatar-preview" id="new-avatar-preview" style="display: none;">
                        <img id="new-avatar-img" src="" alt="New Avatar Preview">
                        <div class="avatar-overlay">
                            <i class="fas fa-check"></i>
                            <span>Foto Baru</span>
                        </div>
                    </div>
                    
                    <div class="no-preview-placeholder" id="no-preview-placeholder">
                        <i class="fas fa-image"></i>
                        <span>Preview foto baru</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="avatar">Pilih Foto Profil Baru</label>
                    <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/jpg,image/png,image/gif" onchange="previewAvatar(this)" required>
                    <small>Format: JPG, PNG, GIF. Maksimal 5MB. Foto akan dipotong menjadi persegi secara otomatis.</small>
                </div>
                
                <div class="form-actions">
                    <button type="button" onclick="closeModal('avatar-modal')" class="btn-secondary">
                        Batal
                    </button>
                    <button type="submit" class="btn-primary" id="save-avatar-btn" disabled>
                        <i class="fas fa-save"></i>
                        Simpan Foto
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lost & Found Modal -->
    <div class="modal" id="lost-found-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Tambah Laporan Lost & Found</h2>
                <button onclick="closeModal('lost-found-modal')" class="close-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form class="modal-form" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="add_lost_found" value="1">
                
                <div class="form-group">
                    <label for="lf_type">Jenis Laporan</label>
                    <select id="lf_type" name="type" required>
                        <option value="">Pilih jenis laporan</option>
                        <option value="kehilangan">Kehilangan Barang</option>
                        <option value="penemuan">Penemuan Barang</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="lf_title">Nama Barang</label>
                    <input type="text" id="lf_title" name="title" placeholder="Contoh: Laptop ASUS ROG" required>
                </div>
                
                <div class="form-group">
                    <label for="lf_category_id">Kategori</label>
                    <select id="lf_category_id" name="category_id" required>
                        <option value="">Pilih kategori</option>
                        <?php foreach ($lostFoundCategories as $category): ?>
                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="lf_description">Deskripsi</label>
                    <textarea id="lf_description" name="description" rows="4" placeholder="Jelaskan ciri-ciri barang secara detail..." required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="lf_location">Lokasi</label>
                    <input type="text" id="lf_location" name="location" placeholder="Contoh: Perpustakaan Lantai 2" required>
                </div>
                
                <div class="form-group">
                    <label for="lf_date_occurred">Tanggal Kejadian</label>
                    <input type="date" id="lf_date_occurred" name="date_occurred" required>
                </div>
                
                <div class="form-group">
                    <label for="lf_image" class="optional">Foto Barang</label>
                    <input type="file" id="lf_image" name="lf_image" accept="image/*" onchange="previewImage(this, 'lf-preview')">
                    <div class="image-preview" id="lf-preview" style="display: none;">
                        <img id="lf-preview-img" src="/placeholder.svg" alt="Preview">
                        <br>
                        <button type="button" class="remove-image" onclick="removeImage('lf_image', 'lf-preview')">Hapus Foto</button>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" onclick="closeModal('lost-found-modal')" class="btn-secondary">
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

    <!-- Activity Modal -->
    <div class="modal" id="activity-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Tambah Kegiatan</h2>
                <button onclick="closeModal('activity-modal')" class="close-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form class="modal-form" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="add_activity" value="1">
                
                <div class="form-group">
                    <label for="act_title">Judul Kegiatan</label>
                    <input type="text" id="act_title" name="title" placeholder="Contoh: Workshop Python Programming" required>
                </div>
                
                <div class="form-group">
                    <label for="act_category_id">Kategori</label>
                    <select id="act_category_id" name="category_id" required>
                        <option value="">Pilih kategori</option>
                        <?php foreach ($activityCategories as $category): ?>
                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="act_description">Deskripsi</label>
                    <textarea id="act_description" name="description" rows="4" placeholder="Jelaskan detail kegiatan, materi yang akan dibahas, dan informasi penting lainnya..." required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="act_event_date">Tanggal</label>
                        <input type="date" id="act_event_date" name="event_date" required>
                    </div>
                    <div class="form-group">
                        <label for="act_event_time">Waktu</label>
                        <input type="time" id="act_event_time" name="event_time" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="act_location">Lokasi</label>
                    <input type="text" id="act_location" name="location" placeholder="Contoh: Auditorium Utama STIS" required>
                </div>
                
                <div class="form-group">
                    <label for="act_organizer">Penyelenggara</label>
                    <input type="text" id="act_organizer" name="organizer" placeholder="Contoh: Himpunan Mahasiswa Statistika" required>
                </div>
                
                <div class="form-group">
                    <label for="act_image" class="optional">Poster/Gambar Kegiatan</label>
                    <input type="file" id="act_image" name="act_image" accept="image/*" onchange="previewImage(this, 'act-preview')">
                    <div class="image-preview" id="act-preview" style="display: none;">
                        <img id="act-preview-img" src="/placeholder.svg" alt="Preview">
                        <br>
                        <button type="button" class="remove-image" onclick="removeImage('act_image', 'act-preview')">Hapus Foto</button>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" onclick="closeModal('activity-modal')" class="btn-secondary">
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

    <!-- Edit Profile Modal -->
    <div class="modal" id="edit-profile-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Profil</h2>
                <button onclick="closeModal('edit-profile-modal')" class="close-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form class="modal-form" id="edit-profile-form" method="POST">
                <input type="hidden" name="edit_profile" value="1">
                
                <div class="form-group">
                    <label for="edit_first_name">Nama Depan</label>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" id="edit_first_name" name="first_name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_last_name">Nama Belakang</label>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" id="edit_last_name" name="last_name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_nim">NIM</label>
                    <div class="input-group">
                        <i class="fas fa-id-card"></i>
                        <input type="text" id="edit_nim" name="nim" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="edit_email" name="email" placeholder="contoh@stis.ac.id atau @bps.go.id" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_phone">Nomor WhatsApp</label>
                    <div class="input-group">
                        <i class="fas fa-phone"></i>
                        <input type="tel" id="edit_phone" name="phone" required>
                    </div>
                </div>
                
                <hr style="margin: 2rem 0; border: none; border-top: 1px solid var(--border-color);">
                
                <h3 style="margin-bottom: 1rem; color: var(--text-dark);">Ubah Password (Opsional)</h3>
                
                <div class="form-group">
                    <label for="current_password">Password Saat Ini</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="current_password" name="current_password" placeholder="Kosongkan jika tidak ingin mengubah password">
                        <button type="button" class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="new_password">Password Baru</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="new_password" name="new_password" placeholder="Kosongkan jika tidak ingin mengubah password">
                        <button type="button" class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password Baru</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Kosongkan jika tidak ingin mengubah password">
                        <button type="button" class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" onclick="closeModal('edit-profile-modal')" class="btn-secondary">
                        <i class="fas fa-times"></i>
                        Batal
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <?php include('assets/php/footer.php'); ?>


    <script>
    // Data user untuk JavaScript
    const userProfileData = {
        first_name: '<?= htmlspecialchars($user['first_name']) ?>',
        last_name: '<?= htmlspecialchars($user['last_name']) ?>',
        nim: '<?= htmlspecialchars($user['nim']) ?>',
        email: '<?= htmlspecialchars($user['email']) ?>',
        phone: '<?= htmlspecialchars($user['phone']) ?>'
    };
    </script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/profile.js"></script>

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

    <?php
        // Handle PHP messages untuk JavaScript
        $message = '';
        $messageType = '';

        if (isset($_GET['edited_activity']) && $_GET['edited_activity'] == '1') {
            if (isset($_SESSION['success_message'])) {
                $message = $_SESSION['success_message'];
                $messageType = 'success';
                unset($_SESSION['success_message']);
            }
        }

        if (isset($_GET['deleted_activity']) && $_GET['deleted_activity'] == '1') {
            if (isset($_SESSION['success_message'])) {
                $message = $_SESSION['success_message'];
                $messageType = 'success';
                unset($_SESSION['success_message']);
            }
        }

        if (isset($_GET['edited_lf']) && $_GET['edited_lf'] == '1') {
            if (isset($_SESSION['success_message'])) {
                $message = $_SESSION['success_message'];
                $messageType = 'success';
                unset($_SESSION['success_message']);
            }
        }

        if (isset($_GET['deleted_lf']) && $_GET['deleted_lf'] == '1') {
            if (isset($_SESSION['success_message'])) {
                $message = $_SESSION['success_message'];
                $messageType = 'success';
                unset($_SESSION['success_message']);
            }
        }
        ?>
        
        <script src="assets/js/profile.js"></script>

        <!-- Handle messages dari PHP -->
        <?php if (!empty($message)): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                if (typeof showCustomNotification === 'function') {
                    showCustomNotification('<?= addslashes($message) ?>', '<?= $messageType ?>');
                } else {
                    alert('<?= addslashes($message) ?>');
                }
            }, 500);
        });
        </script>
    <?php endif; ?>
</body>
</html>