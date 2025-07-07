<?php
require_once __DIR__ . '/../../config/database.php';

$auth = new Auth();
$auth->requireAuth(); 

$user = $auth->getCurrentUser();
$database = new Database();
$db = $database->getConnection();

$message = '';
$messageType = '';

function validateActivityDate($eventDate, $eventTime = null) {
    if (empty($eventDate)) {
        return 'Tanggal kegiatan wajib diisi';
    }
    
    $selectedDate = new DateTime($eventDate);
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    
    if ($selectedDate < $today) {
        return 'Tanggal kegiatan tidak boleh di masa lalu';
    }
    
    
    if (!empty($eventTime)) {
        $selectedDateTime = new DateTime($eventDate . ' ' . $eventTime);
        $now = new DateTime();
        
        if ($selectedDateTime < $now) {
            return 'Waktu kegiatan tidak boleh di masa lalu';
        }
    }
    
    return null; 
}

// Fungsi validasi tanggal lost & found
function validateLostFoundDate($dateOccurred) {
    if (empty($dateOccurred)) {
        return 'Tanggal kejadian wajib diisi';
    }
    
    $selectedDate = new DateTime($dateOccurred);
    $today = new DateTime();
    $today->setTime(23, 59, 59);
    
    if ($selectedDate > $today) {
        return 'Tanggal kejadian tidak boleh di masa depan';
    }
    
    return null; // Valid
}

// Handle edit lost & found item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_lost_found'])) {
    $itemId = $_POST['item_id'] ?? 0;
    
    if ($itemId > 0) {
        // Check if item belongs to current user
        $checkQuery = "SELECT * FROM lost_found_items WHERE id = ? AND user_id = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$itemId, $user['id']]);
        $item = $checkStmt->fetch();
        
        if ($item) {
            $data = [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'type' => $_POST['type'] ?? '',
                'category_id' => $_POST['category_id'] ?? '',
                'location' => $_POST['location'] ?? '',
                'date_occurred' => $_POST['date_occurred'] ?? ''
            ];
            
            // Validasi basic
            if (empty($data['title']) || empty($data['description']) || empty($data['type']) || 
                empty($data['category_id']) || empty($data['location']) || empty($data['date_occurred'])) {
                $message = 'Semua field wajib diisi';
                $messageType = 'error';
            } else {
                $dateError = validateLostFoundDate($data['date_occurred']);
                if ($dateError) {
                    $message = $dateError;
                    $messageType = 'error';
                } else {
                    // Handle image upload
                    $imagePath = $item['image']; // Keep existing image by default
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = 'uploads/lost-found/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                        
                        if (in_array($fileExtension, $allowedExtensions)) {
                            // Delete old image if exists
                            if (!empty($item['image']) && file_exists($item['image'])) {
                                unlink($item['image']);
                            }
                            
                            $fileName = uniqid() . '.' . $fileExtension;
                            $targetPath = $uploadDir . $fileName;
                            
                            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                                $imagePath = $targetPath;
                            }
                        }
                    }
                    
                    // Update database
                    $updateQuery = "UPDATE lost_found_items SET title = ?, description = ?, type = ?, category_id = ?, location = ?, date_occurred = ?, image = ? WHERE id = ? AND user_id = ?";
                    $updateStmt = $db->prepare($updateQuery);
                    
                    if ($updateStmt->execute([$data['title'], $data['description'], $data['type'], $data['category_id'], 
                                             $data['location'], $data['date_occurred'], $imagePath, $itemId, $user['id']])) {
                        header('Location: profile.php?edited_lf=1');
                        exit;
                    } else {
                        $message = 'Gagal mengupdate laporan';
                        $messageType = 'error';
                    }
                }
            }
        } else {
            $message = 'Laporan tidak ditemukan atau Anda tidak memiliki akses';
            $messageType = 'error';
        }
    }
}

