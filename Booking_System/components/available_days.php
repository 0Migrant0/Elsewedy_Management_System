<?php
require_once '../../manegment_system/components/db.php';


if (isset($_GET['clinic_id'])) {
    $clinic_id = $_GET['clinic_id'];

    // جلب الأيام المتاحة للعيادة
    $sql = "SELECT day_of_week FROM clinics WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(1, $clinic_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // تحويل الأيام من نص مفصول بفواصل إلى مصفوفة
        $days = explode(',', $result['day_of_week']);
        echo json_encode($days);
    } else {
        echo json_encode([]);
    }
}
?>