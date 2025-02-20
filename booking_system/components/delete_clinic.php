<?php
require_once '../../manegment_system/components/auth.php';
require_once '../../manegment_system/components/db.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
        $id = $_POST['id'];

        // Delete clinic from the database
        $sql = "DELETE FROM clinics WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt) {
            $stmt->execute([$id]);
            echo "<script>alert('تم حذف العيادة بنجاح!'); window.location.href = 'add_clinic.php';</script>";
            exit;
        } else {
            throw new Exception("خطأ في إعداد الاستعلام.");
        }
    } else {
        die("معرف العيادة غير محدد.");
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'حدث خطأ في قاعدة البيانات.', 'details' => $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['error' => 'حدث خطأ غير متوقع.', 'details' => $e->getMessage()]);
}
?>