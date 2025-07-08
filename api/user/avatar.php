<?php
// Konfigurasi Header & CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle pre-flight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Memuat file dependensi
require_once '../config/database.php';
require_once '../utils/auth.php'; // Seharusnya '../utils/auth.php'

try {
    // Otentikasi pengguna
    $auth = new Auth();
    $user = $auth->requireAuth(); // Menggunakan requireAuth untuk memastikan pengguna login
    
    // Koneksi ke database
    $database = new Database();
    $db = $database->getConnection();
    
    // Handle POST (unggah avatar)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validasi file unggahan
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            sendJsonResponse(['error' => 'No file uploaded or upload error'], 400);
        }
        
        $file = $_FILES['avatar'];
        
        // Validasi tipe file
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];
        $mimeType = mime_content_type($file['tmp_name']);
        if (!in_array($mimeType, $allowedMimes)) {
            sendJsonResponse(['error' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.'], 400);
        }
        
        // Validasi ukuran file (maks 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            sendJsonResponse(['error' => 'File size too large. Maximum 5MB allowed.'], 400);
        }
        
        // Buat direktori unggahan jika belum ada
        $uploadDir = '../../uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Buat nama file yang unik
        $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileName = $user['id'] . '_' . time() . '.' . $fileType;
        $targetPath = $uploadDir . $fileName;
        $avatarPathForDb = 'uploads/avatars/' . $fileName;
        
        // Ambil path avatar saat ini untuk dihapus nanti
        $currentAvatarQuery = "SELECT avatar FROM users WHERE id = ?";
        $currentAvatarStmt = $db->prepare($currentAvatarQuery);
        $currentAvatarStmt->execute([$user['id']]);
        $currentAvatar = $currentAvatarStmt->fetchColumn();
        
        // Ubah ukuran dan simpan gambar
        if (resizeAndSaveImage($file['tmp_name'], $targetPath, $mimeType)) {
            // Update path avatar di database
            $updateQuery = "UPDATE users SET avatar = ? WHERE id = ?";
            $updateStmt = $db->prepare($updateQuery);
            
            if ($updateStmt->execute([$avatarPathForDb, $user['id']])) {
                // Hapus file avatar lama jika ada
                if ($currentAvatar && file_exists('../../' . $currentAvatar)) {
                    unlink('../../' . $currentAvatar);
                }
                
                // Kirim respons sukses
                sendJsonResponse([
                    'success' => true,
                    'message' => 'Avatar uploaded successfully',
                    'avatar_url' => $avatarPathForDb
                ]);
            } else {
                // Hapus file yang baru diunggah jika update DB gagal
                if (file_exists($targetPath)) {
                    unlink($targetPath);
                }
                sendJsonResponse(['error' => 'Failed to update database'], 500);
            }
        } else {
            sendJsonResponse(['error' => 'Failed to process image'], 500);
        }
        
    // Handle DELETE (hapus avatar)
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Ambil path avatar saat ini
        $currentAvatarQuery = "SELECT avatar FROM users WHERE id = ?";
        $currentAvatarStmt = $db->prepare($currentAvatarQuery);
        $currentAvatarStmt->execute([$user['id']]);
        $currentAvatar = $currentAvatarStmt->fetchColumn();
        
        // Update database, set avatar ke NULL
        $updateQuery = "UPDATE users SET avatar = NULL WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        
        if ($updateStmt->execute([$user['id']])) {
            // Hapus file avatar lama jika ada
            if ($currentAvatar && file_exists('../../' . $currentAvatar)) {
                unlink('../../' . $currentAvatar);
            }
            
            // Kirim respons sukses
            sendJsonResponse(['success' => true, 'message' => 'Avatar removed successfully']);
        } else {
            sendJsonResponse(['error' => 'Failed to remove avatar'], 500);
        }
        
    } else {
        // Handle metode lain
        sendJsonResponse(['error' => 'Method not allowed'], 405);
    }
    
} catch (Exception $e) {
    // Handle error
    error_log("Avatar API error: " . $e->getMessage());
    sendJsonResponse(['error' => 'Internal server error: ' . $e->getMessage()], 500);
}

// Fungsi untuk mengubah ukuran dan menyimpan gambar
function resizeAndSaveImage($sourcePath, $targetPath, $mimeType, $newSize = 300) {
    try {
        // Buat resource gambar berdasarkan tipe mime
        switch ($mimeType) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $source = imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $source = imagecreatefromgif($sourcePath);
                break;
            default:
                return false;
        }
        if (!$source) return false;
        
        // Dapatkan dimensi asli
        $originalWidth = imagesx($source);
        $originalHeight = imagesy($source);
        $size = min($originalWidth, $originalHeight);
        
        // Hitung koordinat untuk pemotongan tengah (center crop)
        $cropX = ($originalWidth - $size) / 2;
        $cropY = ($originalHeight - $size) / 2;
        
        // Buat gambar baru
        $resized = imagecreatetruecolor($newSize, $newSize);
        
        // Jaga transparansi untuk PNG dan GIF
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }
        
        // Ubah ukuran dan potong gambar
        imagecopyresampled(
            $resized, $source,
            0, 0, (int)$cropX, (int)$cropY,
            $newSize, $newSize, $size, $size
        );
        
        // Simpan gambar baru sebagai JPEG dengan kualitas 85%
        $result = imagejpeg($resized, $targetPath, 85);
        
        // Bersihkan memori
        imagedestroy($source);
        imagedestroy($resized);
        
        return $result;
        
    } catch (Exception $e) {
        // Handle error saat proses gambar
        error_log("Image resize error: " . $e->getMessage());
        return false;
    }
}
?>