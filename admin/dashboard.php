<?php
// admin/dashboard.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Session.php';

Auth::requireRole('admin');

define('PAGE_TITLE', 'Admin Dashboard');
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance();

// Get Admin info
$stmt = $db->prepare("SELECT full_name FROM admins WHERE user_id = ?");
$stmt->execute([Session::get('user_id')]);
$admin = $stmt->fetch();

// Handle Approval/Rejection
if (isset($_POST['action']) && isset($_POST['doctor_id'])) {
    $action = $_POST['action'];
    $doctor_id = $_POST['doctor_id'];
    $status = ($action === 'approve') ? 'approved' : 'rejected';
    
    $stmt = $db->prepare("UPDATE doctors SET registration_status = ? WHERE id = ?");
    $stmt->execute([$status, $doctor_id]);
    Session::setFlash('success', 'Doctor registration ' . $status . ' successfully.');
    header("Location: dashboard.php");
    exit;
}

// Stats
$total_doctors = $db->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
$pending_doctors = $db->query("SELECT COUNT(*) FROM doctors WHERE registration_status = 'pending'")->fetchColumn();
$total_patients = $db->query("SELECT COUNT(*) FROM patients")->fetchColumn();
$total_appointments = $db->query("SELECT COUNT(*) FROM appointments")->fetchColumn();

// Fetch Pending Doctors
$pending_list = $db->query("SELECT d.*, u.email FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.registration_status = 'pending' ORDER BY d.created_at DESC")->fetchAll();

