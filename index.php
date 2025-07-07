<?php
require_once 'includes/auth.php';
require_once 'includes/data/index_data.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Statmad - Electronic Bulletin Board</title>
    <link rel="icon" href="assets/images/logo.png" type="image/png">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/home.css">
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
    
    <!-- Navigation -->
    <?php include('assets/php/navbar.php'); ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <div>
                <h1>Selamat Datang di E-Statmad</h1>
                <p>Platform mading elektronik untuk mahasiswa POLSTAT STIS. Temukan barang hilang dan ikuti kegiatan kampus dengan mudah.</p>
                <div class="hero-buttons">
                    <a href="lost-found.php" class="btn-primary">
                        <i class="fas fa-search"></i>
                        Lost & Found
                    </a>
                    <a href="activities.php" class="btn-secondary">
                        <i class="fas fa-calendar"></i>
                        Kegiatan Mahasiswa
                    </a>
                </div>
            </div>
            <div class="hero-image">
                <div class="floating-cards">
                    <div class="card-float card-1">
                        <i class="fas fa-search"></i>
                        <span>Cari Barang</span>
                    </div>
                    <div class="card-float card-2">
                        <i class="fas fa-calendar"></i>
                        <span>Event Kampus</span>
                    </div>
                    <div class="card-float card-3">
                        <i class="fas fa-users"></i>
                        <span>Komunitas</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <h2>Fitur Utama</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>Lost & Found</h3>
                    <p>Laporkan barang hilang atau temukan barang yang hilang dengan mudah. Sistem notifikasi otomatis untuk menghubungkan pemilik dan penemu.</p>
                    <a href="lost-found.php" class="feature-link">Jelajahi <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3>Kegiatan Mahasiswa</h3>
                    <p>Ikuti berbagai kegiatan kampus, seminar, workshop, dan acara menarik lainnya. Jangan sampai terlewat!</p>
                    <a href="activities.php" class="feature-link">Lihat Kegiatan <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h3>Profil Personal</h3>
                    <p>Kelola laporan dan unggahan Anda. Pantau status laporan lost & found dan riwayat kegiatan yang diikuti.</p>
                    <a href="<?= $user ? 'profile.php' : 'login.php' ?>" class="feature-link"><?= $user ? 'Profil' : 'Masuk' ?> <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </section>

    <!-- Recent Posts -->
    <section class="recent-posts">
        <div class="container">
            <h2>Postingan Terbaru</h2>
            <div id="recent-posts">
                <?php if (count($lostFoundItems) > 0 || count($activities) > 0): ?>
                    <div class="slider-container">
                        <div class="slider-wrapper">
                            <div class="posts-slider" id="posts-slider">
                                <?php 
                                // Combine all posts
                                $allPosts = [];
                                
                                // Add lost & found items
                                foreach ($lostFoundItems as $item) {
                                    $allPosts[] = [
                                        'type' => 'lost-found',
                                        'data' => $item,
                                        'category_display' => $item['type'] === 'hilang' ? 'Barang Hilang' : 'Barang Ditemukan',
                                        'category_class' => $item['type'] === 'hilang' ? 'lost' : 'found',
                                        'date' => $item['date_occurred'],
                                        'link' => 'lost-found.php'
                                    ];
                                }
                                
                                // Add activities
                                foreach ($activities as $activity) {
                                    $allPosts[] = [
                                        'type' => 'activity',
                                        'data' => $activity,
                                        'category_display' => $activity['category_name'],
                                        'category_class' => 'activity',
                                        'date' => $activity['event_date'],
                                        'link' => 'activities.php'
                                    ];
                                }
                                
                                // Sort by date (newest first)
                                usort($allPosts, function($a, $b) {
                                    return strtotime($b['date']) - strtotime($a['date']);
                                });
                                
                                // Display posts
                                foreach ($allPosts as $post): 
                                    $item = $post['data'];
                                    $hasImage = !empty($item['image']) && file_exists($item['image']);
                                    $icon = getItemIcon($item['category_name'], $post['type']);
                                ?>
                                    <div class="post-card" onclick="window.location.href='<?= $post['link'] ?>'">
                                        <div class="post-image <?= $hasImage ? 'has-image' : '' ?>">
                                            <?php if ($hasImage): ?>
                                                <img src="<?= htmlspecialchars($item['image']) ?>" 
                                                     alt="<?= htmlspecialchars($item['title']) ?>" 
                                                     loading="lazy"
                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                <div class="fallback-icon" style="display: none;">
                                                    <i class="fas fa-<?= $icon ?>"></i>
                                                </div>
                                            <?php else: ?>
                                                <i class="fas fa-<?= $icon ?>"></i>
                                            <?php endif; ?>
                                            <div class="post-type-badge <?= $post['type'] ?>">
                                                <?= $post['type'] === 'lost-found' ? 'Lost & Found' : 'Kegiatan' ?>
                                            </div>
                                        </div>
                                        <div class="post-content">
                                            <div class="post-category <?= $post['category_class'] ?>">
                                                <?= htmlspecialchars($post['category_display']) ?>
                                            </div>
                                            <h3 class="post-title"><?= htmlspecialchars($item['title']) ?></h3>
                                            <div class="post-meta">
                                                <div class="meta-item">
                                                    <i class="fas fa-calendar"></i>
                                                    <span><?= date('d M Y', strtotime($post['date'])) ?></span>
                                                </div>
                                                <div class="meta-item">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <span><?= htmlspecialchars($item['location']) ?></span>
                                                </div>
                                                <div class="meta-item">
                                                    <i class="fas fa-user"></i>
                                                    <span><?= htmlspecialchars($item['first_name'] . ' ' . $item['last_name']) ?></span>
                                                </div>
                                            </div>
                                            <p class="post-description"><?= htmlspecialchars(substr($item['description'], 0, 100)) ?>...</p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <button class="slider-btn prev" id="prev-btn">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="slider-btn next" id="next-btn">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <div class="slider-dots" id="slider-dots"></div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Belum Ada Postingan</h3>
                        <p>Belum ada postingan terbaru untuk ditampilkan</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="view-more">
                <a href="lost-found.php" class="btn-outline">Lihat Semua Lost & Found</a>
                <a href="activities.php" class="btn-outline">Lihat Semua Kegiatan</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include('assets/php/footer.php'); ?>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/home.js"></script>
    <script>
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                    <span>${message}</span>
                    <button class="notification-close"><i class="fas fa-times"></i></button>
                </div>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => notification.remove(), 5000);
            notification.querySelector('.notification-close').onclick = () => notification.remove();
        }
    </script>
</body>
</html>
