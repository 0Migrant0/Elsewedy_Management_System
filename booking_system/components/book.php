<?php
require_once '../../manegment_system/components/db.php';

$client_name = $_POST['client_name'];
$clinic_id = $_POST['clinic'];
$day_of_week = $_POST['day_of_week'];
$appointment_date = $_POST['appointment_date'];
$time_slot = $_POST['time_slot'];
$phone_number = $_POST['phone_number'];
$status = 'قيد الانتظار';

// Verify existing appointments for the same phone on the same day
$checkQuery = "SELECT * FROM appointments WHERE phone_number = ? AND DATE(appointment_datetime) = ?";
$stmt = $pdo->prepare($checkQuery);
$stmt->execute([$phone_number, $appointment_date]);
$result = $stmt->fetchAll();

if (count($result) > 0) {
    echo "<script>alert('لا يمكنك حجز أكثر من موعد في نفس اليوم باستخدام نفس رقم الهاتف.'); window.location.href = '../index.php';</script>";
} else {
    // Insert new appointment into the database
    $appointment_datetime = $appointment_date . ' ' . $time_slot;
    $insertQuery = "INSERT INTO appointments (client_name, clinic_id, appointment_datetime, phone_number, status) 
                    VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($insertQuery);
    $success = $stmt->execute([$client_name, $clinic_id, $appointment_datetime, $phone_number, $status]);

    if ($success) {
        // Fetch clinic name for display
        $clinicStmt = $pdo->prepare("SELECT name FROM clinics WHERE id = ?");
        $clinicStmt->execute([$clinic_id]);
        $clinic = $clinicStmt->fetch(PDO::FETCH_ASSOC);

        // Prepare data for local storage
        $appointmentData = [
            'clientName' => $client_name,
            'clinic' => $clinic['name'],
            'day' => $day_of_week,
            'date' => $appointment_date,
            'time' => $time_slot,
            'phone' => $phone_number,
            'datetime' => $appointment_date . 'T' . $time_slot // ISO format for date comparison
        ];

        // Save to local storage and redirect
        echo "<script>
            let existing = JSON.parse(localStorage.getItem('appointments')) || [];
            existing.push(" . json_encode($appointmentData) . ");
            localStorage.setItem('appointments', JSON.stringify(existing));
            window.location.href = '../../ticket.html';
        </script>";
    } else {
        echo "<script>alert('حدث خطأ أثناء حجز الموعد.'); window.location.href = '../../index.php';</script>";
    }
}

$stmt = null;
$pdo = null;
?>