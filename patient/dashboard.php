<?php
// patient/dashboard.php
define('PAGE_TITLE', 'My Dashboard');
require_once __DIR__ . '/../includes/header.php';

// Ensure user is logged in as patient
Auth::requireRole('patient');

$user_id = Auth::id();
$db = Database::getInstance();

// Fetch Patient ID
$stmt = $db->prepare("SELECT id, full_name FROM patients WHERE user_id = ?");
$stmt->execute([$user_id]);
$patient = $stmt->fetch();
$patient_id = $patient['id'];

// Stats
$stmt = $db->prepare("SELECT count(*) as total, 
    SUM(CASE WHEN appointment_date >= CURDATE() AND status != 'cancelled' THEN 1 ELSE 0 END) as upcoming,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM appointments WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$stats = $stmt->fetch();

// Recent Appointments
$stmt = $db->prepare("
    SELECT a.*, d.full_name as doctor_name, d.specialization, c.name as clinic_name, addr.city
    FROM appointments a 
    JOIN doctors d ON a.doctor_id = d.id 
    JOIN clinics c ON a.clinic_id = c.id
    JOIN addresses addr ON c.address_id = addr.id
    WHERE a.patient_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 5
");
$stmt->execute([$patient_id]);
$appointments = $stmt->fetchAll();
?>

<div class="bg-slate-50 min-h-screen py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Welcome Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">Hello, <?php echo htmlspecialchars($patient['full_name']); ?>! 👋</h1>
                <p class="text-slate-500">Manage your health and upcoming appointments.</p>
            </div>
            <a href="browse-doctors.php" class="inline-flex items-center justify-center px-6 py-3 border border-transparent rounded-xl shadow-sm text-base font-medium text-white bg-primary-600 hover:bg-primary-700 transition-all shadow-lg hover:shadow-primary-500/30">
                <i class="fa-solid fa-plus mr-2"></i> Book New Appointment
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-xl">
                    <i class="fa-regular fa-calendar-check"></i>
                </div>
                <div>
                    <p class="text-slate-500 text-sm font-medium">Upcoming</p>
                    <p class="text-2xl font-bold text-slate-900"><?php echo $stats['upcoming']; ?></p>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4">
                <div class="w-12 h-12 bg-green-50 text-green-600 rounded-xl flex items-center justify-center text-xl">
                    <i class="fa-solid fa-check-double"></i>
                </div>
                <div>
                    <p class="text-slate-500 text-sm font-medium">Completed</p>
                    <p class="text-2xl font-bold text-slate-900"><?php echo $stats['completed']; ?></p>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center text-xl">
                    <i class="fa-solid fa-user-doctor"></i>
                </div>
                <div>
                    <p class="text-slate-500 text-sm font-medium">Total Visits</p>
                    <p class="text-2xl font-bold text-slate-900"><?php echo $stats['total']; ?></p>
                </div>
            </div>
        </div>

        <!-- Appointments List -->
        <h2 class="text-xl font-bold text-slate-900 mb-6">Recent Appointments</h2>
        
        <?php if (empty($appointments)): ?>
            <div class="bg-white rounded-2xl p-12 text-center shadow-sm border border-slate-100">
                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300 text-3xl">
                    <i class="fa-solid fa-clipboard-list"></i>
                </div>
                <h3 class="text-lg font-medium text-slate-900 mb-2">No appointments yet</h3>
                <p class="text-slate-500 mb-6 max-w-sm mx-auto">Get started by finding a specialist for your needs.</p>
                <a href="browse-doctors.php" class="text-primary-600 font-medium hover:text-primary-700">Find a Doctor &rarr;</a>
            </div>
        <?php else: ?>
            <div class="grid gap-4">
                <?php foreach ($appointments as $apt): 
                    $statusColors = [
                        'pending' => 'bg-amber-100 text-amber-800',
                        'confirmed' => 'bg-green-100 text-green-800',
                        'completed' => 'bg-blue-100 text-blue-800',
                        'cancelled' => 'bg-red-100 text-red-800',
                    ];
                    $statusClass = $statusColors[$apt['status']] ?? 'bg-slate-100 text-slate-800';
                ?>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center overflow-hidden flex-shrink-0">
                                <i class="fa-solid fa-user-doctor text-slate-400"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 text-lg"><?php echo htmlspecialchars($apt['doctor_name']); ?></h3>
                                <p class="text-slate-500"><?php echo htmlspecialchars($apt['specialization']); ?> • <?php echo htmlspecialchars($apt['clinic_name']); ?></p>
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mt-2 text-sm text-slate-500">
                                    <span class="flex items-center gap-1">
                                        <i class="fa-regular fa-calendar"></i> <?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?>
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <i class="fa-regular fa-clock"></i> <?php echo date('h:i A', strtotime($apt['appointment_time'])); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between md:justify-end gap-4 border-t md:border-t-0 pt-4 md:pt-0 border-slate-100">
                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide <?php echo $statusClass; ?>">
                                <?php echo ucfirst($apt['status']); ?>
                            </span>
                            <div class="flex items-center gap-4">
                                <?php if ($apt['status'] === 'pending' || $apt['status'] === 'confirmed'): ?>
                                    <a href="reschedule.php?id=<?php echo $apt['id']; ?>" class="text-slate-400 hover:text-primary-600 transition-colors" title="Reschedule">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <button onclick="cancelAppointment(<?php echo $apt['id']; ?>)" class="text-slate-400 hover:text-red-600 transition-colors" title="Cancel">
                                        <i class="fa-solid fa-ban"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if ($apt['status'] === 'completed'): ?>
                                    <a href="view-prescription.php?id=<?php echo $apt['id']; ?>" class="text-primary-600 hover:text-primary-700 text-sm font-medium">
                                        View Prescription
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-6">
                 <a href="my-appointments.php" class="text-primary-600 font-medium hover:text-primary-700">View All History &rarr;</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function cancelAppointment(id) {
    if(confirm('Are you sure you want to cancel this appointment?')) {
        // Redirect to cancel script or use AJAX (using simple link for MVP)
        window.location.href = `cancel-appointment.php?id=${id}`;
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
