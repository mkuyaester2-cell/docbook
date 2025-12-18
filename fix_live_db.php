<?php
// fix_live_db.php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Database.php';

echo "<h2>DocBook Database Self-Healing Script</h2>";
echo "Attempting to connect to database: <b>" . DB_NAME . "</b>...<br>";

try {
    $db = Database::getInstance();
    echo "<span style='color:green'>Connection Successful!</span><br><br>";
    
    // 1. Check if registration_status column exists in doctors table
    echo "Checking 'doctors' table structure...<br>";
    $stmt = $db->query("SHOW COLUMNS FROM doctors LIKE 'registration_status'");
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        echo "<span style='color:orange'>Column 'registration_status' not found. Adding it now...</span><br>";
        
        // Add the column
        $db->exec("ALTER TABLE doctors ADD COLUMN registration_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER consultation_fee");
        echo "Successfully added 'registration_status' column.<br>";
        
        // Update existing doctors to 'approved' so they don't get hidden
        echo "Marking existing doctors as 'approved'...<br>";
        $db->exec("UPDATE doctors SET registration_status = 'approved'");
        
        echo "<br><b style='color:green'>DATABASE UPDATED SUCCESSFULLY!</b>";
    } else {
        echo "<b style='color:blue'>The 'registration_status' column already exists. No action needed.</b>";
    }
    
    echo "<br><br><div style='padding:20px; background:#f0f9ff; border:1px solid #bae6fd; border-radius:8px;'>";
    echo "<b>Next Steps:</b><br>";
    echo "1. Go to <a href='patient/browse-doctors.php'>Find Doctors</a> - It should work now.<br>";
    echo "2. Go to <a href='login.php'>Login</a> - Try logging in as Admin.<br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<br><b style='color:red'>CRITICAL ERROR:</b><br>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "Please ensure your <b>config/Database.php</b> has the correct credentials for your live server.";
}

echo "<br><br><a href='index.php'>&larr; Back to Homepage</a>";
?>
