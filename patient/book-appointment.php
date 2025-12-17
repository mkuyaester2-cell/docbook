<?php
// patient/book-appointment.php

// Load dependencies FIRST (before any output)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Session.php';

Auth::requireRole('patient');

$doctor_id = $_GET['doctor_id'] ?? null;
if (!$doctor_id) {
    header("Location: browse-doctors.php");
    exit;
}

$db = Database::getInstance();

// Fetch Doctor Details
$stmt = $db->prepare("SELECT d.*, c.name as clinic_name, c.address_id, a.city, a.street_address 
                      FROM doctors d 
                      JOIN clinics c ON d.clinic_id = c.id
                      JOIN addresses a ON c.address_id = a.id
                      WHERE d.id = ?");
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch();

if (!$doctor) {
    die("Doctor not found");
}

// Handle Booking Submission BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $time = $_POST['time'];
    
    // Get patient ID
    $stmt = $db->prepare("SELECT id FROM patients WHERE user_id = ?");
    $stmt->execute([Auth::id()]);
    $patient = $stmt->fetch();
    
    // Insert Appointment
    // Note: In a real app, re-verify availability here to prevent double booking!
    $stmt = $db->prepare("INSERT INTO appointments (patient_id, doctor_id, clinic_id, appointment_date, appointment_time, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$patient['id'], $doctor['id'], $doctor['clinic_id'], $date, $time]);
    
    // Redirect BEFORE any HTML output
    header("Location: appointment-confirmation.php?id=" . $db->lastInsertId());
    exit;
}

// NOW include header (which outputs HTML)
define('PAGE_TITLE', 'Book Appointment');
require_once __DIR__ . '/../includes/header.php';
?>

<div class="bg-slate-50 min-h-screen py-12">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <a href="browse-doctors.php" class="inline-flex items-center text-slate-500 hover:text-primary-600 mb-6 transition-colors font-medium">
            <i class="fa-solid fa-arrow-left mr-2"></i> Back to Doctors
        </a>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Booking Form -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-8">
                        <h1 class="text-2xl font-bold text-slate-900 mb-6">Select Appointment Time</h1>
                        
                        <form id="bookingForm" method="POST" class="space-y-8">
                            <!-- Hidden Inputs -->
                            <input type="hidden" name="date" id="selectedDateInput" required>
                            <input type="hidden" name="time" id="selectedTime" required>

                            <!-- Custom Calendar UI -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-4">Select Date</label>
                                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                                    <div class="flex items-center justify-between mb-6">
                                        <button type="button" id="prevMonth" class="p-2 hover:bg-slate-100 rounded-full text-slate-600 transition-colors">
                                            <i class="fa-solid fa-chevron-left"></i>
                                        </button>
                                        <h2 id="monthYear" class="text-lg font-bold text-slate-900"></h2>
                                        <button type="button" id="nextMonth" class="p-2 hover:bg-slate-100 rounded-full text-slate-600 transition-colors">
                                            <i class="fa-solid fa-chevron-right"></i>
                                        </button>
                                    </div>
                                    
                                    <!-- Days Grid -->
                                    <div class="grid grid-cols-7 mb-2 text-center">
                                        <div class="text-xs font-semibold text-slate-400 py-2">Su</div>
                                        <div class="text-xs font-semibold text-slate-400 py-2">Mo</div>
                                        <div class="text-xs font-semibold text-slate-400 py-2">Tu</div>
                                        <div class="text-xs font-semibold text-slate-400 py-2">We</div>
                                        <div class="text-xs font-semibold text-slate-400 py-2">Th</div>
                                        <div class="text-xs font-semibold text-slate-400 py-2">Fr</div>
                                        <div class="text-xs font-semibold text-slate-400 py-2">Sa</div>
                                    </div>
                                    <div id="calendarDays" class="grid grid-cols-7 gap-1 text-sm">
                                        <!-- Days injected via JS -->
                                    </div>
                                </div>
                            </div>

                            <!-- Slots Selection -->
                            <div id="slotsContainer" class="hidden animate-fade-in-up">
                                <label class="block text-sm font-medium text-slate-700 mb-3">Available Time Slots</label>
                                <div id="loader" class="hidden py-8 text-center">
                                    <div class="inline-block animate-spin w-6 h-6 border-2 border-primary-600 border-t-transparent rounded-full font-medium"></div>
                                    <p class="text-xs text-slate-500 mt-2">Checking availability...</p>
                                </div>
                                <div id="slotsGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                                    <!-- Slots injected via JS -->
                                </div>
                                <p id="noSlotsMsg" class="hidden text-amber-600 bg-amber-50 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                                    <i class="fa-solid fa-circle-exclamation"></i>
                                    No free slots available for this date.
                                </p>
                            </div>

                            <button type="submit" id="bookBtn" disabled class="w-full bg-slate-100 text-slate-400 font-bold py-4 rounded-xl transition-all cursor-not-allowed flex items-center justify-center gap-2">
                                <span>Confirm Appointment</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Column: Doctor Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-3xl shadow-lg border border-slate-100 p-6 sticky top-24">
                    <div class="flex items-center gap-4 mb-6 border-b border-slate-100 pb-6">
                        <img src="<?php echo htmlspecialchars($doctor['profile_image']); ?>" class="w-20 h-20 rounded-2xl object-cover shadow-md">
                        <div>
                            <h3 class="font-bold text-slate-900 text-lg"><?php echo htmlspecialchars($doctor['full_name']); ?></h3>
                            <p class="text-primary-600 font-medium"><?php echo htmlspecialchars($doctor['specialization']); ?></p>
                        </div>
                    </div>
                    
                    <div class="space-y-4 text-sm text-slate-600">
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-hospital mt-1 text-slate-400"></i>
                            <div>
                                <p class="font-medium text-slate-900"><?php echo htmlspecialchars($doctor['clinic_name']); ?></p>
                                <p><?php echo htmlspecialchars($doctor['street_address'] . ', ' . $doctor['city']); ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-money-bill text-slate-400"></i>
                            <span class="font-medium text-slate-900">$<?php echo number_format($doctor['consultation_fee'], 0); ?> Consultation Fee</span>
                        </div>
                    </div>

                    <div class="mt-6 bg-blue-50 p-4 rounded-xl flex items-start gap-3">
                        <i class="fa-solid fa-circle-info text-blue-500 mt-0.5"></i>
                        <p class="text-xs text-blue-700">Please arrive 15 minutes before your scheduled appointment time.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const doctorId = <?php echo $doctor_id; ?>;
let currentDate = new Date(); // Internal tracking date state
let workingDays = []; // [1, 3, 5] etc.
let selectedDateStr = null;

// Elements
const monthYearEl = document.getElementById('monthYear');
const calendarDaysEl = document.getElementById('calendarDays');
const prevBtn = document.getElementById('prevMonth');
const nextBtn = document.getElementById('nextMonth');
const selectedDateInput = document.getElementById('selectedDateInput');
const slotsContainer = document.getElementById('slotsContainer');
const slotsGrid = document.getElementById('slotsGrid');
const loader = document.getElementById('loader');
const noSlotsMsg = document.getElementById('noSlotsMsg');
const selectedTimeInput = document.getElementById('selectedTime');
const bookBtn = document.getElementById('bookBtn');

// Initialize
init();

async function init() {
    await fetchWorkingDays();
    renderCalendar();
    
    prevBtn.onclick = () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    };
    nextBtn.onclick = () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    };
}

