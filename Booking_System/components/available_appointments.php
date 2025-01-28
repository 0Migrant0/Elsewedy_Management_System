<?php
include 'db_connection.php';
$today = date("Y-m-d");
$seven_days_later = date("Y-m-d", strtotime("+7 days"));

$sql = "SELECT * FROM appointments WHERE date BETWEEN '$today' AND '$seven_days_later'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "العيادة: " . $row['clinic_id'] . " - الطبيب: " . $row['doctor_id'] . " - التاريخ: " . $row['date'] . " - الوقت: " . $row['time_slot'] . "<br>";
    }
} else {
    echo "لا توجد مواعيد متاحة في الأيام السبعة القادمة.";
}
?>
