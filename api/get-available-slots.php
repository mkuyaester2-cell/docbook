<?php
// api/get-available-slots.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

$doctor_id = $_GET['doctor_id'] ?? null;
$date = $_GET['date'] ?? null;

if (!$doctor_id || !$date) {
    echo json_encode([]);
    exit;
}

$db = Database::getInstance();

// 1. Get Doctor's Availability Schedule for the specific day of week
$dayOfWeek = date('w', strtotime($date)); // 0 = Sunday, 1 = Monday...
$stmt = $db->prepare("SELECT * FROM doctor_availability WHERE doctor_id = ? AND day_of_week = ? AND is_active = 1");
$stmt->execute([$doctor_id, $dayOfWeek]);
$avail = $stmt->fetch();

if (!$avail) {
    echo json_encode([]); // Doctor not working this day
    exit;
}

// 2. Get Existing Appointments
$stmt = $db->prepare("SELECT appointment_time FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status != 'cancelled'");
$stmt->execute([$doctor_id, $date]);
$bookedCols = $stmt->fetchAll(PDO::FETCH_COLUMN); // ['09:00:00', '10:30:00']

// 3. Generate Slots
$slots = [];
$startTime = strtotime($avail['start_time']);
$endTime = strtotime($avail['end_time']);
$duration = $avail['slot_duration'] * 60; // Convert to seconds

$current = $startTime;
while (($current + $duration) <= $endTime) {
    $timeStr = date('H:i:00', $current);
    
    // Check if booked
    if (!in_array($timeStr, $bookedCols)) {
        // Format for display
        $slots[] = [
            'value' => $timeStr,
            'display' => date('h:i A', $current)
        ];
    }
    
    $current += $duration;
}

echo json_encode($slots);
?>
