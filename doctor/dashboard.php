<?php
// doctor/dashboard.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Session.php';

Auth::requireRole('doctor');

$db = Database::getInstance();

// Get doctor profile
$stmt = $db->prepare("SELECT * FROM doctors WHERE user_id = ?");
$stmt->execute([Session::get('user_id')]);
$doctor = $stmt->fetch();
$doctor_id = $doctor['id'];

// Check approval status only if column exists
$hasRegistrationStatus = $db->query("SHOW COLUMNS FROM doctors LIKE 'registration_status'")->fetch();

if ($hasRegistrationStatus) {
    if ($doctor['registration_status'] === 'pending') {
        define('PAGE_TITLE', 'Pending Approval');
        require_once __DIR__ . '/../includes/header.php';
        echo '<div class="max-w-7xl mx-auto px-4 py-12"><div class="bg-yellow-50 border-l-4 border-yellow-400 p-8 rounded-2xl text-center shadow-sm">
                <div class="w-16 h-16 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl"><i class="fa-solid fa-clock-rotate-left"></i></div>
                <h2 class="text-2xl font-bold text-yellow-800 mb-2">Account Pending Approval</h2>
                <p class="text-yellow-700 max-w-md mx-auto">Your medical credentials are currently being verified by our administration. You will have full access to your dashboard once approved.</p>
                <div class="mt-8"><a href="../logout.php" class="text-yellow-800 font-bold hover:underline">Sign Out</a></div>
              </div></div>';
        require_once __DIR__ . '/../includes/footer.php';
        exit;
    } elseif ($doctor['registration_status'] === 'rejected') {
        define('PAGE_TITLE', 'Registration Rejected');
        require_once __DIR__ . '/../includes/header.php';
        echo '<div class="max-w-7xl mx-auto px-4 py-12"><div class="bg-red-50 border-l-4 border-red-400 p-8 rounded-2xl text-center shadow-sm">
                <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl"><i class="fa-solid fa-circle-xmark"></i></div>
                <h2 class="text-2xl font-bold text-red-800 mb-2">Registration Rejected</h2>
                <p class="text-red-700 max-w-md mx-auto">Unfortunately, your registration request has been declined. Please contact support for more information.</p>
                <div class="mt-8"><a href="../logout.php" class="text-red-800 font-bold hover:underline">Sign Out</a></div>
              </div></div>';
        require_once __DIR__ . '/../includes/footer.php';
        exit;
    }
}

// Handle Appointment Actions - MUST BE BEFORE HEADER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appt_action']) && isset($_POST['appointment_id'])) {
    $appt_id = $_POST['appointment_id'];
    $new_status = ($_POST['appt_action'] === 'confirm') ? 'confirmed' : 'cancelled';
    
    // Security check: ensure this appointment belongs to this doctor
    $stmt = $db->prepare("UPDATE appointments SET status = ? WHERE id = ? AND doctor_id = ?");
    $stmt->execute([$new_status, $appt_id, $doctor_id]);
    
    Session::setFlash('success', 'Appointment ' . $new_status . ' successfully.');
    header("Location: dashboard.php");
    exit;
}

define('PAGE_TITLE', 'Doctor Dashboard');
require_once __DIR__ . '/../includes/header.php';

// Stats
$stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status = 'pending'");
$stmt->execute([$doctor_id]);
$pending_appts = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = CURDATE()");
$stmt->execute([$doctor_id]);
$today_appts = $stmt->fetchColumn();

?>

<div class="py-12 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <header class="mb-12">
            <h1 class="text-3xl font-bold text-slate-900">Welcome, <?php echo htmlspecialchars($doctor['full_name']); ?></h1>
            <p class="text-slate-500">Here's what's happening with your practice today.</p>
        </header>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-xl">
                        <i class="fa-solid fa-calendar-day"></i>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Today's Appointments</p>
                        <h3 class="text-2xl font-bold text-slate-900"><?php echo $today_appts; ?></h3>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-xl flex items-center justify-center text-xl">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Pending Requests</p>
                        <h3 class="text-2xl font-bold text-slate-900"><?php echo $pending_appts; ?></h3>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-green-100 text-green-600 rounded-xl flex items-center justify-center text-xl">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Total Earnings Estimation</p>
                        <?php
                        $stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status IN ('confirmed', 'completed')");
                        $stmt->execute([$doctor_id]);
                        $earned_count = $stmt->fetchColumn();
                        ?>
                        <h3 class="text-2xl font-bold text-slate-900">TZS <?php echo number_format($earned_count * $doctor['consultation_fee'], 0); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-slate-100">
            <div class="p-8">
                <h2 class="text-xl font-bold text-slate-900 mb-6">Recent Appointments</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100">
                                <th class="py-4 font-semibold text-slate-700">Patient</th>
                                <th class="py-4 font-semibold text-slate-700">Date/Time</th>
                                <th class="py-4 font-semibold text-slate-700">Status</th>
                                <th class="py-4 font-semibold text-slate-700">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php
                            $stmt = $db->prepare("SELECT a.*, p.full_name as patient_name 
                                                 FROM appointments a 
                                                 JOIN patients p ON a.patient_id = p.id 
                                                 WHERE a.doctor_id = ? 
                                                 ORDER BY a.appointment_date DESC, a.appointment_time DESC 
                                                 LIMIT 5");
                            $stmt->execute([$doctor_id]);
                            $appointments = $stmt->fetchAll();
                            
                            if (empty($appointments)): ?>
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-slate-400">No appointments found.</td>
                                </tr>
                            <?php endif;
                            
                            foreach ($appointments as $appt): ?>
                                <tr>
                                    <td class="py-4">
                                        <div class="font-medium text-slate-900"><?php echo htmlspecialchars($appt['patient_name']); ?></div>
                                    </td>
                                    <td class="py-4">
                                        <div class="text-sm text-slate-600"><?php echo date('M d, Y', strtotime($appt['appointment_date'])); ?></div>
                                        <div class="text-xs text-slate-400"><?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></div>
                                    </td>
                                    <td class="py-4">
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold 
                                            <?php echo $appt['status'] === 'confirmed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'; ?>">
                                            <?php echo ucfirst($appt['status']); ?>
                                        </span>
                                    </td>
                                    <td class="py-4">
                                        <?php if ($appt['status'] === 'pending'): ?>
                                            <form method="POST" class="flex gap-2">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
                                                <button type="submit" name="appt_action" value="confirm" class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-xs font-bold hover:bg-emerald-200 transition-colors">
                                                    Approve
                                                </button>
                                                <button type="submit" name="appt_action" value="cancel" class="px-3 py-1 bg-red-100 text-red-700 rounded-lg text-xs font-bold hover:bg-red-200 transition-colors">
                                                    Cancel
                                                </button>
                                            </form>
                                        <?php elseif ($appt['status'] === 'confirmed'): ?>
                                             <form method="POST">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
                                                <button type="submit" name="appt_action" value="cancel" class="text-red-500 hover:text-red-700 text-xs font-bold transition-colors">
                                                    Cancel Appointment
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-slate-400 text-xs italic">No actions available</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