// Handle Activity form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_activity'])) {
    $data = [
        'title' => $_POST['title'] ?? '',
        'description' => $_POST['description'] ?? '',
        'category_id' => $_POST['category_id'] ?? '',
        'event_date' => $_POST['event_date'] ?? '',
        'event_time' => $_POST['event_time'] ?? '',
        'location' => $_POST['location'] ?? '',
        'organizer' => $_POST['organizer'] ?? ''
    ];
    
    // Validasi basic
    if (empty($data['title']) || empty($data['description']) || empty($data['category_id']) || 
        empty($data['event_date']) || empty($data['event_time']) || empty($data['location']) || empty($data['organizer'])) {
        $message = 'Semua field wajib diisi';
        $messageType = 'error';
    } else {
        $dateError = validateActivityDate($data['event_date'], $data['event_time']);
        if ($dateError) {
            $message = $dateError;
            $messageType = 'error';
        } else {
            // Handle image upload
            $imagePath = null;
            if (isset($_FILES['act_image']) && $_FILES['act_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/activities/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileExtension = strtolower(pathinfo($_FILES['act_image']['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($fileExtension, $allowedExtensions)) {
                    $fileName = uniqid() . '.' . $fileExtension;
                    $targetPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['act_image']['tmp_name'], $targetPath)) {
                        $imagePath = $targetPath;
                    }
                }
            }
            
            $insertQuery = "INSERT INTO activities (user_id, category_id, title, description, event_date, event_time, location, organizer, contact_info, image) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($insertQuery);
            
            if ($stmt->execute([$user['id'], $data['category_id'], $data['title'], $data['description'], 
                               $data['event_date'], $data['event_time'], $data['location'], $data['organizer'], $user['phone'], $imagePath])) {
                $message = 'Kegiatan berhasil ditambahkan!';
                $messageType = 'success';
            } else {
                $message = 'Gagal menambahkan kegiatan';
                $messageType = 'error';
            }
        }
    }
}

// Improved delete handling with better error checking and logging
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Handle delete lost & found item
    if (isset($_POST['delete_lost_found'])) {
        $itemId = intval($_POST['item_id'] ?? 0);
        
        if ($itemId > 0) {
            try {
                // Check if item belongs to current user
                $checkQuery = "SELECT * FROM lost_found_items WHERE id = ? AND user_id = ?";
                $checkStmt = $db->prepare($checkQuery);
                $checkStmt->execute([$itemId, $user['id']]);
                $item = $checkStmt->fetch();
                
                if ($item) {
                    // Begin transaction
                    $db->beginTransaction();
                    
                    // Delete image file if exists
                    if (!empty($item['image']) && file_exists($item['image'])) {
                        unlink($item['image']);
                    }
                    
                    // Delete from database
                    $deleteQuery = "DELETE FROM lost_found_items WHERE id = ? AND user_id = ?";
                    $deleteStmt = $db->prepare($deleteQuery);
                    
                    if ($deleteStmt->execute([$itemId, $user['id']])) {
                        $db->commit();
                        $_SESSION['success_message'] = "Laporan '{$item['title']}' berhasil dihapus!";
                        header('Location: profile.php?deleted_lf=1');
                        exit;
                    } else {
                        $db->rollback();
                        $message = 'Gagal menghapus laporan dari database';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Laporan tidak ditemukan atau Anda tidak memiliki akses';
                    $messageType = 'error';
                }
            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollback();
                }
                error_log("Error deleting lost-found item: " . $e->getMessage());
                $message = 'Terjadi kesalahan saat menghapus laporan: ' . $e->getMessage();
                $messageType = 'error';
            }
        } else {
            $message = 'ID laporan tidak valid';
            $messageType = 'error';
        }
    }
    
    // Handle delete activity
    if (isset($_POST['delete_activity'])) {
        $itemId = intval($_POST['item_id'] ?? 0);
        
        if ($itemId > 0) {
            try {
                // Check if activity belongs to current user
                $checkQuery = "SELECT * FROM activities WHERE id = ? AND user_id = ?";
                $checkStmt = $db->prepare($checkQuery);
                $checkStmt->execute([$itemId, $user['id']]);
                $activity = $checkStmt->fetch();
                
                if ($activity) {
                    // Begin transaction
                    $db->beginTransaction();
                    
                    // Delete image file if exists
                    if (!empty($activity['image']) && file_exists($activity['image'])) {
                        unlink($activity['image']);
                    }
                    
                    // Delete from database
                    $deleteQuery = "DELETE FROM activities WHERE id = ? AND user_id = ?";
                    $deleteStmt = $db->prepare($deleteQuery);
                    
                    if ($deleteStmt->execute([$itemId, $user['id']])) {
                        $db->commit();
                        $_SESSION['success_message'] = "Kegiatan '{$activity['title']}' berhasil dihapus!";
                        header('Location: profile.php?deleted_activity=1');
                        exit;
                    } else {
                        $db->rollback();
                        $message = 'Gagal menghapus kegiatan dari database';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Kegiatan tidak ditemukan atau Anda tidak memiliki akses';
                    $messageType = 'error';
                }
            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollback();
                }
                error_log("Error deleting activity: " . $e->getMessage());
                $message = 'Terjadi kesalahan saat menghapus kegiatan: ' . $e->getMessage();
                $messageType = 'error';
            }
        } else {
            $message = 'ID kegiatan tidak valid';
            $messageType = 'error';
        }
    }
}

