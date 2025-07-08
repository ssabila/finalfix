<?php
// Konfigurasi database
define('DB_HOST', 'localhost');
define('DB_NAME', 'estatmad');
define('DB_USER', 'root');
define('DB_PASS', '');

class Database {
    // Properti koneksi database
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn = null;

    // Method untuk mendapatkan koneksi database
    public function getConnection() {
        // Buat koneksi hanya jika belum ada (singleton pattern)
        if ($this->conn === null) {
            try {
                // Membuat instance PDO baru
                $this->conn = new PDO(
                    "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                    $this->username,
                    $this->password,
                    [
                        // Pengaturan atribut PDO
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch(PDOException $e) {
                // Tangani error jika koneksi gagal
                error_log("Connection error: " . $e->getMessage());
                throw new Exception("Database connection failed");
            }
        }
        // Kembalikan objek koneksi
        return $this->conn;
    }

    // Method untuk menutup koneksi
    public function closeConnection() {
        // Set koneksi ke null untuk menutupnya
        $this->conn = null;
    }
}
?>