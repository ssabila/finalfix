<?php
// Memuat file dependensi
require_once __DIR__ . '/../../config/database.php';

// Inisialisasi variabel dan objek
$auth = new Auth();
$user = $auth->getCurrentUser();
$message = '';
$messageType = '';

$database = new Database();
$db = $database->getConnection();

// Menangani pengiriman form (tambah item)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    // Cek jika pengguna sudah login
    if (!$auth->isLoggedIn()) {
        $message = 'Anda harus login terlebih dahulu';
        $messageType = 'error';
    } else {
        // Ambil data dari form
        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'type' => $_POST['type'] ?? '',
            'category_id' => $_POST['category_id'] ?? '',
            'location' => trim($_POST['location'] ?? ''),
            'date_occurred' => $_POST['date_occurred'] ?? ''
        ];
        
        // Validasi field wajib diisi
        if (empty($data['title']) || empty($data['description']) || empty($data['type']) || 
            empty($data['category_id']) || empty($data['location']) || empty($data['date_occurred'])) {
            $message = 'Semua field wajib diisi';
            $messageType = 'error';
        } else {
            // Menangani unggahan gambar
            $imagePath = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/lost-found/';
                
                // Buat direktori jika belum ada
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($fileExtension, $allowedExtensions)) {
                    // Validasi ukuran file (max 5MB)
                    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                        $message = 'Ukuran file terlalu besar! Maksimal 5MB.';
                        $messageType = 'error';
                    } else {
                        // Validasi adalah gambar yang valid
                        $imageInfo = getimagesize($_FILES['image']['tmp_name']);
                        if ($imageInfo === false) {
                            $message = 'File yang diupload bukan gambar yang valid.';
                            $messageType = 'error';
                        } else {
                            $fileName = uniqid() . '.' . $fileExtension;
                            $targetPath = $uploadDir . $fileName;
                            
                            // Pindahkan file yang diunggah
                            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                                $imagePath = $targetPath;
                            } else {
                                $message = 'Gagal mengupload file gambar.';
                                $messageType = 'error';
                            }
                        }
                    }
                } else {
                    $message = 'Format file tidak didukung. Hanya JPG, PNG, dan GIF yang diperbolehkan.';
                    $messageType = 'error';
                }
            }
            
            // Lanjutkan insert jika tidak ada error
            if (empty($message)) {
                try {
                    // Query untuk memasukkan item baru
                    $insertQuery = "INSERT INTO lost_found_items (user_id, category_id, title, description, type, location, date_occurred, contact_info, image) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($insertQuery);
                    
                    if ($stmt->execute([$user['id'], $data['category_id'], $data['title'], $data['description'], 
                                        $data['type'], $data['location'], $data['date_occurred'], $user['phone'], $imagePath])) {
                        $message = 'Laporan berhasil ditambahkan!';
                        $messageType = 'success';
                        
                        // Reset form data
                        $_POST = [];
                    } else {
                        $message = 'Gagal menambahkan laporan ke database.';
                        $messageType = 'error';
                        
                        // Hapus file jika insert gagal
                        if ($imagePath && file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                    }
                } catch (Exception $e) {
                    $message = 'Terjadi kesalahan: ' . $e->getMessage();
                    $messageType = 'error';
                    
                    // Hapus file jika terjadi error
                    if ($imagePath && file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }
            }
        }
    }
}

// Mengambil daftar kategori untuk filter
try {
    $categoriesQuery = "SELECT * FROM categories WHERE type = 'lost_found' ORDER BY name ASC";
    $categoriesStmt = $db->prepare($categoriesQuery);
    $categoriesStmt->execute();
    $categories = $categoriesStmt->fetchAll();
} catch (Exception $e) {
    $categories = [];
    error_log("Error fetching categories: " . $e->getMessage());
}

// Mengambil daftar item dengan filter
// Membangun klausa WHERE secara dinamis
$whereConditions = ['u.is_active = 1'];
$params = [];

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = '%' . trim($_GET['search']) . '%';
    $whereConditions[] = '(lf.title LIKE ? OR lf.description LIKE ? OR lf.location LIKE ?)';
    $params = array_merge($params, [$search, $search, $search]);
}

if (isset($_GET['category']) && !empty($_GET['category']) && is_numeric($_GET['category'])) {
    $whereConditions[] = 'lf.category_id = ?';
    $params[] = (int)$_GET['category'];
}

if (isset($_GET['type']) && !empty($_GET['type']) && in_array($_GET['type'], ['kehilangan', 'penemuan'])) {
    $whereConditions[] = 'lf.type = ?';
    $params[] = $_GET['type'];
}

$whereClause = implode(' AND ', $whereConditions);

// Query utama untuk mengambil data item
try {
    $itemsQuery = "SELECT lf.*, u.first_name, u.last_name, u.phone as user_phone, c.name as category_name,
                      CONCAT(u.first_name, ' ', u.last_name) as user_name
                      FROM lost_found_items lf
                      JOIN users u ON lf.user_id = u.id
                      JOIN categories c ON lf.category_id = c.id
                      WHERE $whereClause
                      ORDER BY lf.created_at DESC";

    $itemsStmt = $db->prepare($itemsQuery);
    $itemsStmt->execute($params);
    $items = $itemsStmt->fetchAll();
} catch (Exception $e) {
    $items = [];
    error_log("Error fetching items: " . $e->getMessage());
}

?>