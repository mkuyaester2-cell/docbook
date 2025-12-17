<?php
// includes/Auth.php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/Session.php';

class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
        Session::init();
    }

    // Register a new user
    public function register($email, $password, $role) {
        // Hash password
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        $sql = "INSERT INTO users (email, password_hash, user_type) VALUES (:email, :password_hash, :user_type)";
        $stmt = $this->db->prepare($sql);
        
        try {
            $stmt->execute([
                ':email' => $email,
                ':password_hash' => $password_hash,
                ':user_type' => $role
            ]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            // Check for duplicate email
            if ($e->getCode() == 23000) {
                return false; // Email already exists
            }
            throw $e;
        }
    }

    // Login user
    public function login($email, $password) {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Set session variables
            Session::set('user_id', $user['id']);
            Session::set('user_type', $user['user_type']);
            Session::set('email', $user['email']);
            return true;
        }
        return false;
    }

    // Logout user
    public function logout() {
        Session::destroy();
        return true;
    }

    // Check if user is logged in
    public static function isLoggedIn() {
        Session::init();
        return Session::has('user_id');
    }

    // Require specific role, else redirect
    public static function requireRole($role) {
        if (!self::isLoggedIn()) {
            header('Location: ' . APP_URL . '/login.php');
            exit;
        }

        if (Session::get('user_type') !== $role) {
            // Redirect based on actual role
            $actual_role = Session::get('user_type');
            if ($actual_role) {
                header('Location: ' . APP_URL . '/' . $actual_role . '/dashboard.php');
            } else {
                header('Location: ' . APP_URL . '/login.php');
            }
            exit;
        }
    }
    
    // Get current user ID
    public static function id() {
        return Session::get('user_id');
    }
}
?>
