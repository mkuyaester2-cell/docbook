<?php
// index.php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Session.php';

// Redirect to dashboard if logged in
if (Auth::isLoggedIn()) {
    $role = Session::get('user_type');
    header("Location: " . APP_URL . "/$role/dashboard.php");
    exit;
}

define('PAGE_TITLE', 'Home');
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="relative bg-gradient-to-br from-blue-50 to-white overflow-hidden">
    <div class="absolute inset-0 bg-grid-slate-100 [mask-image:linear-gradient(0deg,white,rgba(255,255,255,0.6))] -z-10"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-32">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <!-- Text Content -->
            <div class="space-y-8 animate-fade-in-up">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-sm font-semibold">
                    <span class="relative flex h-2 w-2">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                    </span>
                    24/7 Online Booking Available
                </div>
                <h1 class="text-4xl lg:text-7xl font-bold tracking-tight text-slate-900 leading-tight">
                    Your Health, <br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-600 to-primary-400">Our Priority</span>
                </h1>
                <p class="text-lg lg:text-xl text-slate-600 max-w-lg leading-relaxed">
                    Book appointments with top-rated doctors in seconds. No holding on the line, no waiting rooms. Just modern healthcare designed for you.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 pt-4">
                    <a href="patient/register.php" class="inline-flex justify-center items-center px-8 py-4 text-lg font-semibold rounded-full text-white bg-primary-600 hover:bg-primary-700 transition-all shadow-lg shadow-primary-500/30 hover:shadow-primary-600/40 hover:-translate-y-1 w-full sm:w-auto">
                        Get Started
                        <i class="fa-solid fa-arrow-right ml-2"></i>
                    </a>
                    <a href="patient/browse-doctors.php" class="inline-flex justify-center items-center px-8 py-4 text-lg font-semibold rounded-full text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-all hover:-translate-y-1 shadow-sm w-full sm:w-auto">
                        Find a Doctor
                    </a>
                </div>
                
                <!-- Trust Indicators -->
                <div class="pt-8 flex items-center gap-8 text-slate-500 grayscale opacity-70">
                   <div class="flex items-center gap-2">
                       <i class="fa-solid fa-star text-yellow-400"></i>
                       <span class="font-semibold">4.9/5 Rating</span>
                   </div>
                   <div class="flex items-center gap-2">
                       <i class="fa-solid fa-user-doctor text-primary-500"></i>
                       <span class="font-semibold">50+ Specialists</span>
                   </div>
                </div>
            </div>

            <!-- Illustration/Image -->
            <div class="relative lg:h-[600px] flex items-center justify-center">
                <!-- Abstract blobs background -->
                <div class="absolute top-0 right-0 w-72 h-72 bg-purple-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
                <div class="absolute top-0 -left-4 w-72 h-72 bg-primary-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
                <div class="absolute -bottom-8 left-20 w-72 h-72 bg-pink-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>
                
                <!-- Main Image -->
                <img src="https://images.unsplash.com/photo-1638202993928-7267aad84c31?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Doctor with tablet" class="relative z-10 w-full rounded-3xl shadow-2xl transform rotate-2 hover:rotate-0 transition-transform duration-500 object-cover h-full border-4 border-white">
                
                <!-- Floating Cards -->
                <div class="absolute top-10 left-0 bg-white p-4 rounded-2xl shadow-xl z-20 animate-bounce-slow">
                    <div class="flex items-center gap-3">
                        <div class="bg-green-100 p-2 rounded-full text-green-600">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Appointment</p>
                            <p class="font-bold text-slate-800">Confirmed!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How it Works -->
<section class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <h2 class="text-3xl font-bold text-slate-900 mb-4">Healthcare Made Simple</h2>
            <p class="text-lg text-slate-600">Booking an appointment shouldn't be a headache. We've streamlined the process to get you the care you need, faster.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            <!-- Step 1 -->
            <div class="bg-slate-50 p-8 rounded-3xl hover:bg-white hover:shadow-xl transition-all duration-300 border border-slate-100 group">
                <div class="w-14 h-14 bg-blue-100 text-primary-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">1. Find a Specialist</h3>
                <p class="text-slate-500 leading-relaxed">Search by specialty, location, or name. View detailed profiles and verified patient reviews.</p>
            </div>

            <!-- Step 2 -->
            <div class="bg-slate-50 p-8 rounded-3xl hover:bg-white hover:shadow-xl transition-all duration-300 border border-slate-100 group">
                <div class="w-14 h-14 bg-purple-100 text-purple-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition-transform">
                    <i class="fa-regular fa-calendar-check"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">2. Book a Slot</h3>
                <p class="text-slate-500 leading-relaxed">View real-time availability and select a time that works for you. No phone calls required.</p>
            </div>

            <!-- Step 3 -->
            <div class="bg-slate-50 p-8 rounded-3xl hover:bg-white hover:shadow-xl transition-all duration-300 border border-slate-100 group">
                <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-user-doctor"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">3. Get Treatment</h3>
                <p class="text-slate-500 leading-relaxed">Visit the clinic or consult online. Your medical records are stored securely for future reference.</p>
            </div>
        </div>
    </div>
</section>

<style>
    @keyframes blob {
        0% { transform: translate(0px, 0px) scale(1); }
        33% { transform: translate(30px, -50px) scale(1.1); }
        66% { transform: translate(-20px, 20px) scale(0.9); }
        100% { transform: translate(0px, 0px) scale(1); }
    }
    .animate-blob { animation: blob 7s infinite; }
    .animation-delay-2000 { animation-delay: 2s; }
    .animation-delay-4000 { animation-delay: 4s; }
    
    @keyframes fade-in-up {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in-up { animation: fade-in-up 0.8s ease-out forwards; }
    
    @keyframes bounce-slow {
        0%, 100% { transform: translateY(-5%); }
        50% { transform: translateY(5%); }
    }
    .animate-bounce-slow { animation: bounce-slow 3s infinite ease-in-out; }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
