<?php
// file yang dibutuhkan
require_once __DIR__ . '/../../config/database.php';

$message = '';
$messageType = '';
$categories = [];
$activities = [];

$auth = new Auth();
$user = $auth->getCurrentUser();
$message = '';
$messageType = '';

$database = new Database();
$db = $database->getConnection();

// Handle form submission 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_activity'])) {
    if (!$auth->isLoggedIn()) {
        $message = 'Anda harus login terlebih dahulu';
        $messageType = 'error';
    } else {
        $data = [
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'category_id' => $_POST['category_id'] ?? '',
            'event_date' => $_POST['event_date'] ?? '',
            'event_time' => $_POST['event_time'] ?? '',
            'location' => $_POST['location'] ?? '',
            'organizer' => $_POST['organizer'] ?? ''
        ];
        
        if (empty($data['title']) || empty($data['description']) || empty($data['category_id']) || 
            empty($data['event_date']) || empty($data['event_time']) || empty($data['location']) || empty($data['organizer'])) {
            $message = 'Semua field wajib diisi';
            $messageType = 'error';
        } else {
            // Handle image upload
            $imagePath = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/activities/';
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
                    $insertQuery = "INSERT INTO activities (user_id, category_id, title, description, event_date, event_time, location, organizer, contact_info, image) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($insertQuery);
                    
                    if ($stmt->execute([$user['id'], $data['category_id'], $data['title'], $data['description'], 
                                       $data['event_date'], $data['event_time'], $data['location'], $data['organizer'], $user['phone'], $imagePath])) {
                        $message = 'Kegiatan berhasil ditambahkan!';
                        $messageType = 'success';
                        
                        // Reset form data
                        $_POST = [];
                    } else {
                        $message = 'Gagal menambahkan kegiatan ke database.';
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

// Get categories
try {
    $categoriesQuery = "SELECT * FROM categories WHERE type = 'activity' ORDER BY name ASC";
    $categoriesStmt = $db->prepare($categoriesQuery);
    $categoriesStmt->execute();
    $categories = $categoriesStmt->fetchAll();
} catch (Exception $e) {
    $categories = [];
    error_log("Error fetching categories: " . $e->getMessage());
}

// Get activities with filters
$whereConditions = ['a.is_active = 1', 'u.is_active = 1'];
$params = [];

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = '%' . trim($_GET['search']) . '%';
    $whereConditions[] = '(a.title LIKE ? OR a.description LIKE ? OR a.organizer LIKE ?)';
    $params = array_merge($params, [$search, $search, $search]);
}

if (isset($_GET['category']) && !empty($_GET['category']) && is_numeric($_GET['category'])) {
    $whereConditions[] = 'a.category_id = ?';
    $params[] = (int)$_GET['category'];
}

$whereClause = implode(' AND ', $whereConditions);

try {
    $activitiesQuery = "SELECT a.*, u.first_name, u.last_name, u.phone as user_phone, c.name as category_name,
                        CONCAT(u.first_name, ' ', u.last_name) as user_name
                        FROM activities a
                        JOIN users u ON a.user_id = u.id
                        JOIN categories c ON a.category_id = c.id
                        WHERE $whereClause
                        ORDER BY a.event_date ASC, a.event_time ASC";

    $activitiesStmt = $db->prepare($activitiesQuery);
    $activitiesStmt->execute($params);
    $activities = $activitiesStmt->fetchAll();
} catch (Exception $e) {
    $activities = [];
    error_log("Error fetching activities: " . $e->getMessage());
}