<?php
$auth = new Auth();
$user = $auth->getCurrentUser();
$isAdmin = $user && isset($user['role']) && $user['role'] === 'admin';
?>

<nav class="navbar">
    <div class="nav-container">
        <div class="nav-logo">
            <img src="assets/images/logo.png" alt="E-Statmad Logo">
            <?php if ($isAdmin): ?>
                <span style="background: #dc3545; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.7rem; margin-left: 10px; font-weight: bold;">ADMIN</span>
            <?php endif; ?>
        </div>
        <div class="nav-menu" id="nav-menu">
            <a href="index.php" class="nav-link">Beranda</a>
            <a href="lost-found.php" class="nav-link">Lost & Found</a>
            <a href="activities.php" class="nav-link">Kegiatan</a>
            <a href="about.php" class="nav-link">Tentang</a>
            
            <!-- Menu Admin - Hanya tampil untuk admin -->
            <?php if ($isAdmin): ?>
                <a href="admin.php" class="nav-link" style="background: linear-gradient(135deg, #dc3545, #c82333); color: white; padding: 8px 15px; border-radius: 20px; margin-left: 10px; font-weight: 600; box-shadow: 0 2px 4px rgba(220,53,69,0.3);">
                    <i class="fas fa-shield-alt"></i> Admin Dashboard
                </a>
            <?php endif; ?>
            
            <div class="nav-auth">
                <?php if ($user): ?>
                    <a href="profile.php" class="btn-login">
                        <?php if ($isAdmin): ?>
                            <i class="fas fa-user-shield"></i>
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars($user['first_name']) ?>
                    </a>
                    <a href="logout.php" class="btn-register">
                        <i class="fas fa-sign-out-alt"></i>
                        Keluar
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn-login">Masuk</a>
                    <a href="register.php" class="btn-register">Daftar</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="hamburger" id="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
</nav>