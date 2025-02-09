<?php
require_once '../../manegment_system/components/db.php';

try {
    // Fetch governorates
    $stmt = $pdo->query("SELECT id, name FROM governorates");
    $governorates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch cities
    $stmt = $pdo->query("SELECT id, name, governorate_id FROM cities");
    $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return data as JSON
    header('Content-Type: application/json');
    echo json_encode(['governorates' => $governorates, 'cities' => $cities]);
    exit;
} catch (PDOException $e) {
    echo json_encode(['error' => 'حدث خطأ في قاعدة البيانات.', 'details' => $e->getMessage()]);
}
?>