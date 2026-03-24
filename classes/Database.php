<?php

class Database
{
    private static $instance = null;

    private $db;

    private function __construct()
    {
        require_once __DIR__ . '/../config/database.php';

        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,    // Lempar exception jika query error
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,          // Hasil query berupa array asosiatif
            PDO::ATTR_EMULATE_PREPARES   => false,                     // Gunakan native prepared statement
        ];

        try {
            $this->db = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<div style="background:#7A1B22;color:#fff;padding:20px;font-family:sans-serif;">
                    <strong>Koneksi Database Gagal.</strong> Periksa konfigurasi di config/database.php.<br>
                    <small>Detail: ' . $e->getMessage() . '</small>
                 </div>');
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }

        return self::$instance;
    }

    public function getConnection()
    {
        return $this->db;
    }

    // Cegah cloning object (bagian dari Singleton)
    private function __clone() {}

    // Cegah unserialize object (bagian dari Singleton)
    public function __wakeup() {}
}
