<?php
require_once 'includes/auth.php';

$auth = new Auth();
$user = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang - E-Statmad</title>
    <link rel="icon" href="assets/images/logo.png" type="image/png">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/about.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include('assets/php/navbar.php'); ?>

    <section class="about-hero">
        <div class="container">
            <div class="hero-content">
                <h1>Tentang E-Statmad</h1>
                <p>Platform mading elektronik yang menghubungkan mahasiswa POLSTAT STIS dalam berbagai aktivitas kampus</p>
            </div>
        </div>
    </section>

    <section class="about-content animate-on-scroll">
        <div class="container">
            <div class="content-grid">
                <div class="content-text">
                    <h2>Apa itu E-Statmad?</h2>
                    <p>E-Statmad adalah platform mading elektronik yang dirancang khusus untuk mahasiswa Politeknik Statistika STIS. Platform ini menyediakan dua fitur utama yang sangat dibutuhkan dalam kehidupan kampus: sistem Lost & Found untuk barang hilang dan platform berbagi informasi kegiatan mahasiswa.</p>
                    
                    <p>Dengan desain yang modern dan user-friendly, E-Statmad memudahkan mahasiswa untuk saling terhubung, berbagi informasi, dan membantu satu sama lain dalam kehidupan sehari-hari di kampus.</p>
                </div>
                <div class="content-image">
                    <div class="image-placeholder">
                        <i class="fas fa-users"></i>
                        <span>Komunitas Mahasiswa</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="features-detail animate-on-scroll">
        <div class="container">
            <h2>Fitur Utama</h2>
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>Lost & Found</h3>
                    <p>Sistem pelaporan barang hilang dan ditemukan yang memudahkan mahasiswa untuk saling membantu. Fitur ini dilengkapi dengan:</p>
                    <ul>
                        <li>Upload foto barang</li>
                        <li>Deskripsi detail</li>
                        <li>Lokasi kejadian</li>
                        <li>Kontak WhatsApp</li>
                        <li>Status laporan (terbuka/selesai)</li>
                    </ul>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3>Kegiatan Mahasiswa</h3>
                    <p>Platform berbagi informasi kegiatan kampus yang memungkinkan mahasiswa untuk:</p>
                    <ul>
                        <li>Mengumumkan event dan kegiatan</li>
                        <li>Melihat jadwal kegiatan</li>
                        <li>Mendapatkan detail lengkap acara</li>
                        <li>Menghubungi penyelenggara</li>
                        <li>Filter berdasarkan kategori</li>
                    </ul>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h3>Profil Personal</h3>
                    <p>Setiap pengguna memiliki profil personal untuk mengelola aktivitas mereka:</p>
                    <ul>
                        <li>Riwayat laporan Lost & Found</li>
                        <li>Kegiatan yang dibuat</li>
                        <li>Edit dan hapus postingan</li>
                        <li>Update status laporan</li>
                        <li>Statistik aktivitas</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="mission animate-on-scroll">
        <div class="container">
            <h2>Misi Kami</h2>
            <p class="section-subtitle">E-Statmad hadir dengan komitmen untuk menciptakan ekosistem digital yang mendukung kehidupan kampus</p>
            
            <div class="mission-cards">
                <div class="mission-card vision-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <h3>Visi & Tujuan</h3>
                    </div>
                    <div class="card-content">
                        <div class="mission-point">
                            <div class="point-icon">
                                <i class="fas fa-handshake"></i>
                            </div>
                            <div class="point-text">
                                <h4>Membangun Komunitas</h4>
                                <p>Menciptakan platform yang memungkinkan mahasiswa saling membantu dan terhubung dalam kehidupan kampus.</p>
                            </div>
                        </div>
                        
                        <div class="mission-point">
                            <div class="point-icon">
                                <i class="fas fa-lightbulb"></i>
                            </div>
                            <div class="point-text">
                                <h4>Inovasi Digital</h4>
                                <p>Menghadirkan solusi digital modern untuk menggantikan mading konvensional dengan fitur interaktif.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mission-card impact-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <h3>Dampak & Manfaat</h3>
                    </div>
                    <div class="card-content">
                        <div class="mission-point">
                            <div class="point-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div class="point-text">
                                <h4>Mendukung Akademik</h4>
                                <p>Memfasilitasi penyebaran informasi kegiatan akademik dan non-akademik untuk pengembangan mahasiswa.</p>
                            </div>
                        </div>
                        
                        <div class="mission-point">
                            <div class="point-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="point-text">
                                <h4>Efisiensi Kampus</h4>
                                <p>Meningkatkan efisiensi komunikasi dan koordinasi antar mahasiswa dalam berbagai aktivitas kampus.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="contact animate-on-scroll">
        <div class="container">
            <h2>Hubungi Kami</h2>
            <p class="section-subtitle">Punya pertanyaan, saran, atau butuh bantuan? Tim E-Statmad siap membantu Anda</p>
            
            <div class="contact-cards">
                <div class="contact-card info-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-address-book"></i>
                        </div>
                        <h3>Informasi Kontak</h3>
                    </div>
                    <div class="card-content">
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-details">
                                <h4>Email</h4>
                                <p>info@estatmad.ac.id</p>
                                <span>Respon dalam 24 jam</span>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="contact-details">
                                <h4>Telepon</h4>
                                <p>(021) 123-4567</p>
                                <span>Senin - Jumat, 08:00 - 17:00</span>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-details">
                                <h4>Alamat</h4>
                                <p>Politeknik Statistika STIS</p>
                                <span>Jl. Otto Iskandardinata No.64C, Jakarta Timur</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="contact-card action-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h3>Butuh Bantuan?</h3>
                    </div>
                    <div class="card-content">
                        <div class="help-section">
                            <div class="help-icon">
                                <i class="fas fa-question-circle"></i>
                            </div>
                            <div class="help-text">
                                <h4>Bantuan Teknis</h4>
                                <p>Jika Anda mengalami masalah teknis atau membutuhkan panduan penggunaan platform, jangan ragu untuk menghubungi kami.</p>
                            </div>
                        </div>
                        
                        <div class="help-section">
                            <div class="help-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <div class="help-text">
                                <h4>Saran & Masukan</h4>
                                <p>Kami sangat menghargai saran dan masukan Anda untuk terus mengembangkan platform E-Statmad.</p>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <a href="mailto:info@estatmad.ac.id" class="btn-primary">
                                <i class="fas fa-envelope"></i>
                                Kirim Email
                            </a>
                            <a href="https://wa.me/6281234567890" target="_blank" class="btn-secondary">
                                <i class="fab fa-whatsapp"></i>
                                Chat WhatsApp
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include('assets/php/footer.php'); ?>

    <script src="assets/js/main.js"></script>
</body>
</html>