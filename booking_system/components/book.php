<?php
require_once '../../manegment_system/components/db.php';


$client_name = $_POST['client_name'];
$clinic_id = $_POST['clinic'];
$day_of_week = $_POST['day_of_week'];
$appointment_date = $_POST['appointment_date'];
$time_slot = $_POST['time_slot'];
$phone_number = $_POST['phone_number'];
$status = 'قيد الانتظار';

// تحقق مما إذا كان هناك موعد لنفس رقم الهاتف في نفس اليوم
$checkQuery = "SELECT * FROM appointments WHERE phone_number = ? AND DATE(appointment_datetime) = ?";
$stmt = $pdo->prepare($checkQuery);
$stmt->bindValue(1, $phone_number);
$stmt->bindValue(2, $appointment_date);
$stmt->execute();
$result = $stmt->fetchAll();

if (count($result) > 0) {
    // إذا وجد موعد، أعد توجيه المستخدم مع رسالة خطأ
    echo "<script>alert('لا يمكنك حجز أكثر من موعد في نفس اليوم باستخدام نفس رقم الهاتف.'); window.location.href = '../index.php';</script>";
} else {
    // إدخال الموعد الجديد في قاعدة البيانات
    $insertQuery = "INSERT INTO appointments (client_name, clinic_id, appointment_datetime, phone_number, status) VALUES (?, ?, ?, ?, 'قيد الأنتظار')";
    $appointment_datetime = $appointment_date . ' ' . $time_slot;
    $stmt = $pdo->prepare($insertQuery);
    $stmt->bindValue(1, $client_name);
    $stmt->bindValue(2, $clinic_id);
    $stmt->bindValue(3, $appointment_datetime);
    $stmt->bindValue(4, $phone_number);

    if ($stmt->execute()) {
        echo "<script>alert('تم حجز الموعد بنجاح.'); window.location.href = '../index.php';</script>";
    } else {
        echo "<script>alert('حدث خطأ أثناء حجز الموعد.'); window.location.href = '../index.php';</script>";
    }
}
$stmt = null;
$pdo = null;
?>
