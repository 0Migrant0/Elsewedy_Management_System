<?php
include 'db_connection.php';

if (isset($_GET['clinic_id'])) {
    $clinic_id = $_GET['clinic_id'];

    // جلب الأيام المتاحة للعيادة
    $sql = "SELECT day_of_week FROM clinics WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $clinic_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // تحويل الأيام من نص مفصول بفواصل إلى مصفوفة
        $days = explode(',', $row['day_of_week']);
        echo json_encode($days);
    } else {
        echo json_encode([]);
    }
}
?>
