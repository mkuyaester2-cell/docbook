<?php
// doctor/register.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Session.php';

// Redirect if already logged in
if (Auth::isLoggedIn()) {
    header("Location: " . APP_URL);
    exit;
}

$error = '';
$success = '';

$db = Database::getInstance();

// Fetch clinics for the dropdown
$clinics = $db->query("SELECT id, name FROM clinics")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $_POST['fullname'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $specialization = $_POST['specialization'] ?? '';
    $qualification = $_POST['qualification'] ?? '';
    $experience = $_POST['experience'] ?? 0;
    $consultation_fee = $_POST['consultation_fee'] ?? 0;
    $clinic_id = $_POST['clinic_id'] ?? '';
    $bio = $_POST['bio'] ?? '';

    if (empty($fullname) || empty($email) || empty($password) || empty($clinic_id)) {
        $error = 'Please fill in all required fields.';
    } else {
        $auth = new Auth();
        try {
            $db->beginTransaction();
            
            // 1. Create User Account
            $user_id = $auth->register($email, $password, 'doctor');
            
            if ($user_id === false) {
                throw new Exception("Email already registered. Please login.");
            }
            
            // 2. Create Doctor Profile (Status: Pending)
            $hasRegistrationStatus = $db->query("SHOW COLUMNS FROM doctors LIKE 'registration_status'")->fetch();
            
            if ($hasRegistrationStatus) {
                $stmt = $db->prepare("INSERT INTO doctors (user_id, full_name, specialization, qualification, experience_years, phone, bio, clinic_id, consultation_fee, registration_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([$user_id, $fullname, $specialization, $qualification, $experience, $phone, $bio, $clinic_id, $consultation_fee]);
            } else {
                // If column doesn't exist, insert without it (backwards compatibility)
                $stmt = $db->prepare("INSERT INTO doctors (user_id, full_name, specialization, qualification, experience_years, phone, bio, clinic_id, consultation_fee) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $fullname, $specialization, $qualification, $experience, $phone, $bio, $clinic_id, $consultation_fee]);
            }
            
            $db->commit();
            
            $success = 'Successfully registered! Your account is pending admin approval. You will be able to log in once approved.';
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
        }
    }
}

define('PAGE_TITLE', 'Doctor Registration');
require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-slate-100">
            <div class="bg-slate-900 px-6 py-8 sm:px-8 sm:py-10 text-center text-white relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-full opacity-10 bg-[url('https://www.transparenttextures.com/patterns/medical-icons.png')]"></div>
                <h2 class="text-2xl sm:text-3xl font-bold relative z-10">Join as a Doctor</h2>
                <p class="mt-2 text-slate-400 relative z-10">Register to reach more patients and manage your practice effectively</p>
            </div>
            
            <div class="px-6 py-8 sm:px-8 sm:py-10">
                <?php if ($error): ?>
                    <div class="mb-8 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-md flex items-center gap-3">
                        <i class="fa-solid fa-circle-exclamation text-red-500"></i>
                        <p class="text-red-700"><?php echo $error; ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="mb-8 bg-green-50 border-l-4 border-green-500 p-8 rounded-r-md text-center">
                        <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                        <h3 class="text-xl font-bold text-green-800 mb-2">Registration Submitted</h3>
                        <p class="text-green-700 mb-6"><?php echo $success; ?></p>
                        <a href="<?php echo APP_URL; ?>/login.php" class="inline-flex items-center justify-center px-6 py-3 bg-green-600 text-white font-bold rounded-xl hover:bg-green-700 transition-colors">
                            Back to Login
                        </a>
                    </div>
                <?php else: ?>

                <form action="" method="POST" class="space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Professional Details -->
                        <div class="space-y-6">
                            <h3 class="text-lg font-semibold text-slate-800 border-b pb-2 flex items-center gap-2">
                                <i class="fa-solid fa-user-md text-primary-500"></i> Professional Information
                            </h3>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Full Name (with Dr.)</label>
                                <input type="text" name="fullname" required class="w-full rounded-xl border-slate-300 focus:border-primary-500 focus:ring-primary-500 py-3" placeholder="Dr. Jane Doe">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Specialization</label>
                                <input type="text" name="specialization" required class="w-full rounded-xl border-slate-300 focus:border-primary-500 focus:ring-primary-500 py-3" placeholder="e.g., Cardiologist">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Qualification</label>
                                <input type="text" name="qualification" required class="w-full rounded-xl border-slate-300 focus:border-primary-500 focus:ring-primary-500 py-3" placeholder="e.g., MBBS, MD">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Years of Exp.</label>
                                    <input type="number" name="experience" required class="w-full rounded-xl border-slate-300 focus:border-primary-500 focus:ring-primary-500 py-3" placeholder="0">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Consultation Fee (TZS)</label>
                                    <input type="number" name="consultation_fee" required class="w-full rounded-xl border-slate-300 focus:border-primary-500 focus:ring-primary-500 py-3" placeholder="50000">
                                </div>
                            </div>
                        </div>

                        <!-- Account & Clinic -->
                        <div class="space-y-6">
                            <h3 class="text-lg font-semibold text-slate-800 border-b pb-2 flex items-center gap-2">
                                <i class="fa-solid fa-hospital text-primary-500"></i> Clinic & Account
                            </h3>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Affiliated Clinic</label>
                                <select name="clinic_id" required class="w-full rounded-xl border-slate-300 focus:border-primary-500 focus:ring-primary-500 py-3">
                                    <option value="">Select a Clinic</option>
                                    <?php foreach ($clinics as $clinic): ?>
                                        <option value="<?php echo $clinic['id']; ?>"><?php echo htmlspecialchars($clinic['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Phone Number</label>
                                <input type="tel" name="phone" required class="w-full rounded-xl border-slate-300 focus:border-primary-500 focus:ring-primary-500 py-3">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Email Address</label>
                                <input type="email" name="email" required class="w-full rounded-xl border-slate-300 focus:border-primary-500 focus:ring-primary-500 py-3">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                                <input type="password" name="password" required class="w-full rounded-xl border-slate-300 focus:border-primary-500 focus:ring-primary-500 py-3">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Short Bio</label>
                        <textarea name="bio" rows="4" class="w-full rounded-xl border-slate-300 focus:border-primary-500 focus:ring-primary-500 py-3" placeholder="Tell patients about your expertise..."></textarea>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full flex justify-center py-4 px-4 border border-transparent rounded-xl shadow-sm text-lg font-bold text-white bg-slate-900 hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                            Register Now
                        </button>
                    </div>
                    
                    <div class="text-center">
                        <p class="text-slate-600">
                            Already have an account? 
                            <a href="<?php echo APP_URL; ?>/login.php" class="font-bold text-primary-600 hover:text-primary-500 px-1">Login here</a>
                        </p>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
