<?php
// patient/appointment-confirmation.php
define('PAGE_TITLE', 'Booking Confirmed');
require_once __DIR__ . '/../includes/header.php';

Auth::requireRole('patient');

$appointment_id = $_GET['id'] ?? null;
if (!$appointment_id) {
    header("Location: dashboard.php");
    exit;
}

$db = Database::getInstance();
$stmt = $db->prepare("
    SELECT a.*, d.full_name as doctor_name, d.specialization, c.name as clinic_name, 
           c.phone as clinic_phone, addrs.street_address, addrs.city 
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN clinics c ON a.clinic_id = c.id
    JOIN addresses addrs ON c.address_id = addrs.id
    WHERE a.id = ? AND a.patient_id = ?
");

// Get Patient ID first
$user_id = Auth::id();
$stmt_p = $db->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt_p->execute([$user_id]);
$patient = $stmt_p->fetch();

$stmt->execute([$appointment_id, $patient['id']]);
$apt = $stmt->fetch();

if (!$apt) {
    echo "Appointment not found or access denied.";
    exit;
}
?>

<div class="bg-slate-50 min-h-screen py-12 px-4 sm:px-6 lg:px-8 flex items-center justify-center">
    <div class="max-w-xl w-full">
        <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-slate-100 relative">
            <!-- Success Header -->
            <div class="bg-green-600 p-8 text-center relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-full opacity-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-4 animate-bounce">
                    <i class="fa-solid fa-check text-3xl text-green-600"></i>
                </div>
                <h1 class="text-2xl font-bold text-white mb-2">Appointment Confirmed!</h1>
                <p class="text-green-100">Booking Reference #<?php echo str_pad($apt['id'], 6, '0', STR_PAD_LEFT); ?></p>
            </div>

            <div class="p-8">
                <!-- Appointment Details -->
                <div class="space-y-6">
                    <div class="flex items-start gap-4 pb-6 border-b border-slate-100">
                        <div class="bg-blue-50 p-3 rounded-xl text-primary-600">
                            <i class="fa-solid fa-user-doctor text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500">Doctor</p>
                            <h3 class="text-lg font-bold text-slate-900"><?php echo htmlspecialchars($apt['doctor_name']); ?></h3>
                            <p class="text-primary-600"><?php echo htmlspecialchars($apt['specialization']); ?></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6 pb-6 border-b border-slate-100">
                        <div>
                            <p class="text-sm text-slate-500 mb-1">Date</p>
                            <div class="flex items-center gap-2 font-semibold text-slate-900">
                                <i class="fa-regular fa-calendar text-slate-400"></i>
                                <?php echo date('D, M d, Y', strtotime($apt['appointment_date'])); ?>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 mb-1">Time</p>
                            <div class="flex items-center gap-2 font-semibold text-slate-900">
                                <i class="fa-regular fa-clock text-slate-400"></i>
                                <?php echo date('h:i A', strtotime($apt['appointment_time'])); ?>
                            </div>
                        </div>
                    </div>

                    <div class="pb-6 border-b border-slate-100">
                        <p class="text-sm text-slate-500 mb-2">Location</p>
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-location-dot mt-1 text-slate-400"></i>
                            <div>
                                <p class="font-semibold text-slate-900"><?php echo htmlspecialchars($apt['clinic_name']); ?></p>
                                <p class="text-slate-600"><?php echo htmlspecialchars($apt['street_address'] . ', ' . $apt['city']); ?></p>
                                <a href="https://maps.google.com/?q=<?php echo urlencode($apt['street_address'] . ' ' . $apt['city']); ?>" target="_blank" class="text-primary-600 text-sm hover:underline mt-1 inline-block">
                                    Get Directions
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <a href="dashboard.php" class="flex items-center justify-center px-4 py-3 border border-slate-200 rounded-xl text-slate-700 font-medium hover:bg-slate-50 transition-colors">
                        Go to Dashboard
                    </a>
                    <button onclick="window.print()" class="flex items-center justify-center px-4 py-3 bg-slate-900 text-white rounded-xl font-medium hover:bg-slate-800 transition-colors shadow-lg">
                        <i class="fa-solid fa-print mr-2"></i> Print Details
                    </button>
                </div>
                
                <div class="mt-6 text-center">
                    <p class="text-xs text-slate-400">A confirmation email has been sent to your registered email address.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
