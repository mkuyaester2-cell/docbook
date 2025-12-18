<?php
// fix_live_db.php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Database.php';

echo "<h2>DocBook Database Self-Healing Script</h2>";
echo "Attempting to connect to database: <b>" . DB_NAME . "</b>...<br>";

try {
    $db = Database::getInstance();
    echo "<span style='color:green'>Connection Successful!</span><br><br>";
    
    // List databases
    echo "<b>Database Environment:</b><br>";
    echo "- Current Database: <b>" . DB_NAME . "</b><br>";
    echo "- Host: <b>" . DB_HOST . "</b><br>";
    echo "<br>";

    // Show Counts
    echo "<b>Current Stats in this Database:</b><br>";
    $userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $doctorCount = $db->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
    $patientCount = $db->query("SELECT COUNT(*) FROM patients")->fetchColumn();
    $adminCount = $db->query("SELECT COUNT(*) FROM users WHERE user_type = 'admin'")->fetchColumn();

    echo "- Total Users: <b>$userCount</b><br>";
    echo "- Admins: <b>$adminCount</b><br>";
    echo "- Doctors: <b>$doctorCount</b><br>";
    echo "- Patients: <b>$patientCount</b><br>";
    echo "<br>";

    // 1. Handle Admin Seeding
    if (isset($_GET['seed_admin'])) {
        echo "<b>Seeding Admin Account...</b><br>";
        $email = 'admin@docbook.co.tz';
        $pass = password_hash('admin123', PASSWORD_BCRYPT);
        
        $check = $db->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            echo "<span style='color:orange'>Admin email already exists. Resetting password to 'admin123'...</span><br>";
            $stmt = $db->prepare("UPDATE users SET password_hash = ?, user_type = 'admin' WHERE email = ?");
            $stmt->execute([$pass, $email]);
        } else {
            $db->exec("INSERT INTO users (email, password_hash, user_type) VALUES ('$email', '$pass', 'admin')");
            $userId = $db->lastInsertId();
            $db->exec("INSERT INTO admins (user_id, full_name, phone) VALUES ($userId, 'System Admin', '0700000000')");
            echo "<span style='color:green'>Admin account created!</span><br>";
        }
    }

    // 2. Check if registration_status column exists in doctors table
    echo "<b>Checking 'doctors' table structure...</b><br>";
    $stmt = $db->query("SHOW COLUMNS FROM doctors LIKE 'registration_status'");
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        echo "<span style='color:orange'>Column 'registration_status' not found. Adding it now...</span><br>";
        $db->exec("ALTER TABLE doctors ADD COLUMN registration_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER consultation_fee");
        $db->exec("UPDATE doctors SET registration_status = 'approved'");
        echo "<b style='color:green'>DATABASE UPDATED SUCCESSFULLY!</b><br>";
    } else {
        echo "The 'registration_status' column exists.<br>";
    }

    // List recent users to prove they are here
    echo "<br><b>Recently Registered Users (Last 5):</b><br>";
    $recentUsers = $db->query("SELECT email, user_type, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
    if (empty($recentUsers)) {
        echo "<span style='color:red'>No users found in this database!</span><br>";
    } else {
        foreach ($recentUsers as $u) {
            echo "- " . $u['email'] . " (" . $u['user_type'] . ") at " . $u['created_at'] . "<br>";
        }
    }
    
    echo "<br><div style='padding:20px; background:#f0f9ff; border:1px solid #bae6fd; border-radius:8px;'>";
    echo "<b>Troubleshooting & Utilities:</b><br>";
    echo "1. <a href='fix_live_db.php?seed_admin=1' style='color:red; font-weight:bold;'>CLICK HERE TO RESET/CREATE ADMIN (admin@docbook.co.tz / admin123)</a><br>";
    echo "2. <a href='patient/browse-doctors.php'>Go to Find Doctors</a><br>";
    echo "3. <a href='login.php'>Go to Login Page</a><br>";
    echo "4. <a href='index.php'>Go to Homepage</a><br>";
    echo "</div>";
    
    echo "<br><b>Current URL Settings:</b><br>";
    echo "- APP_URL in config: <b>" . APP_URL . "</b><br>";
    $actual_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . str_replace('fix_live_db.php', '', $_SERVER['REQUEST_URI']);
    echo "- Actual Server URL: <b>" . $actual_url . "</b><br>";
    if (trim(APP_URL, '/') !== trim($actual_url, '/')) {
        echo "<span style='color:red'>Warning: APP_URL does not match your server URL! This will break redirects.</span>";
    }
    
} catch (Exception $e) {
    echo "<br><b style='color:red'>CRITICAL ERROR:</b><br>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "Please ensure your <b>config/Database.php</b> has the correct credentials for your live server.";
}

echo "<br><br><a href='index.php'>&larr; Back to Homepage</a>";
?>
