<?php
// debug_admin.php - Buat file ini untuk debug masalah admin
require_once 'includes/auth.php';

$auth = new Auth();
$user = $auth->getCurrentUser();

echo "<h2>🔍 Debug Admin Access</h2>";
echo "<hr>";

echo "<h3>1. Login Status:</h3>";
if ($user) {
    echo "✅ User is logged in<br>";
    echo "<strong>User ID:</strong> " . $user['id'] . "<br>";
    echo "<strong>Name:</strong> " . $user['first_name'] . " " . $user['last_name'] . "<br>";
    echo "<strong>Email:</strong> " . $user['email'] . "<br>";
    echo "<strong>NIM:</strong> " . $user['nim'] . "<br>";
} else {
    echo "❌ User is NOT logged in<br>";
    echo "<a href='login.php'>Login First</a>";
    exit;
}

echo "<h3>2. Role Check:</h3>";
if (isset($user['role'])) {
    echo "✅ Role column exists<br>";
    echo "<strong>Current Role:</strong> '" . $user['role'] . "'<br>";
    
    if ($user['role'] === 'admin') {
        echo "✅ User has admin role<br>";
    } else {
        echo "❌ User role is NOT admin (current: '" . $user['role'] . "')<br>";
        echo "<strong>🔧 FIX NEEDED:</strong> Update role to 'admin'<br>";
    }
} else {
    echo "❌ Role column does NOT exist in user data<br>";
    echo "<strong>🔧 FIX NEEDED:</strong> Add role column to database<br>";
}

echo "<h3>3. Database Direct Check:</h3>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, nim, first_name, last_name, email, role, is_active FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user['id']]);
    $dbUser = $stmt->fetch();
    
    if ($dbUser) {
        echo "✅ User found in database<br>";
        echo "<strong>Database Role:</strong> '" . ($dbUser['role'] ?? 'NULL') . "'<br>";
        echo "<strong>Is Active:</strong> " . ($dbUser['is_active'] ? 'Yes' : 'No') . "<br>";
        
        if (!isset($dbUser['role'])) {
            echo "❌ Role column missing in database<br>";
            echo "<button onclick=\"addRoleColumn()\">🔧 Add Role Column</button><br>";
        } elseif ($dbUser['role'] !== 'admin') {
            echo "❌ Role is not admin<br>";
            echo "<button onclick=\"makeAdmin()\">🔧 Make This User Admin</button><br>";
        }
    } else {
        echo "❌ User not found in database<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<h3>4. Admin Access Test:</h3>";
$isAdmin = $user && isset($user['role']) && $user['role'] === 'admin';
if ($isAdmin) {
    echo "✅ Admin access should work<br>";
    echo "<a href='admin.php' style='background: green; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>🚀 Go to Admin Dashboard</a><br>";
} else {
    echo "❌ Admin access will be denied<br>";
    echo "Need to fix role first<br>";
}

echo "<hr>";
echo "<h3>5. Quick Fixes:</h3>";
?>

<script>
function addRoleColumn() {
    if (confirm('Add role column to users table?')) {
        fetch('fix_admin.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=add_role_column'
        }).then(() => location.reload());
    }
}

function makeAdmin() {
    if (confirm('Make current user admin?')) {
        fetch('fix_admin.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=make_admin&user_id=<?= $user['id'] ?>'
        }).then(() => location.reload());
    }
}
</script>

<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
h2, h3 { color: #333; }
button { background: #007bff; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; margin: 5px 0; }
button:hover { background: #0056b3; }
</style>