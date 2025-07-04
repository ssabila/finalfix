<?php
// create_admin.php - Script untuk membuat admin user
// Upload file ini ke root directory dan akses via browser

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Cek apakah admin sudah ada
    $checkQuery = "SELECT * FROM users WHERE email = 'admin@estatmad.ac.id'";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute();
    $existingAdmin = $checkStmt->fetch();
    
    if ($existingAdmin) {
        echo "✅ Admin user sudah ada!<br>";
        echo "Email: " . $existingAdmin['email'] . "<br>";
        echo "Role: " . $existingAdmin['role'] . "<br>";
        
        // Update role jika bukan admin
        if ($existingAdmin['role'] !== 'admin') {
            $updateQuery = "UPDATE users SET role = 'admin' WHERE email = 'admin@estatmad.ac.id'";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute();
            echo "✅ Role berhasil diupdate ke admin!<br>";
        }
        
        // Reset password
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $resetQuery = "UPDATE users SET password = ? WHERE email = 'admin@estatmad.ac.id'";
        $resetStmt = $db->prepare($resetQuery);
        $resetStmt->execute([$passwordHash]);
        echo "✅ Password berhasil direset ke 'admin123'<br>";
        
    } else {
        // Buat admin baru
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        
        $insertQuery = "INSERT INTO users (nim, first_name, last_name, email, phone, password, role, is_active) 
                        VALUES (?, ?, ?, ?, ?, ?, 'admin', 1)";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->execute([
            'ADMIN001',
            'Admin',
            'E-Statmad',
            'admin@estatmad.ac.id',
            '08123456789',
            $passwordHash
        ]);
        
        echo "✅ Admin user berhasil dibuat!<br>";
    }
    
    echo "<br><strong>LOGIN CREDENTIALS:</strong><br>";
    echo "Email: admin@estatmad.ac.id<br>";
    echo "Password: admin123<br>";
    echo "<br><a href='login.php'>Login Sekarang</a>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>