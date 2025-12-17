<?php
// patient/my-appointments.php
define('PAGE_TITLE', 'My Appointments');
require_once __DIR__ . '/../includes/header.php';

Auth::requireRole('patient');
$patient_id = Auth::id(); // Need actual patient_id lookup like dashboard
$db = Database::getInstance();

$stmt = $db->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->execute([Auth::id()]);
$p = $stmt->fetch();
$real_patient_id = $p['id'];

// Filter
$filter = $_GET['filter'] ?? 'all';
$sql = "SELECT a.*, d.full_name as doctor_name, d.specialization, c.name as clinic_name 
        FROM appointments a 
        JOIN doctors d ON a.doctor_id = d.id 
        JOIN clinics c ON a.clinic_id = c.id
        WHERE a.patient_id = ?";

if ($filter === 'upcoming') {
    $sql .= " AND a.appointment_date >= CURDATE() AND a.status != 'cancelled'";
} elseif ($filter === 'past') {
    $sql .= " AND a.appointment_date < CURDATE()";
}

$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $db->prepare($sql);
$stmt->execute([$real_patient_id]);
$appointments = $stmt->fetchAll();
?>

<div class="bg-slate-50 min-h-screen py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <h1 class="text-3xl font-bold text-slate-900">My Appointments</h1>
            <div class="flex overflow-x-auto pb-2 md:pb-0 space-x-2 bg-white p-1 rounded-xl shadow-sm border border-slate-200 no-scrollbar">
                <a href="?filter=all" class="flex-shrink-0 px-4 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $filter === 'all' ? 'bg-primary-100 text-primary-700' : 'text-slate-600 hover:bg-slate-50'; ?>">All</a>
                <a href="?filter=upcoming" class="flex-shrink-0 px-4 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $filter === 'upcoming' ? 'bg-primary-100 text-primary-700' : 'text-slate-600 hover:bg-slate-50'; ?>">Upcoming</a>
                <a href="?filter=past" class="flex-shrink-0 px-4 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $filter === 'past' ? 'bg-primary-100 text-primary-700' : 'text-slate-600 hover:bg-slate-50'; ?>">Past</a>
            </div>
        </div>

        <?php if (empty($appointments)): ?>
            <div class="bg-white rounded-2xl p-16 text-center border border-slate-100">
                <p class="text-slate-500 text-lg">No appointments found matching your filter.</p>
                <a href="browse-doctors.php" class="inline-block mt-4 text-primary-600 font-medium hover:underline">Book a new one</a>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($appointments as $apt): 
                     $statusColors = [
                        'pending' => 'bg-amber-50 text-amber-700 border-amber-100',
                        'confirmed' => 'bg-green-50 text-green-700 border-green-100',
                        'completed' => 'bg-blue-50 text-blue-700 border-blue-100',
                        'cancelled' => 'bg-red-50 text-red-700 border-red-100',
                    ];
                    $statusClass = $statusColors[$apt['status']] ?? 'bg-slate-50 text-slate-700';
                ?>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex flex-col md:flex-row justify-between gap-6 transition-all hover:shadow-md">
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center justify-center w-16 h-16 bg-slate-50 rounded-xl border border-slate-100 text-slate-700 flex-shrink-0">
                            <span class="text-xs font-bold uppercase"><?php echo date('M', strtotime($apt['appointment_date'])); ?></span>
                            <span class="text-xl font-bold"><?php echo date('d', strtotime($apt['appointment_date'])); ?></span>
                        </div>
                        <div>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3 mb-1">
                                <h3 class="font-bold text-slate-900 text-lg"><?php echo htmlspecialchars($apt['doctor_name']); ?></h3>
                                <span class="self-start sm:self-auto px-2.5 py-0.5 rounded-full text-xs font-bold uppercase border <?php echo $statusClass; ?>">
                                    <?php echo $apt['status']; ?>
                                </span>
                            </div>
                            <p class="text-slate-500 mb-2"><?php echo htmlspecialchars($apt['specialization']); ?></p>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 text-sm text-slate-500">
                                <span class="flex items-center gap-1.5"><i class="fa-regular fa-clock"></i> <?php echo date('h:i A', strtotime($apt['appointment_time'])); ?></span>
                                <span class="flex items-center gap-1.5"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($apt['clinic_name']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 border-t md:border-t-0 pt-4 md:pt-0 border-slate-100">
                         <?php if ($apt['status'] === 'pending' || $apt['status'] === 'confirmed'): ?>
                             <!-- Add Actions Here -->
                             <a href="cancel-appointment.php?id=<?php echo $apt['id']; ?>" class="flex-1 md:flex-none text-center px-4 py-2 text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">Cancel</a>
                         <?php endif; ?>
                         <a href="appointment-confirmation.php?id=<?php echo $apt['id']; ?>" class="flex-1 md:flex-none text-center px-4 py-2 text-sm font-medium text-slate-600 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors">Details</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
