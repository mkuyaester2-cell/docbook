<?php
// patient/cancel-appointment.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Session.php';

Auth::requireRole('patient');

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: dashboard.php");
    exit;
}

$db = Database::getInstance();

// Verify ownership
$user_id = Auth::id();
$stmt_p = $db->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt_p->execute([$user_id]);
$patient = $stmt_p->fetch();

$stmt = $db->prepare("SELECT id, status FROM appointments WHERE id = ? AND patient_id = ?");
$stmt->execute([$id, $patient['id']]);
$apt = $stmt->fetch();

if ($apt && $apt['status'] !== 'completed' && $apt['status'] !== 'cancelled') {
    $update = $db->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?");
    $update->execute([$id]);
    Session::setFlash('success', 'Appointment cancelled successfully.');
} else {
    Session::setFlash('error', 'Cannot cancel this appointment.');
}

header("Location: dashboard.php");
exit;
?>