// Handle success messages from redirects
if (isset($_GET['deleted_lf']) && $_GET['deleted_lf'] == '1') {
    if (isset($_SESSION['success_message'])) {
        $message = $_SESSION['success_message'];
        $messageType = 'success';
        unset($_SESSION['success_message']);
    } else {
        $message = 'Laporan berhasil dihapus!';
        $messageType = 'success';
    }
}

if (isset($_GET['deleted_activity']) && $_GET['deleted_activity'] == '1') {
    if (isset($_SESSION['success_message'])) {
        $message = $_SESSION['success_message'];
        $messageType = 'success';
        unset($_SESSION['success_message']);
    } else {
        $message = 'Kegiatan berhasil dihapus!';
        $messageType = 'success';
    }
}

// Handle avatar upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_avatar'])) {
  if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/avatars/';
    
    // Buat direktori jika belum ada
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileExtension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (in_array($fileExtension, $allowedExtensions)) {
        // Validasi ukuran file (max 5MB untuk avatar)
        if ($_FILES['avatar']['size'] > 5 * 1024 * 1024) {
            $message = 'Ukuran file terlalu besar! Maksimal 5MB untuk foto profil.';
            $messageType = 'error';
        } else {
            // Validasi adalah gambar yang valid
            $imageInfo = getimagesize($_FILES['avatar']['tmp_name']);
            if ($imageInfo === false) {
                $message = 'File yang diupload bukan gambar yang valid.';
                $messageType = 'error';
            } else {
                // Hapus avatar lama jika ada
                if (!empty($user['avatar'])) {
                    $oldAvatarPath = '';
                    if (strpos($user['avatar'], 'uploads/') === 0) {
                        // Sudah path lengkap
                        $oldAvatarPath = $user['avatar'];
                    } else {
                        // Masih nama file saja
                        $oldAvatarPath = $uploadDir . $user['avatar'];
                    }
                    
                    if (file_exists($oldAvatarPath)) {
                        unlink($oldAvatarPath);
                    }
                }
                
                $fileName = 'avatar_' . $user['id'] . '_' . time() . '.' . $fileExtension;
                $targetPath = $uploadDir . $fileName;
                
                // PERBAIKAN: Simpan path lengkap ke database
                $avatarPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
                    // Update database
                    try {
                        $updateQuery = "UPDATE users SET avatar = ? WHERE id = ?";
                        $stmt = $db->prepare($updateQuery);
                        
                        if ($stmt->execute([$avatarPath, $user['id']])) {
                            // PERBAIKAN: Update session data dengan path lengkap
                            $_SESSION['user_data']['avatar'] = $avatarPath;
                            
                            // Redirect untuk menghindari resubmission dan alert berulang
                            header('Location: profile.php?avatar_updated=1');
                            exit;
                        } else {
                            $message = 'Gagal memperbarui foto profil di database.';
                            $messageType = 'error';
                            
                            // Hapus file jika update database gagal
                            if (file_exists($targetPath)) {
                                unlink($targetPath);
                            }
                        }
                    } catch (Exception $e) {
                        $message = 'Terjadi kesalahan: ' . $e->getMessage();
                        $messageType = 'error';
                        
                        // Hapus file jika terjadi error
                        if (file_exists($targetPath)) {
                            unlink($targetPath);
                        }
                    }
                } else {
                    $message = 'Gagal mengupload file foto profil.';
                    $messageType = 'error';
                }
            }
        }
    } else {
        $message = 'Format file tidak didukung. Hanya JPG, PNG, dan GIF yang diperbolehkan.';
        $messageType = 'error';
    }
  } else {
      $message = 'Silakan pilih file foto profil terlebih dahulu.';
      $messageType = 'error';
  }
}


