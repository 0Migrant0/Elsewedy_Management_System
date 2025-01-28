<?php
include 'db_connection.php';

$clinic_id = $_GET['clinic_id'];
$day_of_week = $_GET['day_of_week'];
$selected_date = $_GET['selected_date']; // احصل على التاريخ المحدد من استعلام GET

// استعلام للحصول على الوقت المتاح بناءً على اليوم
$sql = "SELECT start_time, end_time FROM clinics WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $clinic_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $start_time = $row['start_time'];
    $end_time = $row['end_time'];

    // استعلام للحصول على الوقت المحجوز في التاريخ المحدد
    $booked_sql = "SELECT appointment_datetime FROM appointments WHERE clinic_id = ? AND DATE(appointment_datetime) = ?";
    $booked_stmt = $conn->prepare($booked_sql);
    $booked_stmt->bind_param("is", $clinic_id, $selected_date); // استخدم التاريخ
    $booked_stmt->execute();
    $booked_result = $booked_stmt->get_result();

    $booked_slots = [];
    while ($booked_row = $booked_result->fetch_assoc()) {
        $booked_slots[] = date('H:i', strtotime($booked_row['appointment_datetime']));
    }

    $available_slots = [];
    $current_time = strtotime($start_time);
    $end_time = strtotime($end_time);

    while ($current_time <= $end_time) {
        $slot_time = date('H:i', $current_time);
        if (!in_array($slot_time, $booked_slots)) {
            $available_slots[] = "<option value='$slot_time'>$slot_time</option>";
        }
        $current_time = strtotime('+30 minutes', $current_time); // تقسيم الوقت الى كل نصف ساعه
    }

    // تحقق  إذا كان الاوقات المتاحة فارغة
    if (empty($available_slots)) {
        echo "<option disabled>تم ملء جميع المواعيد في هذا التاريخ</option>"; // عرض رسالة
    } else {
        echo implode('', $available_slots);
    }
}
?>
