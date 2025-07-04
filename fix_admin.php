<?php
// fix_admin.php - Script untuk fix masalah admin otomatis
require_once 'includes/auth.php';
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if ($action === 'add_role_column') {
            // Add role column to users table
            $query = "ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user' AFTER is_active";
            $db->exec($query);
            echo "✅ Role column added successfully!";
            
        } elseif ($action === 'make_admin') {
            $userId = $_POST['user_id'] ?? 0;
            
            // Update user role to admin
            $query = "UPDATE users SET role = 'admin' WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$userId]);
            
            echo "✅ User role updated to admin!";
            
        } elseif ($action === 'create_admin_logs') {
            // Create admin_logs table
            $query = "CREATE TABLE IF NOT EXISTS admin_logs (
                id INT PRIMARY KEY AUTO_INCREMENT,
                admin_user_id INT NOT NULL,
                action VARCHAR(100) NOT NULL,
                target_id INT NOT NULL,
                target_title VARCHAR(200),
                reason TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (admin_user_id) REFERENCES users(id) ON DELETE CASCADE
            )";
            $db->exec($query);
            echo "✅ Admin logs table created!";
            
        } elseif ($action === 'fix_all') {
            $userId = $_POST['user_id'] ?? 0;
            
            // 1. Add role column if not exists
            try {
                $db->exec("ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user' AFTER is_active");
            } catch (Exception $e) {
                // Column might already exist
            }
            
            // 2. Make user admin
            $query = "UPDATE users SET role = 'admin' WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$userId]);
            
            // 3. Create admin_logs table
            $query = "CREATE TABLE IF NOT EXISTS admin_logs (
                id INT PRIMARY KEY AUTO_INCREMENT,
                admin_user_id INT NOT NULL,
                action VARCHAR(100) NOT NULL,
                target_id INT NOT NULL,
                target_title VARCHAR(200),
                reason TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (admin_user_id) REFERENCES users(id) ON DELETE CASCADE
            )";
            $db->exec($query);
            
            echo "✅ All fixes applied successfully!";
        }
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage();
    }
    exit;
}

// If not POST request, show manual fix options
$auth = new Auth();
$user = $auth->getCurrentUser();

if (!$user) {
    echo "Please login first. <a href='login.php'>Login</a>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fix Admin Access</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .fix-button { background: #28a745; color: white; border: none; padding: 15px 20px; border-radius: 5px; cursor: pointer; margin: 10px 0; display: block; width: 100%; font-size: 16px; }
        .fix-button:hover { background: #218838; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <h2>🔧 Fix Admin Access</h2>
    
    <div class="warning">
        <strong>⚠️ Warning:</strong> This will modify your database. Make sure you have a backup!
    </div>
    
    <p><strong>Current User:</strong> <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
    
    <button class="fix-button" onclick="fixAll()">
        🚀 Fix All Admin Issues (Recommended)
    </button>
    
    <hr>
    
    <h3>Individual Fixes:</h3>
    
    <button class="fix-button" onclick="addRoleColumn()">
        1. Add Role Column to Database
    </button>
    
    <button class="fix-button" onclick="makeAdmin()">
        2. Make Current User Admin
    </button>
    
    <button class="fix-button" onclick="createAdminLogs()">
        3. Create Admin Logs Table
    </button>
    
    <hr>
    
    <p><a href="debug_admin.php">🔍 Back to Debug</a> | <a href="admin.php">🎯 Try Admin Dashboard</a></p>

    <script>
    function fixAll() {
        if (confirm('Fix all admin issues? This will:\n1. Add role column\n2. Make you admin\n3. Create admin logs table')) {
            fetch('fix_admin.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=fix_all&user_id=<?= $user['id'] ?>'
            }).then(response => response.text()).then(data => {
                alert(data);
                if (data.includes('✅')) {
                    window.location.href = 'admin.php';
                }
            });
        }
    }
    
    function addRoleColumn() {
        if (confirm('Add role column to users table?')) {
            fetch('fix_admin.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=add_role_column'
            }).then(response => response.text()).then(data => alert(data));
        }
    }
    
    function makeAdmin() {
        if (confirm('Make current user admin?')) {
            fetch('fix_admin.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=make_admin&user_id=<?= $user['id'] ?>'
            }).then(response => response.text()).then(data => alert(data));
        }
    }
    
    function createAdminLogs() {
        if (confirm('Create admin_logs table?')) {
            fetch('fix_admin.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=create_admin_logs'
            }).then(response => response.text()).then(data => alert(data));
        }
    }
    </script>
</body>
</html>