// Check untuk pesan dari redirect
if (isset($_GET['avatar_updated']) && $_GET['avatar_updated'] == '1') {
    $message = 'Foto profil berhasil diperbarui!';
    $messageType = 'success';
}

if (isset($_GET['deleted_lf']) && $_GET['deleted_lf'] == '1') {
    $message = 'Laporan Lost & Found berhasil dihapus!';
    $messageType = 'success';
}

if (isset($_GET['deleted_activity']) && $_GET['deleted_activity'] == '1') {
    $message = 'Kegiatan berhasil dihapus!';
    $messageType = 'success';
}

if (isset($_GET['edited_lf']) && $_GET['edited_lf'] == '1') {
    $message = 'Laporan Lost & Found berhasil diperbarui!';
    $messageType = 'success';
}

if (isset($_GET['edited_activity']) && $_GET['edited_activity'] == '1') {
    $message = 'Kegiatan berhasil diperbarui!';
    $messageType = 'success';
}

// Handle Lost & Found form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_lost_found'])) {
    $data = [
        'title' => $_POST['title'] ?? '',
        'description' => $_POST['description'] ?? '',
        'type' => $_POST['type'] ?? '',
        'category_id' => $_POST['category_id'] ?? '',
        'location' => $_POST['location'] ?? '',
        'date_occurred' => $_POST['date_occurred'] ?? ''
    ];
    
    // Validasi basic
    if (empty($data['title']) || empty($data['description']) || empty($data['type']) || 
        empty($data['category_id']) || empty($data['location']) || empty($data['date_occurred'])) {
        $message = 'Semua field wajib diisi';
        $messageType = 'error';
    } else {
        // Validasi tanggal kejadian
        $dateError = validateLostFoundDate($data['date_occurred']);
        if ($dateError) {
            $message = $dateError;
            $messageType = 'error';
        } else {
            // Handle image upload
            $imagePath = null;
            if (isset($_FILES['lf_image']) && $_FILES['lf_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/lost-found/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileExtension = strtolower(pathinfo($_FILES['lf_image']['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($fileExtension, $allowedExtensions)) {
                    $fileName = uniqid() . '.' . $fileExtension;
                    $targetPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['lf_image']['tmp_name'], $targetPath)) {
                        $imagePath = $targetPath;
                    }
                }
            }
            
            $insertQuery = "INSERT INTO lost_found_items (user_id, category_id, title, description, type, location, date_occurred, contact_info, image) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($insertQuery);
            
            if ($stmt->execute([$user['id'], $data['category_id'], $data['title'], $data['description'], 
                               $data['type'], $data['location'], $data['date_occurred'], $user['phone'], $imagePath])) {
                $message = 'Laporan berhasil ditambahkan!';
                $messageType = 'success';
            } else {
                $message = 'Gagal menambahkan laporan';
                $messageType = 'error';
            }
        }
    }
}

