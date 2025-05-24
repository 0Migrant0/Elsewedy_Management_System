<?php
// fetch_appointments.php
header("Content-Type: application/json");

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'components/db.php';
try {
    $stmt = $pdo->prepare("
        SELECT 
            a.id AS appointment_id,
            a.clinic_id,
            a.client_name,
            a.appointment_datetime,
            a.phone_number,
            a.status,
            c.name AS clinic_name
        FROM appointments a
        JOIN clinics c ON a.clinic_id = c.id
        WHERE a.appointment_datetime > NOW()
        ORDER BY a.appointment_datetime ASC
    ");
    $stmt->execute();
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($appointments);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'فشل في جلب المواعيد', 'details' => $e->getMessage()]);
}
?>