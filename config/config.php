// Load .env file manually (since we might not have composer/vlucas/phpdotenv)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Database Credentials
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'docbook');

// Application Settings
define('APP_NAME', 'DocBook');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost/doctor-appointment-2');

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
