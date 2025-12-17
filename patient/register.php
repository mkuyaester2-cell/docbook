<?php
// patient/register.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Session.php';

// Redirect if already logged in - check BEFORE any output
if (Auth::isLoggedIn()) {
    header("Location: " . APP_URL);
    exit;
}

define('PAGE_TITLE', 'Patient Registration');
require_once __DIR__ . '/../includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect specific patient data
    $fullname = $_POST['fullname'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $gender = $_POST['gender'] ?? '';
    
    // Address data
    $street = $_POST['street'] ?? '';
    $city = $_POST['city'] ?? '';
    $state = $_POST['state'] ?? '';
    $zip = $_POST['zip'] ?? '';

    // Basic Validation
    if (empty($fullname) || empty($email) || empty($password) || empty($phone)) {
        $error = 'Please fill in all required fields.';
    } else {
        $db = Database::getInstance();
        $auth = new Auth();
        
        try {
            $db->beginTransaction();
            
            // 1. Create User Account
            $user_id = $auth->register($email, $password, 'patient');
            
            if ($user_id === false) {
                throw new Exception("Email already registered. Please login.");
            }
            
            // 2. Create Address
            $stmt = $db->prepare("INSERT INTO addresses (street_address, city, state, postal_code) VALUES (?, ?, ?, ?)");
            $stmt->execute([$street, $city, $state, $zip]);
            $address_id = $db->lastInsertId();
            
            // 3. Create Patient Profile
            $stmt = $db->prepare("INSERT INTO patients (user_id, full_name, phone, dob, gender, address_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $fullname, $phone, $dob, $gender, $address_id]);
            
            $db->commit();
            
            // Auto Login
            $auth->login($email, $password);
            Session::setFlash('success', 'Registration successful! Welcome to DocBook.');
            header("Location: " . APP_URL . "/patient/dashboard.php");
            exit;
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>

<div class="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-slate-100">
            <!-- Form Header -->
            <div class="bg-primary-600 px-6 py-8 sm:px-8 sm:py-10 text-center text-white relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-full opacity-10 bg-[url('https://www.transparenttextures.com/patterns/medical-icons.png')]"></div>
                <h2 class="text-2xl sm:text-3xl font-bold relative z-10">Create Patient Account</h2>
                <p class="mt-2 text-primary-100 relative z-10">Join thousands of users managing their health online</p>
            </div>
            
            <div class="px-6 py-8 sm:px-8 sm:py-10">
                <?php if ($error): ?>
                    <div class="mb-8 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-md flex items-center gap-3">
                        <i class="fa-solid fa-circle-exclamation text-red-500"></i>
                        <p class="text-red-700"><?php echo $error; ?></p>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" class="space-y-8">
                    <!-- Personal Details -->
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800 border-b pb-2 mb-6 flex items-center gap-2">
                            <i class="fa-regular fa-user text-primary-500"></i> Personal Information
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-slate-700 mb-1">Full Name</label>
                                <input type="text" name="fullname" required class="w-full rounded-xl border-slate-300 focus:border-primary-500 focus:ring-primary-500 py-3">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Date of Birth</label>
                                <input type="date" name="dob" required class="w-full rounded-xl border-slate-300 focus:border-primary-500 focus:ring-primary-500 py-3">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Gender</label>
                                <select name="gender" class="w-full rounded-xl border-slate-300 focus:border-primary-500 focus:ring-primary-500 py-3">
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Phone Number</label>
                                <input type="tel" name="phone" required class="w-full rounded-xl border-slate-300 focus:border-primary-500 focus:ring-primary-500 py-3">
                            </div>
                        </div>
                    </div>

                    <!-- Address -->
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800 border-b pb-2 mb-6 flex items-center gap-2">
                            <i class="fa-solid fa-map-pin text-primary-500"></i> Address
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-slate-700 mb-1">Street Address</label>
                                <input type="text" name="street" required class="w-full rounded-xl border-slate-300 focus:border-primary-500 focus:ring-primary-500 py-3">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">City</label>
                                <input type="text" name="city" required class="w-full rounded-xl border-slate-300 focus:border-primary-500 focus:ring-primary-500 py-3">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">State</label>
                                <input type="text" name="state" required class="w-full rounded-xl border-slate-300 focus:border-primary-500 focus:ring-primary-500 py-3">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Postal Code</label>
                                <input type="text" name="zip" required class="w-full rounded-xl border-slate-300 focus:border-primary-500 focus:ring-primary-500 py-3">
                            </div>
                        </div>
                    </div>

                    <!-- Account Details -->
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800 border-b pb-2 mb-6 flex items-center gap-2">
                            <i class="fa-solid fa-lock text-primary-500"></i> Account Security
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-slate-700 mb-1">Email Address</label>
                                <input type="email" name="email" required class="w-full rounded-xl border-slate-300 focus:border-primary-500 focus:ring-primary-500 py-3">
                            </div>
                            
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                                <input type="password" name="password" required class="w-full rounded-xl border-slate-300 focus:border-primary-500 focus:ring-primary-500 py-3" placeholder="Min. 8 characters">
                            </div>
                        </div>
                    </div>
                    
                    <div class="pt-4">
                        <button type="submit" class="w-full flex justify-center py-4 px-4 border border-transparent rounded-xl shadow-sm text-lg font-bold text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                            Create Account
                        </button>
                    </div>
                    
                    <div class="text-center">
                        <p class="text-slate-600">
                            Already have an account? 
                            <a href="<?php echo APP_URL; ?>/login.php" class="font-bold text-primary-600 hover:text-primary-500 px-1">Login here</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
