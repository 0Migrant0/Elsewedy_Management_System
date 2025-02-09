<?php
require_once '../../manegment_system/components/auth.php';
require_once '../../manegment_system/components/db.php';

try {
    // Get JSON data from the request
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Validate required fields
    if (!isset($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'معرف العيادة غير محدد.']);
        exit;
    }

    $id = $data['id'];
    $governorate = $data['governorate'];
    $city = $data['city'];
    $name = $data['name'];
    $start_time = $data['start_time'];
    $end_time = $data['end_time'];
    $day_of_week = $data['day_of_week'];

    // Update clinic data in the database
    $sql = "UPDATE clinics SET governorate = ?, city = ?, name = ?, start_time = ?, end_time = ?, day_of_week = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    if ($stmt) {
        $stmt->execute([$governorate, $city, $name, $start_time, $end_time, $day_of_week, $id]);
        echo json_encode(['success' => true, 'message' => 'تم تحديث العيادة بنجاح.']);
    } else {
        throw new Exception("خطأ في إعداد الاستعلام.");
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في قاعدة البيانات.', 'details' => $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'حدث خطأ غير متوقع.', 'details' => $e->getMessage()]);
}
?>