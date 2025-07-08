<?php
// Konfigurasi Header & CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Memuat file dependensi
require_once '../../config/database.php';

try {
    // Koneksi database
    $database = new Database();
    $db = $database->getConnection();

    // Ambil parameter pencarian dari URL
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $category = isset($_GET['category']) ? trim($_GET['category']) : '';

    // Bangun kondisi WHERE secara dinamis
    $whereConditions = ['a.is_active = 1', 'u.is_active = 1'];
    $params = [];

    if (!empty($search)) {
        $searchParam = '%' . $search . '%';
        $whereConditions[] = '(a.title LIKE ? OR a.description LIKE ? OR a.organizer LIKE ?)';
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
    }

    if (!empty($category) && is_numeric($category)) {
        $whereConditions[] = 'a.category_id = ?';
        $params[] = (int)$category;
    }

    $whereClause = implode(' AND ', $whereConditions);

    // Eksekusi query pencarian
    $query = "SELECT a.*, u.first_name, u.last_name, u.phone as user_phone, c.name as category_name,
              CONCAT(u.first_name, ' ', u.last_name) as user_name
              FROM activities a
              JOIN users u ON a.user_id = u.id
              JOIN categories c ON a.category_id = c.id
              WHERE $whereClause
              ORDER BY a.event_date ASC, a.event_time ASC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Kembalikan hasil dalam format JSON
    echo json_encode($activities);

} catch (Exception $e) {
    // Handle error
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>