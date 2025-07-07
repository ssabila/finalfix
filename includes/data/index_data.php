<?php
require_once __DIR__ . '/../../config/database.php';

$auth = new Auth();
$user = $auth->getCurrentUser();

// Get recent posts
$database = new Database();
$db = $database->getConnection();

// Get recent lost & found items with images
$lostFoundQuery = "SELECT lf.*, u.first_name, u.last_name, c.name as category_name
                    FROM lost_found_items lf
                    JOIN users u ON lf.user_id = u.id
                    JOIN categories c ON lf.category_id = c.id
                    WHERE u.is_active = 1
                    ORDER BY lf.created_at DESC LIMIT 6";
$lostFoundStmt = $db->prepare($lostFoundQuery);
$lostFoundStmt->execute();
$lostFoundItems = $lostFoundStmt->fetchAll();

// Get recent activities with images
$activitiesQuery = "SELECT a.*, u.first_name, u.last_name, c.name as category_name
                    FROM activities a
                    JOIN users u ON a.user_id = u.id
                    JOIN categories c ON a.category_id = c.id
                    WHERE a.is_active = 1 AND u.is_active = 1 AND a.event_date >= CURDATE()
                    ORDER BY a.event_date ASC LIMIT 6";
$activitiesStmt = $db->prepare($activitiesQuery);
$activitiesStmt->execute();
$activities = $activitiesStmt->fetchAll();

// Function to get appropriate icon based on category
function getItemIcon($categoryName, $type) {
    if ($type === 'lost_found') {
        $iconMap = [
            'elektronik' => 'laptop',
            'aksesoris' => 'glasses',
            'pakaian' => 'tshirt',
            'buku' => 'book',
            'alat tulis' => 'pen',
            'tas' => 'briefcase',
            'sepatu' => 'shoe-prints',
            'perhiasan' => 'gem',
            'kendaraan' => 'car',
            'lainnya' => 'box'
        ];
        $normalizedCategory = strtolower($categoryName);
        return $iconMap[$normalizedCategory] ?? 'search';
    } else {
        return 'calendar-alt';
    }
}
?>