<?php
// database/seed.php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Database.php';

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    echo "🌱 Seeding Database...\n";

    // 1. Create Addresses
    $addresses = [
        ['123 Samora Ave', 'Dar es Salaam', 'TZ', '11101'], // Admin/Clinic
        ['456 Ali Hassan Mwinyi Rd', 'Dar es Salaam', 'TZ', '14111'], // Doctor 1
        ['789 Njiro Rd', 'Arusha', 'TZ', '23100'], // Doctor 2
    ];
    
    $address_ids = [];
    $stmt = $db->prepare("INSERT INTO addresses (street_address, city, state, postal_code, country) VALUES (?, ?, ?, ?, 'Tanzania')");
    foreach ($addresses as $addr) {
        $stmt->execute($addr);
        $address_ids[] = $db->lastInsertId();
    }
    echo "✅ Addresses seeded.\n";

    // 2. Create Admin User
    $admin_pass = password_hash('admin123', PASSWORD_BCRYPT);
    $stmt = $db->prepare("INSERT INTO users (email, password_hash, user_type) VALUES (?, ?, 'admin')");
    $stmt->execute(['admin@docbook.co.tz', $admin_pass]);
    $admin_user_id = $db->lastInsertId();

    $stmt = $db->prepare("INSERT INTO admins (user_id, full_name, phone) VALUES (?, 'System Administrator', '0755-000000')");
    $stmt->execute([$admin_user_id]);
    echo "✅ Admin seeded (admin@docbook.co.tz / admin123).\n";

    // 3. Create Clinic
    $stmt = $db->prepare("INSERT INTO clinics (name, address_id, phone, email, created_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['Afya Bora Medical Centre', $address_ids[0], '0755-123456', 'info@afyabora.co.tz', $admin_user_id]);
    $clinic_id = $db->lastInsertId();
    echo "✅ Clinic seeded.\n";

    // 4. Create Doctors
    $doctors = [
        [
            'name' => 'Dr. Amani Mushi',
            'email' => 'amani@docbook.co.tz',
            'spec' => 'General Physician',
            'qual' => 'MBBS, MD',
            'exp' => 12,
            'fee' => 50000.00,
            'img' => 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?auto=format&fit=crop&q=80&w=300&h=300'
        ],
        [
            'name' => 'Dr. Baraka Juma',
            'email' => 'baraka@docbook.co.tz',
            'spec' => 'Cardiologist',
            'qual' => 'MBBS, MD, MMed',
            'exp' => 15,
            'fee' => 80000.00,
            'img' => 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?auto=format&fit=crop&q=80&w=300&h=300'
        ]
    ];

    foreach ($doctors as $idx => $doc) {
        // User
        $stmt = $db->prepare("INSERT INTO users (email, password_hash, user_type) VALUES (?, ?, 'doctor')");
        $stmt->execute([$doc['email'], $admin_pass]); // Same password for ease
        $doc_user_id = $db->lastInsertId();

        // Doctor Profile
        $stmt = $db->prepare("INSERT INTO doctors (user_id, full_name, specialization, qualification, experience_years, phone, bio, profile_image, clinic_id, address_id, consultation_fee) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $doc_user_id,
            $doc['name'],
            $doc['spec'],
            $doc['qual'],
            $doc['exp'],
            '0655-010' . $idx . '00',
            'Specialist at Afya Bora dedicated to patient care in Tanzania.',
            $doc['img'],
            $clinic_id,
            $address_ids[$idx + 1],
            $doc['fee']
        ]);
        $doctor_id = $db->lastInsertId();

        // Availability (Mon-Fri, 9AM - 5PM)
        $stmt = $db->prepare("INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time, slot_duration) VALUES (?, ?, '09:00:00', '14:00:00', 30)"); // 30 min slots
        for ($day = 1; $day <= 5; $day++) {
            $stmt->execute([$doctor_id, $day]);
        }
    }
    echo "✅ Doctors seeded (password: admin123).\n";

    $db->commit();
    echo "🚀 Database seeding completed successfully!\n";

} catch (Exception $e) {
    if (isset($db)) $db->rollBack();
    echo "❌ Error seeding database: " . $e->getMessage() . "\n";
}
?>
