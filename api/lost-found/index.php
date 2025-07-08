<?php
// Konfigurasi Header & CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle pre-flight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Memuat file dependensi
require_once '../config/database.php';
require_once '../utils/auth.php';

try {
    // Koneksi ke database
    $database = new Database();
    $db = $database->getConnection();
    
    // Handle GET (ambil daftar barang hilang/ditemukan)
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Pengaturan paginasi
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        // Query untuk mengambil daftar barang
        $query = "SELECT lf.*, u.first_name, u.last_name, u.phone as user_phone, c.name as category_name,
                  CONCAT(u.first_name, ' ', u.last_name) as user_name
                  FROM lost_found_items lf
                  JOIN users u ON lf.user_id = u.id
                  JOIN categories c ON lf.category_id = c.id
                  WHERE u.is_active = 1
                  ORDER BY lf.created_at DESC
                  LIMIT ? OFFSET ?";
        
        // Eksekusi query
        $stmt = $db->prepare($query);
        $stmt->execute([$limit, $offset]);
        $items = $stmt->fetchAll();
        
        sendJsonResponse($items);
        
    // Handle POST (buat item baru)
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Otentikasi pengguna
        $auth = new Auth();
        $user = $auth->requireAuth();
        
        // Ambil dan validasi input
        $input = getJsonInput();
        $required = ['title', 'description', 'type', 'category_id', 'location', 'date_occurred'];
        $missing = validateRequired($input, $required);
        if (!empty($missing)) {
            sendJsonResponse(['error' => 'Missing required fields: ' . implode(', ', $missing)], 400);
        }
        
        // Validasi tipe (harus "hilang" atau "ditemukan")
        if (!in_array($input['type'], ['hilang', 'ditemukan'])) {
            sendJsonResponse(['error' => 'Invalid type. Must be "hilang" or "ditemukan"'], 400);
        }
        
        // Query untuk memasukkan item baru
        $insertQuery = "INSERT INTO lost_found_items (user_id, category_id, title, description, type, location, date_occurred, contact_info, image) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        // Eksekusi query insert
        $stmt = $db->prepare($insertQuery);
        $stmt->execute([
            $user['id'],
            $input['category_id'],
            $input['title'],
            $input['description'],
            $input['type'],
            $input['location'],
            $input['date_occurred'],
            $user['phone'],
            $input['image'] ?? null
        ]);
        
        $itemId = $db->lastInsertId();
        
        // Pencatatan log aktivitas
        $logQuery = "INSERT INTO activity_logs (user_id, action, table_name, record_id, description) VALUES (?, ?, ?, ?, ?)";
        $logStmt = $db->prepare($logQuery);
        $logStmt->execute([$user['id'], 'CREATE', 'lost_found_items', $itemId, 'Created lost & found item: ' . $input['title']]);
        
        // Kirim respons sukses
        sendJsonResponse([
            'success' => true,
            'message' => 'Item created successfully',
            'id' => $itemId
        ]);
        
    } else {
        // Handle metode lain
        sendJsonResponse(['error' => 'Method not allowed'], 405);
    }
    
} catch (Exception $e) {
    // Handle error
    error_log("Lost & Found error: " . $e->getMessage());
    sendJsonResponse(['error' => 'Internal server error'], 500);
}
?>