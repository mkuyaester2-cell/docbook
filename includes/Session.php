<?php
// includes/Session.php

class Session {
    // Start session if not started
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // Set session variable
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    // Get session variable
    public static function get($key) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    // Check if session variable exists
    public static function has($key) {
        return isset($_SESSION[$key]);
    }

    // Remove session variable
    public static function remove($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    // Destroy entire session
    public static function destroy() {
        session_destroy();
        $_SESSION = [];
    }

    // Set flash message (once-only message)
    public static function setFlash($key, $message, $type = 'success') {
        $_SESSION['flash'][$key] = [
            'message' => $message,
            'type' => $type
        ];
    }

    // Get and clear flash message
    public static function getFlash($key) {
        if (isset($_SESSION['flash'][$key])) {
            $flash = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $flash;
        }
        return null;
    }
}
?>