// Handle Activity form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_activity'])) {
    $data = [
        'title' => $_POST['title'] ?? '',
        'description' => $_POST['description'] ?? '',
        'category_id' => $_POST['category_id'] ?? '',
        'event_date' => $_POST['event_date'] ?? '',
        'event_time' => $_POST['event_time'] ?? '',
        'location' => $_POST['location'] ?? '',
        'organizer' => $_POST['organizer'] ?? ''
    ];
    
    // Validasi basic
    if (empty($data['title']) || empty($data['description']) || empty($data['category_id']) || 
        empty($data['event_date']) || empty($data['event_time']) || empty($data['location']) || empty($data['organizer'])) {
        $message = 'Semua field wajib diisi';
        $messageType = 'error';
    } else {
        // Validasi tanggal dan waktu kegiatan
        $dateError = validateActivityDate($data['event_date'], $data['event_time']);
        if ($dateError) {
            $message = $dateError;
            $messageType = 'error';
        } else {
            // Handle image upload
            $imagePath = null;
            if (isset($_FILES['act_image']) && $_FILES['act_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/activities/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileExtension = strtolower(pathinfo($_FILES['act_image']['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($fileExtension, $allowedExtensions)) {
                    $fileName = uniqid() . '.' . $fileExtension;
                    $targetPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['act_image']['tmp_name'], $targetPath)) {
                        $imagePath = $targetPath;
                    }
                }
            }
            
            $insertQuery = "INSERT INTO activities (user_id, category_id, title, description, event_date, event_time, location, organizer, contact_info, image) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($insertQuery);
            
            if ($stmt->execute([$user['id'], $data['category_id'], $data['title'], $data['description'], 
                               $data['event_date'], $data['event_time'], $data['location'], $data['organizer'], $user['phone'], $imagePath])) {
                $message = 'Kegiatan berhasil ditambahkan!';
                $messageType = 'success';
            } else {
                $message = 'Gagal menambahkan kegiatan';
                $messageType = 'error';
            }
        }
    }
}


