<?php
$auth = new Auth();
$user = $auth->getCurrentUser();
$isAdmin = $user && isset($user['role']) && $user['role'] === 'admin';
$currentPage = basename($_SERVER['PHP_SELF']); // Get current page filename
?>

<nav class="navbar">
    <div class="nav-container">
        <div class="nav-logo">
            <img src="assets/images/logo.png" alt="E-Statmad Logo">
            <?php if ($isAdmin): ?>
                <span class="admin-badge">ADMIN</span>
            <?php endif; ?>
        </div>
        
        <div class="nav-menu" id="nav-menu">
            <a href="index.php" class="nav-link <?= ($currentPage == 'index.php') ? 'active' : '' ?>">Beranda</a>
            <a href="lost-found.php" class="nav-link <?= ($currentPage == 'lost-found.php') ? 'active' : '' ?>">Lost & Found</a>
            <a href="activities.php" class="nav-link <?= ($currentPage == 'activities.php') ? 'active' : '' ?>">Kegiatan</a>
            <a href="about.php" class="nav-link <?= ($currentPage == 'about.php') ? 'active' : '' ?>">Tentang</a>
            
            <?php if ($isAdmin): ?>
                <a href="admin.php" class="nav-link admin-dashboard-link <?= ($currentPage == 'admin.php') ? 'active' : '' ?>">
                    <i class="fas fa-shield-alt"></i> Admin Dashboard
                </a>
            <?php endif; ?>
            
            <!-- Auth buttons untuk mobile menu -->
            <div class="nav-auth">
                <?php if ($user): ?>
                    <a href="profile.php" class="btn-login <?= ($currentPage == 'profile.php') ? 'active' : '' ?>">
                        <?php if (!empty($user['avatar'])): ?>
                            <?php 
                            // PERBAIKAN: Gunakan helper function untuk mendapatkan URL avatar yang benar
                            $avatarUrl = $auth->getAvatarUrl($user);
                            
                            // Pastikan file avatar benar-benar ada
                            if (file_exists($avatarUrl)): ?>
                                <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar" class="nav-avatar">
                            <?php else: ?>
                                <!-- Fallback jika file tidak ada -->
                                <?php if ($isAdmin): ?>
                                    <i class="fas fa-user-shield"></i>
                                <?php else: ?>
                                    <i class="fas fa-user"></i>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php elseif ($isAdmin): ?>
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
        
        <!-- Auth buttons untuk desktop -->
        <div class="nav-auth">
            <?php if ($user): ?>
                <a href="profile.php" class="btn-login <?= ($currentPage == 'profile.php') ? 'active' : '' ?>">
                    <?php if (!empty($user['avatar'])): ?>
                        <?php 
                        // PERBAIKAN: Gunakan helper function untuk mendapatkan URL avatar yang benar
                        $avatarUrl = $auth->getAvatarUrl($user);
                        
                        // Pastikan file avatar benar-benar ada
                        if (file_exists($avatarUrl)): ?>
                            <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar" class="nav-avatar">
                        <?php else: ?>
                            <!-- Fallback jika file tidak ada -->
                            <?php if ($isAdmin): ?>
                                <i class="fas fa-user-shield"></i>
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php elseif ($isAdmin): ?>
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
        
        <div class="hamburger" id="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
</nav>