// Fetch Recent Appointments with Doctor and Patient info
$appointments = $db->query("SELECT a.*, p.full_name as patient_name, d.full_name as doctor_name 
                           FROM appointments a 
                           JOIN patients p ON a.patient_id = p.id 
                           JOIN doctors d ON a.doctor_id = d.id 
                           ORDER BY a.created_at DESC LIMIT 10")->fetchAll();
?>

<div class="py-12 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <header class="mb-12 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">Welcome, <?php echo htmlspecialchars($admin['full_name']); ?></h1>
                <p class="text-slate-500">Manage doctors, patients and monitor system activity.</p>
            </div>
            <div class="bg-primary-600/10 text-primary-700 px-4 py-2 rounded-xl font-bold border border-primary-200">
                System Active
            </div>
        </header>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 group hover:border-primary-300 transition-all">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-user-md"></i>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 font-medium">Total Doctors</p>
                        <h3 class="text-2xl font-bold text-slate-900"><?php echo $total_doctors; ?></h3>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 group hover:border-yellow-300 transition-all">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-xl flex items-center justify-center text-xl group-hover:scale-110 transition-transform relative">
                        <i class="fa-solid fa-clock"></i>
                        <?php if ($pending_doctors > 0): ?>
                            <span class="absolute -top-1 -right-1 flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-yellow-500"></span>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 font-medium">Pending Approval</p>
                        <h3 class="text-2xl font-bold text-slate-900"><?php echo $pending_doctors; ?></h3>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 group hover:border-emerald-300 transition-all">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 font-medium">Total Patients</p>
                        <h3 class="text-2xl font-bold text-slate-900"><?php echo $total_patients; ?></h3>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 group hover:border-purple-300 transition-all">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 font-medium">Appointments</p>
                        <h3 class="text-2xl font-bold text-slate-900"><?php echo $total_appointments; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Pending Requests -->
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-slate-100">
                <div class="p-8">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-slate-900">Doctor Requests</h2>
                        <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs font-bold"><?php echo count($pending_list); ?> Pending</span>
                    </div>
                    
                    <div class="space-y-4">
                        <?php if (empty($pending_list)): ?>
                            <div class="text-center py-8">
                                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-3 text-slate-300">
                                    <i class="fa-solid fa-check-double text-2xl"></i>
                                </div>
                                <p class="text-slate-500">No pending registrations.</p>
                            </div>
                        <?php endif; ?>

                        <?php foreach ($pending_list as $pending): ?>
                            <div class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 border border-slate-100 hover:border-primary-200 transition-colors">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-primary-600 font-bold border border-slate-200 shadow-sm">
                                        <?php echo strtoupper(substr($pending['full_name'], 4, 1)); ?>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-900"><?php echo htmlspecialchars($pending['full_name']); ?></h4>
                                        <p class="text-xs text-slate-500"><?php echo htmlspecialchars($pending['specialization']); ?> • <?php echo htmlspecialchars($pending['email']); ?></p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="doctor_id" value="<?php echo $pending['id']; ?>">
                                        <button type="submit" name="action" value="approve" class="p-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg transition-colors shadow-sm" title="Approve">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                        <button type="submit" name="action" value="reject" class="p-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors shadow-sm" title="Reject">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Appointments -->
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-slate-100">
                <div class="p-8">
                    <h2 class="text-xl font-bold text-slate-900 mb-6">Recent Appointments</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-slate-400 text-xs uppercase tracking-wider border-b border-slate-100">
                                    <th class="pb-4 font-semibold">Patient / Doctor</th>
                                    <th class="pb-4 font-semibold text-center">Date</th>
                                    <th class="pb-4 font-semibold text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php foreach ($appointments as $appt): ?>
                                    <tr>
                                        <td class="py-4">
                                            <div class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($appt['patient_name']); ?></div>
                                            <div class="text-[10px] text-slate-400 flex items-center gap-1">
                                                <i class="fa-solid fa-arrow-right text-[8px]"></i>
                                                <?php echo htmlspecialchars($appt['doctor_name']); ?>
                                            </div>
                                        </td>
                                        <td class="py-4 text-center">
                                            <div class="text-xs text-slate-600 font-medium"><?php echo date('M d, Y', strtotime($appt['appointment_date'])); ?></div>
                                            <div class="text-[10px] text-slate-400"><?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></div>
                                        </td>
                                        <td class="py-4 text-right">
                                            <span class="px-2 py-1 rounded-md text-[10px] font-bold uppercase
                                                <?php 
                                                if ($appt['status'] === 'confirmed') echo 'bg-emerald-100 text-emerald-700';
                                                elseif ($appt['status'] === 'pending') echo 'bg-yellow-100 text-yellow-700';
                                                elseif ($appt['status'] === 'cancelled') echo 'bg-red-100 text-red-700';
                                                else echo 'bg-slate-100 text-slate-700';
                                                ?>">
                                                <?php echo $appt['status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-12">
            <!-- All Doctors -->
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-slate-100">
                <div class="p-8">
                    <h2 class="text-xl font-bold text-slate-900 mb-6">Our Doctors</h2>
                    <div class="space-y-4">
                        <?php
                        $all_docs = $db->query("SELECT * FROM doctors ORDER BY full_name ASC LIMIT 5")->fetchAll();
                        foreach ($all_docs as $d): ?>
                            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-primary-100 text-primary-600 rounded-full flex items-center justify-center font-bold text-xs">
                                        <?php echo strtoupper(substr($d['full_name'], 4, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($d['full_name']); ?></div>
                                        <div class="text-[10px] text-slate-500"><?php echo htmlspecialchars($d['specialization']); ?></div>
                                    </div>
                                </div>
                                <span class="px-2 py-1 rounded text-[10px] font-bold uppercase <?php echo $d['registration_status'] === 'approved' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'; ?>">
                                    <?php echo $d['registration_status']; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- All Patients -->
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-slate-100">
                <div class="p-8">
                    <h2 class="text-xl font-bold text-slate-900 mb-6">Registered Patients</h2>
                    <div class="space-y-4">
                        <?php
                        $all_patients = $db->query("SELECT * FROM patients ORDER BY created_at DESC LIMIT 5")->fetchAll();
                        foreach ($all_patients as $p): ?>
                            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-slate-200 text-slate-600 rounded-full flex items-center justify-center font-bold text-xs">
                                        <?php echo strtoupper(substr($p['full_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($p['full_name']); ?></div>
                                        <div class="text-[10px] text-slate-500"><?php echo htmlspecialchars($p['phone']); ?></div>
                                    </div>
                                </div>
                                <div class="text-[10px] text-slate-400">Joined <?php echo date('M j', strtotime($p['created_at'])); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
