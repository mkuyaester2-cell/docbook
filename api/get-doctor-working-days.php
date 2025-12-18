<?php
// api/get-doctor-working-days.php
header('Content-Type: application/json');
header("Cache-Control: no-cache, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

$doctor_id = $_GET['doctor_id'] ?? null;

if (!$doctor_id) {
    echo json_encode([]);
    exit;
}

$db = Database::getInstance();

// Fetch active working days (0=Sunday, 1=Monday...)
$stmt = $db->prepare("SELECT DISTINCT day_of_week FROM doctor_availability WHERE doctor_id = ? AND is_active = 1");
$stmt->execute([$doctor_id]);
$days = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Convert strings/integers to integers
$days = array_map('intval', $days);

echo json_encode($days);
?>
