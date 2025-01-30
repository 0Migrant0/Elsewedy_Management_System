<?php
require_once '../../manegment_system/components/db.php';


$clinic_id = $_GET['clinic_id'];
$day_of_week = $_GET['day_of_week'];
$selected_date = $_GET['selected_date']; // احصل على التاريخ المحدد من استعلام GET

// استعلام للحصول على الوقت المتاح بناءً على اليوم
$sql = "SELECT start_time, end_time FROM clinics WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(1, $clinic_id);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
if ($row = $result[0]) {
    $start_time = $row['start_time'];
    $end_time = $row['end_time'];

    // استعلام للحصول على الوقت المحجوز في التاريخ المحدد
    $booked_sql = "SELECT appointment_datetime FROM appointments WHERE clinic_id = ? AND DATE(appointment_datetime) = ?";
    $booked_stmt = $pdo->prepare($booked_sql);
    $booked_stmt->bindParam(1, $clinic_id);
    $booked_stmt->bindParam(2, $selected_date); // استخدم التاريخ
    $booked_stmt->execute();
    $booked_result = $booked_stmt->fetchAll(PDO::FETCH_ASSOC);

    $booked_slots = [];
    foreach ($booked_result as $booked_row) {
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
