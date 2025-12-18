<?php
// fix_live_db.php
require_once __DIR__ . '/config/Database.php';

try {
    $db = Database::getInstance();
    
    // 1. Check if registration_status column exists
    $stmt = $db->query("SHOW COLUMNS FROM doctors LIKE 'registration_status'");
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        echo "Adding 'registration_status' column...<br>";
        $db->exec("ALTER TABLE doctors ADD COLUMN registration_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER consultation_fee");
        
        echo "Updating existing doctors to 'approved'...<br>";
        $db->exec("UPDATE doctors SET registration_status = 'approved'");
        
        echo "<b>Database updated successfully!</b>";
    } else {
        echo "<b>The 'registration_status' column already exists. Nothing to do.</b>";
    }
    
    echo "<br><br><a href='index.php'>Go to Homepage</a>";
    
} catch (Exception $e) {
    echo "<b>Error:</b> " . $e->getMessage();
}
?>
