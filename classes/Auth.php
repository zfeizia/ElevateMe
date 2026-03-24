<?php

// classes/Auth.php
// Menangani autentikasi: login, logout, register, cek role
// Juga menyediakan helper CSRF token untuk keamanan form

class Auth
{
    // Koneksi PDO
    private $db;

    function __construct()
    {
        $this->db = Database::getInstance()->getConnection();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    function login($username, $password)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE username = :username LIMIT 1"
        );
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        // Cek apakah user ditemukan & password cocok
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];
            $_SESSION['email']    = $user['email'];

            // Regenerasi session ID untuk mencegah session fixation attack
            session_regenerate_id(true);

            return true;
        }

        return false;
    }

    function register($username, $email, $password, $role = 'attendee')
    {
        // Cek apakah username atau email sudah terdaftar
        $stmt = $this->db->prepare(
            "SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1"
        );
        $stmt->execute([':username' => $username, ':email' => $email]);

        if ($stmt->fetch()) {
            return false; // Username atau email sudah ada
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare(
            "INSERT INTO users (username, password, email, role)
             VALUES (:username, :password, :email, :role)"
        );

        return $stmt->execute([
            ':username' => $username,
            ':password' => $hashed_password,
            ':email'    => $email,
            ':role'     => $role,
        ]);
    }

    function logout()
    {
        $_SESSION = [];
        session_destroy();
        header('Location: ' . BASE_URL . '/views/auth/login.php');
        exit;
    }

    function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    function getRole()
    {
        return $_SESSION['role'] ?? null;
    }

    function getUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }

    function requireLogin($redirect_after = null)
    {
        if (!$this->isLoggedIn()) {
            if ($redirect_after) {
                $_SESSION['redirect_after_login'] = $redirect_after;
            }
            header('Location: ' . BASE_URL . '/views/auth/login.php');
            exit;
        }
    }
    public function requireRole($roles) {
        $role = $this->getRole(); 
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        if (!in_array($role, $roles)) {
            $auth = $this;
            http_response_code(403);
            include __DIR__ . '/../views/layouts/403.php';
            exit;
        }
    }

// --- FUNGSI FLASH MESSAGE ---

    public function setFlash($type, $message) {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    public function getFlash() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']); // Langsung hapus setelah diambil
            return $flash;
        }
        return null;
    }

    // --- FUNGSI KEAMANAN CSRF ---

    public function generateCsrfToken() {
        // Buat token baru kalau belum ada di session
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function verifyCsrfToken($token) {
        // Cocokkan token dari form dengan yang ada di session
        if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
            return true;
        }
        return false;
    }
    }

    function generateCsrfToken()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    function verifyCsrfToken($token)
    {
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(403);
            die('<p style="color:red;font-family:sans-serif;">Permintaan tidak valid (CSRF token mismatch). Silakan refresh halaman.</p>');
        }

        // Regenerasi token setelah digunakan agar tidak bisa dipakai ulang
        unset($_SESSION['csrf_token']);

        return true;
    }

    function setFlash($type, $message)
    {
        $_SESSION['flash'][$type] = $message;
    }

    function getFlash()
    {
        $flash = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);

        return $flash;
    }
    
