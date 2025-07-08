<?php
// Konfigurasi Header & CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle pre-flight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Memuat file dependensi
require_once '../config/database.php';
require_once '../utils/auth.php';

// Pastikan metode adalah GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(['error' => 'Method not allowed'], 405);
}

try {
    // Koneksi ke database
    $database = new Database();
    $db = $database->getConnection();
    
    // Ambil dan validasi parameter 'type' dari URL
    $type = isset($_GET['type']) ? $_GET['type'] : '';
    if (!empty($type) && !in_array($type, ['lost_found', 'activity'])) {
        sendJsonResponse(['error' => 'Invalid category type'], 400);
    }
    
    // Bangun query secara dinamis
    $query = "SELECT id, name, type FROM categories";
    $params = [];
    
    // Tambahkan filter WHERE jika parameter 'type' ada
    if (!empty($type)) {
        $query .= " WHERE type = ?";
        $params[] = $type;
    }
    
    $query .= " ORDER BY name ASC";
    
    // Eksekusi query
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $categories = $stmt->fetchAll();
    
    // Kirim respons sukses dengan daftar kategori
    sendJsonResponse([
        'success' => true,
        'categories' => $categories
    ]);
    
} catch (Exception $e) {
    // Handle error
    error_log("Categories error: " . $e->getMessage());
    sendJsonResponse(['error' => 'Internal server error'], 500);
}
?>