async function fetchWorkingDays() {
    try {
        const res = await fetch(`../api/get-doctor-working-days.php?doctor_id=${doctorId}`);
        workingDays = await res.json();
    } catch (e) {
        console.error("Failed to fetch schedule", e);
    }
}

function renderCalendar() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    
    // Update Header
    const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    monthYearEl.textContent = `${monthNames[month]} ${year}`;
    
    calendarDaysEl.innerHTML = '';
    
    // Days calculation
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startDayIndex = firstDay.getDay(); // 0 is Sunday
    const totalDays = lastDay.getDate();
    
    // Empty cells for offset
    for (let i = 0; i < startDayIndex; i++) {
        calendarDaysEl.innerHTML += `<div></div>`;
    }
    
    // Today check
    const today = new Date();
    today.setHours(0,0,0,0);
    
    // Active days
    for (let i = 1; i <= totalDays; i++) {
        const dayDate = new Date(year, month, i);
        const dayOfWeek = dayDate.getDay();
        const dateStr = formatDate(dayDate); // YYYY-MM-DD
        
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = i;
        btn.className = 'h-10 w-10 mx-auto flex items-center justify-center rounded-full text-sm font-medium transition-all';
        
        // Logic for styling
        const isBeforeToday = dayDate < today;
        const isWorkingDay = workingDays.includes(dayOfWeek);
        const isSelected = selectedDateStr === dateStr;
        
        if (isBeforeToday || !isWorkingDay) {
            btn.classList.add('text-slate-300', 'cursor-not-allowed');
            if(!isWorkingDay) btn.classList.add('bg-slate-50', 'opacity-50');
        } else {
            if (isSelected) {
                btn.classList.add('bg-primary-600', 'text-white', 'shadow-md', 'scale-110');
            } else {
                btn.classList.add('text-slate-700', 'hover:bg-blue-50', 'hover:text-primary-600');
            }
            // Add click event
            btn.onclick = () => selectDate(dayDate, btn);
        }
        
        // Highlight today
        if (dayDate.getTime() === today.getTime() && !isSelected) {
            btn.classList.add('border', 'border-primary-200');
        }
        
        calendarDaysEl.appendChild(btn);
    }
}

