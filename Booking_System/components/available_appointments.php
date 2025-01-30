<?php
require_once '../../manegment_system/components/db.php';

$today = date("Y-m-d");
$seven_days_later = date("Y-m-d", strtotime("+7 days"));

$sql = "SELECT * FROM appointments WHERE date BETWEEN '$today' AND '$seven_days_later'";
$result = $pdo->query($sql);

if ($result->rowCount() > 0) {
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "العيادة: " . $row['clinic_id'] . " - الطبيب: " . $row['doctor_id'] . " - التاريخ: " . $row['date'] . " - الوقت: " . $row['time_slot'] . "<br>";
    }
} else {
    echo "لا توجد مواعيد متاحة في الأيام السبعة القادمة.";
}
?>
