<?php
require_once '../../manegment_system/components/db.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $status = $_POST['status'];

    // تحديث حالة الحجز
    $sql = "UPDATE appointments SET status = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(1, $status);
    $stmt->bindValue(2, $id);

    if ($stmt->execute()) {
        header("Location: ../dashboard.php"); // إعادة توجيه إلى صفحة التحكم بعد التحديث
        exit();
    } else {
        echo "حدث خطأ أثناء تحديث الحالة: " . $stmt->errorInfo()[2];
    }

    $stmt = null;
}
$pdo = null;
?>
