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
    $clinicCount = $db->query("SELECT COUNT(*) FROM clinics")->fetchColumn();

    echo "- Total Users: <b>$userCount</b><br>";
    echo "- Admins: <b>$adminCount</b><br>";
    echo "- Doctors: <b>$doctorCount</b><br>";
    echo "- Patients: <b>$patientCount</b><br>";
    echo "- Clinics: <b>$clinicCount</b><br>";
    echo "<br>";

    if ($clinicCount == 0) {
        echo "<span style='color:red; font-weight:bold;'>WARNING: No clinics found! Doctors cannot register without a clinic.</span><br>";
        echo "<a href='fix_live_db.php?seed_clinic=1'>[Create a Default Clinic]</a><br><br>";
    }

    if (isset($_GET['seed_clinic'])) {
        echo "<b>Creating Default Clinic...</b><br>";
        $db->exec("INSERT IGNORE INTO addresses (street_address, city, state, postal_code, country) VALUES ('123 Health St', 'Dar es Salaam', 'TZ', '0000', 'Tanzania')");
        $addrId = $db->lastInsertId();
        $db->exec("INSERT INTO clinics (name, address_id, phone, email, created_by) VALUES ('General Medical Center', $addrId, '0700000000', 'clinic@example.com', 1)");
        echo "<span style='color:green'>Default clinic created!</span><br>";
    }

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
    echo "<br><b>Doctor Availability Check:</b><br>";
    $availTable = $db->query("SELECT d.id, d.full_name, (SELECT COUNT(*) FROM doctor_availability WHERE doctor_id = d.id) as days_count FROM doctors d")->fetchAll();
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #eee;'><th>ID</th><th>Name</th><th>Schedules Found</th><th>Action</th></tr>";
    foreach ($availTable as $row) {
        echo "<tr>";
        echo "<td style='padding:5px;'>".$row['id']."</td>";
        echo "<td style='padding:5px;'>".htmlspecialchars($row['full_name'])."</td>";
        echo "<td style='padding:5px;'>".$row['days_count']."</td>";
        echo "<td style='padding:5px;'><a href='fix_live_db.php?fix_doc_id=".$row['id']."'>[Force Add Schedule]</a></td>";
        echo "</tr>";
    }
    echo "</table>";

    // Individual Doctor Fix
    if (isset($_GET['fix_doc_id'])) {
        $id = (int)$_GET['fix_doc_id'];
        echo "<br><b>Fixing Doctor ID $id...</b><br>";
        // Remove old if any
        $db->prepare("DELETE FROM doctor_availability WHERE doctor_id = ?")->execute([$id]);
        // Add new
        $stmt = $db->prepare("INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time, slot_duration) VALUES (?, ?, '09:00:00', '17:00:00', 30)");
        for ($day = 0; $day <= 6; $day++) { // All days Mon-Sun for testing
            $stmt->execute([$id, $day]);
        }
        echo "<span style='color:green'>Success! Day 0-6 added for Doctor $id.</span><br>";
        echo "<script>setTimeout(() => { window.location.href='fix_live_db.php'; }, 2000);</script>";
    }

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
    echo "1. <a href='fix_live_db.php?seed_admin=1' style='color:red; font-weight:bold;'>CLICK HERE TO RESET/CREATE ADMIN</a><br>";
    echo "2. <a href='fix_live_db.php?fix_availability=1' style='color:blue; font-weight:bold;'>CLICK HERE TO FIX ALL MISSING TIMES</a><br>";
    echo "3. <a href='patient/browse-doctors.php'>Go to Find Doctors</a><br>";
    echo "4. <a href='login.php'>Go to Login Page</a><br>";
    echo "5. <a href='index.php'>Go to Homepage</a><br>";
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