function formatDate(date) {
    const offset = date.getTimezoneOffset();
    date = new Date(date.getTime() - (offset*60*1000));
    return date.toISOString().split('T')[0];
}

async function selectDate(date, btnEl) {
    selectedDateStr = formatDate(date);
    selectedDateInput.value = selectedDateStr;
    
    // Rerender to show active state
    renderCalendar();
    
    // Fetch slots
    slotsContainer.classList.remove('hidden');
    slotsGrid.innerHTML = '';
    noSlotsMsg.classList.add('hidden');
    loader.classList.remove('hidden');
    
    // Reset selection
    selectedTimeInput.value = '';
    bookBtn.disabled = true;
    updateBookBtn(false);
    
    try {
        const res = await fetch(`../api/get-available-slots.php?doctor_id=${doctorId}&date=${selectedDateStr}`);
        const slots = await res.json();
        
        loader.classList.add('hidden');
        
        if (slots.length === 0) {
            noSlotsMsg.classList.remove('hidden');
        } else {
            slots.forEach(slot => {
                const sBtn = document.createElement('button');
                sBtn.type = 'button';
                sBtn.className = 'py-3 px-2 rounded-xl border border-slate-200 font-medium text-sm hover:border-primary-500 hover:text-primary-600 hover:bg-primary-50 transition-all focus:outline-none slot-btn';
                sBtn.textContent = slot.display;
                sBtn.onclick = () => selectSlot(sBtn, slot.value);
                slotsGrid.appendChild(sBtn);
            });
        }
    } catch (e) {
        console.error(e);
        loader.classList.add('hidden');
    }
}

function selectSlot(btn, timeValue) {
    // Styling reset
    document.querySelectorAll('.slot-btn').forEach(b => {
        b.classList.remove('bg-primary-600', 'text-white', 'border-primary-600', 'shadow-md');
        b.classList.add('border-slate-200');
    });
    
    // Active style
    btn.classList.remove('border-slate-200', 'hover:bg-primary-50');
    btn.classList.add('bg-primary-600', 'text-white', 'border-primary-600', 'shadow-md');
    
    selectedTimeInput.value = timeValue;
    updateBookBtn(true);
}

function updateBookBtn(enable) {
    if(enable) {
        bookBtn.disabled = false;
        bookBtn.classList.remove('bg-slate-100', 'text-slate-400', 'cursor-not-allowed');
        bookBtn.classList.add('bg-primary-600', 'text-white', 'hover:bg-primary-700', 'hover:shadow-lg', 'transform', 'hover:-translate-y-0.5');
    } else {
        bookBtn.disabled = true;
        bookBtn.classList.add('bg-slate-100', 'text-slate-400', 'cursor-not-allowed');
        bookBtn.classList.remove('bg-primary-600', 'text-white', 'hover:bg-primary-700', 'hover:shadow-lg', 'transform', 'hover:-translate-y-0.5');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
