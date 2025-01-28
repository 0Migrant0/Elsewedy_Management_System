<?php
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $status = $_POST['status'];

    // تحديث حالة الحجز
    $sql = "UPDATE appointments SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $id);

    if ($stmt->execute()) {
        header("Location: ../dashboard.php"); // إعادة توجيه إلى صفحة التحكم بعد التحديث
        exit();
    } else {
        echo "حدث خطأ أثناء تحديث الحالة: " . $stmt->error;
    }

    $stmt->close();
}
$conn->close();
?>
