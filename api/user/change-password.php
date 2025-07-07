<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../utils/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['error' => 'Method not allowed'], 405);
}

try {
    $auth = new Auth();
    $user = $auth->requireAuth();
    
    $input = getJsonInput();
    if (!$input) {
        $input = $_POST;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Validate required fields for password change
    $required = ['current_password', 'new_password', 'confirm_password'];
    $missing = validateRequired($input, $required);
    if (!empty($missing)) {
        sendJsonResponse(['error' => 'Missing required fields'], 400);
    }
    
    // Validate password confirmation
    if ($input['new_password'] !== $input['confirm_password']) {
        sendJsonResponse(['error' => 'Password confirmation does not match'], 400);
    }
    
    // Validate password strength
    if (strlen($input['new_password']) < 6) {
        sendJsonResponse(['error' => 'Password must be at least 6 characters long'], 400);
    }
    
    // Get current user password
    $currentUserQuery = "SELECT password FROM users WHERE id = ?";
    $currentUserStmt = $db->prepare($currentUserQuery);
    $currentUserStmt->execute([$user['id']]);
    $currentUserData = $currentUserStmt->fetch();
    
    // Verify current password
    if (!password_verify($input['current_password'], $currentUserData['password'])) {
        sendJsonResponse(['error' => 'Current password is incorrect'], 400);
    }
    
    // Hash new password
    $hashedPassword = password_hash($input['new_password'], PASSWORD_DEFAULT);
    
    // Update password
    $updateQuery = "UPDATE users SET password = ? WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);
    
    if ($updateStmt->execute([$hashedPassword, $user['id']])) {
        // Log activity
        $logQuery = "INSERT INTO activity_logs (user_id, action, table_name, record_id, description) VALUES (?, ?, ?, ?, ?)";
        $logStmt = $db->prepare($logQuery);
        $logStmt->execute([$user['id'], 'UPDATE', 'users', $user['id'], 'Password changed']);
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Password berhasil diubah'
        ]);
    } else {
        sendJsonResponse(['error' => 'Gagal mengubah password'], 500);
    }
    
} catch (Exception $e) {
    error_log("Change password error: " . $e->getMessage());
    sendJsonResponse(['error' => 'Internal server error'], 500);
}
?>