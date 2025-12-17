<?php
// patient/browse-doctors.php
define('PAGE_TITLE', 'Find a Doctor');
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance();

// Search Filters
$search = $_GET['search'] ?? '';
$location = $_GET['location'] ?? '';
$specialization = $_GET['specialization'] ?? '';

// Build Query
$query = "SELECT d.*, c.name as clinic_name, a.city, a.street_address 
          FROM doctors d
          JOIN clinics c ON d.clinic_id = c.id
          JOIN addresses a ON c.address_id = a.id
          WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (d.full_name LIKE ? OR d.specialization LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($location) {
    $query .= " AND a.city LIKE ?";
    $params[] = "%$location%";
}
if ($specialization) {
    $query .= " AND d.specialization = ?";
    $params[] = $specialization;
}

$stmt = $db->prepare($query);
$stmt->execute($params);
$doctors = $stmt->fetchAll();

// Get unique specializations for filter dropdown
$specsObj = $db->query("SELECT DISTINCT specialization FROM doctors ORDER BY specialization")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="bg-slate-50 min-h-screen py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h1 class="text-3xl font-bold text-slate-900 mb-4">Find the Right Doctor</h1>
            <p class="text-lg text-slate-600 max-w-2xl mx-auto">Search through our verified list of specialists and book an appointment today.</p>
        </div>

        <!-- Search Bar -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-12 transform -translate-y-4 border border-slate-100">
            <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-3.5 text-slate-400"></i>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Doctor name or keyword" class="w-full pl-11 rounded-xl border-slate-300 focus:border-primary-500 focus:ring-primary-500 py-3">
                </div>
                <div class="relative">
                    <i class="fa-solid fa-location-dot absolute left-4 top-3.5 text-slate-400"></i>
                    <input type="text" name="location" value="<?php echo htmlspecialchars($location); ?>" placeholder="City or Location" class="w-full pl-11 rounded-xl border-slate-300 focus:border-primary-500 focus:ring-primary-500 py-3">
                </div>
                <div class="relative">
                    <i class="fa-solid fa-stethoscope absolute left-4 top-3.5 text-slate-400"></i>
                    <select name="specialization" class="w-full pl-11 rounded-xl border-slate-300 focus:border-primary-500 focus:ring-primary-500 py-3 appearance-none bg-none">
                        <option value="">All Specializations</option>
                        <?php foreach($specsObj as $spec): ?>
                            <option value="<?php echo htmlspecialchars($spec); ?>" <?php echo $specialization === $spec ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($spec); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-bold py-3 rounded-xl transition-colors shadow-lg shadow-primary-500/30 w-full md:w-auto">
                    Search
                </button>
            </form>
        </div>

        <!-- Doctors Grid -->
        <?php if (empty($doctors)): ?>
            <div class="text-center py-12">
                <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-400 text-3xl">
                    <i class="fa-solid fa-user-doctor"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-900">No doctors found</h3>
                <p class="text-slate-500 mt-2">Try adjusting your filters.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($doctors as $doc): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden hover:shadow-xl transition-all duration-300 group">
                    <div class="p-6 flex flex-col h-full">
                        <div class="flex items-start gap-4 mb-4">
                            <img src="<?php echo htmlspecialchars($doc['profile_image'] ?: 'https://via.placeholder.com/150'); ?>" alt="Doctor" class="w-20 h-20 rounded-2xl object-cover shadow-sm">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 group-hover:text-primary-600 transition-colors"><?php echo htmlspecialchars($doc['full_name']); ?></h3>
                                <p class="text-primary-600 font-medium text-sm"><?php echo htmlspecialchars($doc['specialization']); ?></p>
                                <div class="flex items-center gap-1 mt-1 text-xs text-slate-500">
                                    <i class="fa-solid fa-briefcase"></i> <?php echo $doc['experience_years']; ?>+ Years Exp.
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-3 mb-6 flex-grow">
                            <p class="text-sm text-slate-600 line-clamp-2"><?php echo htmlspecialchars($doc['bio']); ?></p>
                            
                            <div class="flex items-center gap-2 text-sm text-slate-500">
                                <i class="fa-solid fa-hospital text-slate-400"></i>
                                <span><?php echo htmlspecialchars($doc['clinic_name']); ?></span>
                            </div>
                            <div class="flex items-center gap-2 text-sm text-slate-500">
                                <i class="fa-solid fa-location-dot text-slate-400"></i>
                                <span><?php echo htmlspecialchars($doc['city']); ?></span>
                            </div>
                        </div>
                        
                        <div class="mt-auto pt-6 border-t border-slate-100 flex items-center justify-between">
                            <div>
                                <p class="text-xs text-slate-400">Consultation Fee</p>
                                <p class="text-lg font-bold text-slate-900">$<?php echo number_format($doc['consultation_fee'], 0); ?></p>
                            </div>
                            <a href="book-appointment.php?doctor_id=<?php echo $doc['id']; ?>" class="bg-slate-900 hover:bg-primary-600 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                                Book Now
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
