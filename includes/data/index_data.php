<?php
// Memuat file dependensi
require_once __DIR__ . '/../../config/database.php';

// Inisialisasi otentikasi & database
$auth = new Auth();
$user = $auth->getCurrentUser();

$database = new Database();
$db = $database->getConnection();

// Mengambil item hilang & ditemukan terbaru
$lostFoundQuery = "SELECT lf.*, u.first_name, u.last_name, c.name as category_name
                   FROM lost_found_items lf
                   JOIN users u ON lf.user_id = u.id
                   JOIN categories c ON lf.category_id = c.id
                   WHERE u.is_active = 1
                   ORDER BY lf.created_at DESC LIMIT 6";
$lostFoundStmt = $db->prepare($lostFoundQuery);
$lostFoundStmt->execute();
$lostFoundItems = $lostFoundStmt->fetchAll();

// Mengambil kegiatan terbaru
$activitiesQuery = "SELECT a.*, u.first_name, u.last_name, c.name as category_name
                    FROM activities a
                    JOIN users u ON a.user_id = u.id
                    JOIN categories c ON a.category_id = c.id
                    WHERE a.is_active = 1 AND u.is_active = 1 AND a.event_date >= CURDATE()
                    ORDER BY a.event_date ASC LIMIT 6";
$activitiesStmt = $db->prepare($activitiesQuery);
$activitiesStmt->execute();
$activities = $activitiesStmt->fetchAll();

// Fungsi untuk mendapatkan ikon berdasarkan kategori
function getItemIcon($categoryName, $type) {
    // Jika tipenya 'lost_found'
    if ($type === 'lost_found') {
        // Peta ikon untuk kategori 'lost_found'
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
    } else { // Jika tipenya 'activity'
        // Ikon default untuk 'activity'
        return 'calendar-alt';
    }
}
?>