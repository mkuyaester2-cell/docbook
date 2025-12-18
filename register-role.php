<?php
// register.php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Session.php';

// Redirect if already logged in
if (Auth::isLoggedIn()) {
    header("Location: " . APP_URL);
    exit;
}

define('PAGE_TITLE', 'Join DocBook');
require_once __DIR__ . '/includes/header.php';
?>

<div class="min-h-[calc(100vh-200px)] bg-slate-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl w-full">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-extrabold text-slate-900 mb-4">Choose Your Account Type</h1>
            <p class="text-xl text-slate-600">Join our community to manage your health or grow your medical practice.</p>
        </div>

        <div class="grid md:grid-cols-2 gap-8">
            <!-- Patient Option -->
            <a href="patient/register.php" class="group bg-white rounded-3xl p-8 shadow-sm border border-slate-100 hover:shadow-2xl hover:border-primary-500 transition-all duration-300 transform hover:-translate-y-2 flex flex-col items-center text-center">
                <div class="w-24 h-24 bg-primary-100 text-primary-600 rounded-2xl flex items-center justify-center text-5xl mb-6 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-user-injured"></i>
                </div>
                <h2 class="text-2xl font-bold text-slate-900 mb-4">I am a Patient</h2>
                <p class="text-slate-500 leading-relaxed mb-8 flex-grow">
                    Book appointments, manage your medical records, and get access to top-rated specialists in just a few clicks.
                </p>
                <div class="w-full py-4 px-6 bg-primary-600 text-white font-bold rounded-xl group-hover:bg-primary-700 transition-colors">
                    Register as Patient
                </div>
            </a>

            <!-- Doctor Option -->
            <a href="doctor/register.php" class="group bg-white rounded-3xl p-8 shadow-sm border border-slate-100 hover:shadow-2xl hover:border-slate-900 transition-all duration-300 transform hover:-translate-y-2 flex flex-col items-center text-center">
                <div class="w-24 h-24 bg-slate-900 text-white rounded-2xl flex items-center justify-center text-5xl mb-6 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-user-md"></i>
                </div>
                <h2 class="text-2xl font-bold text-slate-900 mb-4">I am a Doctor</h2>
                <p class="text-slate-500 leading-relaxed mb-8 flex-grow">
                    Join our network of healthcare professionals, manage your schedule, and reach more patients effectively.
                </p>
                <div class="w-full py-4 px-6 bg-slate-900 text-white font-bold rounded-xl group-hover:bg-slate-800 transition-colors">
                    Join as Doctor
                </div>
            </a>
        </div>

        <div class="mt-12 text-center">
            <p class="text-slate-500">
                Already have an account? 
                <a href="login.php" class="text-primary-600 font-bold hover:underline">Sign In</a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
