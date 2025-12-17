<?php
// database/seed.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    echo "🌱 Seeding Database...\n";

    // 1. Create Addresses
    $addresses = [
        ['123 Main St', 'New York', 'NY', '10001'], // Admin/Clinic
        ['456 Elm St', 'New York', 'NY', '10002'], // Doctor 1
        ['789 Oak St', 'Brooklyn', 'NY', '11201'], // Doctor 2
    ];
    
    $address_ids = [];
    $stmt = $db->prepare("INSERT INTO addresses (street_address, city, state, postal_code) VALUES (?, ?, ?, ?)");
    foreach ($addresses as $addr) {
        $stmt->execute($addr);
        $address_ids[] = $db->lastInsertId();
    }
    echo "✅ Addresses seeded.\n";

    // 2. Create Admin User
    $admin_pass = password_hash('admin123', PASSWORD_BCRYPT);
    $stmt = $db->prepare("INSERT INTO users (email, password_hash, user_type) VALUES (?, ?, 'admin')");
    $stmt->execute(['admin@docbook.com', $admin_pass]);
    $admin_user_id = $db->lastInsertId();

    $stmt = $db->prepare("INSERT INTO admins (user_id, full_name, phone) VALUES (?, 'System Administrator', '555-0000')");
    $stmt->execute([$admin_user_id]);
    echo "✅ Admin seeded (admin@docbook.com / admin123).\n";

    // 3. Create Clinic
    $stmt = $db->prepare("INSERT INTO clinics (name, address_id, phone, email, created_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['City Health Center', $address_ids[0], '555-1234', 'info@cityhealth.com', $admin_user_id]);
    $clinic_id = $db->lastInsertId();
    echo "✅ Clinic seeded.\n";

    // 4. Create Doctors
    $doctors = [
        [
            'name' => 'Dr. Anita Kapoor',
            'email' => 'anita@docbook.com',
            'spec' => 'General Physician',
            'qual' => 'MBBS, MD',
            'exp' => 12,
            'fee' => 50.00,
            'img' => 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?auto=format&fit=crop&q=80&w=300&h=300'
        ],
        [
            'name' => 'Dr. James Wilson',
            'email' => 'james@docbook.com',
            'spec' => 'Cardiologist',
            'qual' => 'MBBS, MD, DM',
            'exp' => 15,
            'fee' => 80.00,
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
            '555-010' . $idx,
            'Experienced specialist dedicated to patient care.',
            $doc['img'],
            $clinic_id,
            $address_ids[$idx + 1],
            $doc['fee']
        ]);
        $doctor_id = $db->lastInsertId();

        // Availability (Mon-Fri, 9AM - 5PM)
        $stmt = $db->prepare("INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time, slot_duration) VALUES (?, ?, '09:00:00', '14:00:00', 90)"); // 9AM-2PM for shorter test
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
