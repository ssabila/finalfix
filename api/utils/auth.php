<?php
// Memuat file konfigurasi database
require_once '../config/database.php';

class Auth {
    private $db;
    
    // Konstruktor untuk mendapatkan koneksi database
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // Membuat token sesi yang aman secara kriptografis
    public function generateSessionToken() {
        return bin2hex(random_bytes(32));
    }
    
    // Membuat dan menyimpan sesi pengguna baru di database
    public function createSession($userId) {
        $token = $this->generateSessionToken();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        $query = "INSERT INTO user_sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId, $token, $expiresAt]);
        
        return $token;
    }
    
    // Memvalidasi token sesi dan mengambil data pengguna
    public function validateSession($token) {
        if (!$token) return false;
        
        $query = "SELECT us.*, u.* FROM user_sessions us 
                  JOIN users u ON us.user_id = u.id 
                  WHERE us.session_token = ? AND us.expires_at > NOW() AND u.is_active = 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$token]);
        
        return $stmt->fetch();
    }
    
    // Menghapus sesi pengguna (untuk logout)
    public function deleteSession($token) {
        $query = "DELETE FROM user_sessions WHERE session_token = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$token]);
    }
    
    // Membersihkan sesi yang sudah kedaluwarsa dari database
    public function cleanExpiredSessions() {
        $query = "DELETE FROM user_sessions WHERE expires_at < NOW()";
        $stmt = $this->db->prepare($query);
        return $stmt->execute();
    }
    
    // Mendapatkan data pengguna yang sedang login berdasarkan sesi
    public function getCurrentUser() {
        $token = $this->getSessionToken();
        if (!$token) return null;
        
        $session = $this->validateSession($token);
        if (!$session) return null;
        
        // Mengembalikan data pengguna yang relevan
        return [
            'id' => $session['user_id'],
            'nim' => $session['nim'],
            'first_name' => $session['first_name'],
            'last_name' => $session['last_name'],
            'email' => $session['email'],
            'phone' => $session['phone'],
            'avatar' => $session['avatar']
        ];
    }
    
    // Mengambil token sesi dari header Authorization atau cookie
    private function getSessionToken() {
        // Cek header Authorization terlebih dahulu
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $auth = $headers['Authorization'];
            if (strpos($auth, 'Bearer ') === 0) {
                return substr($auth, 7);
            }
        }
        
        // Jika tidak ada di header, cek cookie
        return isset($_COOKIE['session_token']) ? $_COOKIE['session_token'] : null;
    }
    
    // Memastikan pengguna sudah login, jika tidak, hentikan eksekusi
    public function requireAuth() {
        $user = $this->getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }
        return $user;
    }
}

// Fungsi bantuan (Helper functions)

// Mengirim respons dalam format JSON dan menghentikan skrip
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Mengambil input JSON dari body permintaan
function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true);
}

// Memvalidasi apakah field yang wajib diisi ada dan tidak kosong
function validateRequired($data, $fields) {
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $missing[] = $field;
        }
    }
    return $missing;
}
?>