// Handle edit profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_profile'])) {
    $data = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'nim' => trim($_POST['nim'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'current_password' => $_POST['current_password'] ?? '',
        'new_password' => $_POST['new_password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? ''
    ];
    
    // Validasi input dasar
    if (empty($data['first_name']) || empty($data['last_name']) || 
        empty($data['nim']) || empty($data['email']) || empty($data['phone'])) {
        $message = 'Semua field data pribadi wajib diisi';
        $messageType = 'error';
    } 
    // Validasi email domain
    else {
        $email_domain = substr(strrchr($data['email'], "@"), 1);
        if ($email_domain !== 'stis.ac.id' && $email_domain !== 'bps.go.id') {
            $message = 'Email harus berakhiran @stis.ac.id atau @bps.go.id';
            $messageType = 'error';
        }
        // Cek apakah email sudah digunakan oleh user lain
        else {
            $checkEmailQuery = "SELECT id FROM users WHERE email = ? AND id != ?";
            $checkEmailStmt = $db->prepare($checkEmailQuery);
            $checkEmailStmt->execute([$data['email'], $user['id']]);
            
            if ($checkEmailStmt->fetch()) {
                $message = 'Email sudah digunakan oleh pengguna lain';
                $messageType = 'error';
            }
            // Cek apakah NIM sudah digunakan oleh user lain
            else {
                $checkNimQuery = "SELECT id FROM users WHERE nim = ? AND id != ?";
                $checkNimStmt = $db->prepare($checkNimQuery);
                $checkNimStmt->execute([$data['nim'], $user['id']]);
                
                if ($checkNimStmt->fetch()) {
                    $message = 'NIM sudah digunakan oleh pengguna lain';
                    $messageType = 'error';
                }
                // Proses update password jika diisi
                else {
                    $updatePasswordQuery = '';
                    $updatePasswordValue = null;
                    
                    // Jika user ingin mengubah password
                    if (!empty($data['current_password']) || !empty($data['new_password']) || !empty($data['confirm_password'])) {
                        if (empty($data['current_password']) || empty($data['new_password']) || empty($data['confirm_password'])) {
                            $message = 'Untuk mengubah password, semua field password harus diisi';
                            $messageType = 'error';
                        } else if ($data['new_password'] !== $data['confirm_password']) {
                            $message = 'Konfirmasi password baru tidak cocok';
                            $messageType = 'error';
                        } else if (strlen($data['new_password']) < 6) {
                            $message = 'Password baru minimal 6 karakter';
                            $messageType = 'error';
                        } else {
                            // Verifikasi password saat ini
                            $currentUserQuery = "SELECT password FROM users WHERE id = ?";
                            $currentUserStmt = $db->prepare($currentUserQuery);
                            $currentUserStmt->execute([$user['id']]);
                            $currentUserData = $currentUserStmt->fetch();
                            
                            if (!password_verify($data['current_password'], $currentUserData['password'])) {
                                $message = 'Password saat ini tidak benar';
                                $messageType = 'error';
                            } else {
                                $updatePasswordQuery = ', password = ?';
                                $updatePasswordValue = password_hash($data['new_password'], PASSWORD_DEFAULT);
                            }
                        }
                    }
                    
                    // Jika tidak ada error, lakukan update
                    if (empty($message)) {
                        try {
                            $updateQuery = "UPDATE users SET first_name = ?, last_name = ?, nim = ?, email = ?, phone = ?" . $updatePasswordQuery . " WHERE id = ?";
                            $updateStmt = $db->prepare($updateQuery);
                            
                            $updateParams = [
                                $data['first_name'],
                                $data['last_name'], 
                                $data['nim'],
                                $data['email'],
                                $data['phone']
                            ];
                            
                            if ($updatePasswordValue) {
                                $updateParams[] = $updatePasswordValue;
                            }
                            $updateParams[] = $user['id'];
                            
                            if ($updateStmt->execute($updateParams)) {
                                // Update session data
                                $_SESSION['user_data'] = [
                                    'id' => $user['id'],
                                    'nim' => $data['nim'],
                                    'first_name' => $data['first_name'],
                                    'last_name' => $data['last_name'],
                                    'email' => $data['email'],
                                    'phone' => $data['phone'],
                                    'role' => $user['role']
                                ];
                                
                                // Log activity
                                $logQuery = "INSERT INTO activity_logs (user_id, action, table_name, record_id, description) VALUES (?, ?, ?, ?, ?)";
                                $logStmt = $db->prepare($logQuery);
                                $logDescription = 'Profile updated';
                                if ($updatePasswordValue) {
                                    $logDescription .= ' with password change';
                                }
                                $logStmt->execute([$user['id'], 'UPDATE', 'users', $user['id'], $logDescription]);
                                
                                $message = 'Profil berhasil diperbarui';
                                $messageType = 'success';
                                
                                // Refresh user data
                                $user = $_SESSION['user_data'];
                            } else {
                                $message = 'Gagal memperbarui profil';
                                $messageType = 'error';
                            }
                        } catch (Exception $e) {
                            error_log("Update profile error: " . $e->getMessage());
                            $message = 'Terjadi kesalahan saat memperbarui profil';
                            $messageType = 'error';
                        }
                    }
                }
            }
        }
    }
}

// Get updated user data for avatar display
$user = $auth->getCurrentUser();

// Get categories
$lostFoundCategoriesQuery = "SELECT * FROM categories WHERE type = 'lost_found'";
$lostFoundCategoriesStmt = $db->prepare($lostFoundCategoriesQuery);
$lostFoundCategoriesStmt->execute();
$lostFoundCategories = $lostFoundCategoriesStmt->fetchAll();

$activityCategoriesQuery = "SELECT * FROM categories WHERE type = 'activity'";
$activityCategoriesStmt = $db->prepare($activityCategoriesQuery);
$activityCategoriesStmt->execute();
$activityCategories = $activityCategoriesStmt->fetchAll();

// Get user's lost & found items
$lostFoundQuery = "SELECT lf.*, c.name as category_name
                   FROM lost_found_items lf
                   JOIN categories c ON lf.category_id = c.id
                   WHERE lf.user_id = ?
                   ORDER BY lf.created_at DESC";
$lostFoundStmt = $db->prepare($lostFoundQuery);
$lostFoundStmt->execute([$user['id']]);
$userLostFound = $lostFoundStmt->fetchAll();

// Get user's activities
$activitiesQuery = "SELECT a.*, c.name as category_name
                    FROM activities a
                    JOIN categories c ON a.category_id = c.id
                    WHERE a.user_id = ?
                    ORDER BY a.created_at DESC";
$activitiesStmt = $db->prepare($activitiesQuery);
$activitiesStmt->execute([$user['id']]);
$userActivities = $activitiesStmt->fetchAll();

// Calculate statistics
$totalLostFound = count($userLostFound);
$totalActivities = count($userActivities);
$resolvedItems = count(array_filter($userLostFound, function($item) {
    return $item['status'] === 'selesai';
}));


?>