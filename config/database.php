<?php
class Database
{
    private $host = "localhost";
    private $db_name = "quanly_giangvien";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection()
    {
        try {
            // First check if database exists
            $this->conn = new PDO("mysql:host=" . $this->host, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Create database if not exists
            $this->conn->exec("CREATE DATABASE IF NOT EXISTS " . $this->db_name);

            // Connect to the database
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->exec("set names utf8");

            // Initialize database if needed
            $this->initializeDatabase();

            return $this->conn;
        } catch (PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            return null;
        }
    }

    private function initializeDatabase()
    {
        try {
            // Check if users table exists
            $stmt = $this->conn->query("SHOW TABLES LIKE 'users'");
            if ($stmt->rowCount() == 0) {
                // Drop all tables first to avoid conflicts
                $this->conn->exec("SET FOREIGN_KEY_CHECKS = 0");
                $tables = [
                    'users',
                    'thanh_toan_luong',
                    'bang_luong',
                    'day_thay',
                    'diem_danh',
                    'buoi_day',
                    'lich_day',
                    'lich_day_dinh_ky',
                    'mon_hoc',
                    'giaovien',
                    'bangcap',
                    'khoa',
                    'hoc_ky'
                ];

                foreach ($tables as $table) {
                    $this->conn->exec("DROP TABLE IF EXISTS " . $table);
                }
                $this->conn->exec("SET FOREIGN_KEY_CHECKS = 1");

                // Execute database initialization script
                $sql = file_get_contents(__DIR__ . '/../database.sql');
                $this->conn->exec($sql);
            }
        } catch (PDOException $e) {
            error_log("Database initialization error: " . $e->getMessage());
        }
    }
}
?>