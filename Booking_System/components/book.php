<?php
include 'db_connection.php';

$client_name = $_POST['client_name'];
$clinic_id = $_POST['clinic'];
$day_of_week = $_POST['day_of_week'];
$appointment_date = $_POST['appointment_date'];
$time_slot = $_POST['time_slot'];
$phone_number = $_POST['phone_number'];
$status = 'قيد الانتظار';

// تحقق مما إذا كان هناك موعد لنفس رقم الهاتف في نفس اليوم
$checkQuery = "SELECT * FROM appointments WHERE phone_number = ? AND DATE(appointment_datetime) = ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("ss", $phone_number, $appointment_date);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // إذا وجد موعد، أعد توجيه المستخدم مع رسالة خطأ
    echo "<script>alert('لا يمكنك حجز أكثر من موعد في نفس اليوم باستخدام نفس رقم الهاتف.'); window.location.href = '../index.php';</script>";
} else {
    // إدخال الموعد الجديد في قاعدة البيانات
    $insertQuery = "INSERT INTO appointments (client_name, clinic_id, appointment_datetime, phone_number, status) VALUES (?, ?, ?, ?, 'قيد الأنتظار')";
    $appointment_datetime = $appointment_date . ' ' . $time_slot;
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("siss", $client_name, $clinic_id, $appointment_datetime, $phone_number);

    if ($stmt->execute()) {
        echo "<script>alert('تم حجز الموعد بنجاح.'); window.location.href = '../index.php';</script>";
    } else {
        echo "<script>alert('حدث خطأ أثناء حجز الموعد.'); window.location.href = '../index.php';</script>";
    }
}

$stmt->close();
$conn->close();
?>


