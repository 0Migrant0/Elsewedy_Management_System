<?php
// check_medical_id.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $medical_id = htmlspecialchars(trim($_POST['medical_id']));

    try {
        $db = new PDO('mysql:host=localhost;dbname=booking_system', 'root', '');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if medical_id exists
        $stmt = $db->prepare("SELECT COUNT(*) FROM patients WHERE medical_id = ?");
        $stmt->execute([$medical_id]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            echo "exists"; // Return "exists" if medical_id exists
        } else {
            echo "not_exists"; // Return "not_exists" if medical_id doesn't exist
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
