<?php
// config/config.php

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'ester.mkuya');
define('DB_PASS', '95079507');
define('DB_NAME', 'webtech_2025a_ester_mkuya');

// Application Settings
define('APP_NAME', 'DocBook');
define('APP_URL', 'http://169.239.251.102:341/~ester.mkuya/');

// Error Reporting (Enable for development)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set Timezone
date_default_timezone_set('Asia/Kolkata'); // Adjust based on user location if known, generic for now

// Start Session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
