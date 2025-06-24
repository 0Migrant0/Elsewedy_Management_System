<?php
// التأكد من بدء الجلسة فقط إذا لم تكن نشطة بالفعل
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'auth.php';
require_once 'db.php';
// session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// إذا كنت تريد أن تكون الصفحة متاحة للأدمن فقط
if ($_SESSION['admin_role'] !== 'admin') {
    die('ليس لديك صلاحية الوصول لهذه الصفحة.');
}

// الحصول على معرف المريض
$patient_id = $_GET['id'] ?? null;
if (!$patient_id) {
    die("لا يوجد معرف مريض محدد.");
}

// استرداد بيانات المريض
$stmt = $pdo->prepare("SELECT * FROM patients WHERE id = :id");
$stmt->execute(['id' => $patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    die("لا توجد بيانات للمريض.");
}

// معالجة النموذج عند الإرسال


    // تحديث البيانات
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $phone = $_POST['phone'] ?? '';
    $clinic_name = $_POST['clinic_name'] ?? ''; // ← الآن نأخذ الاسم
    $specialization = $_POST['specialization'] ?? '';
    $status = $_POST['status'] ?? '';
    $contract = $_POST['contract'] ?? '';
    $diagnosis = $_POST['diagnosis'] ?? '';
    $notes = $_POST['notes'] ?? '';

    // تحديث البيانات باستخدام clinic_name
    $stmt = $pdo->prepare("
        UPDATE patients SET 
        name = :name,
        phone = :phone,
        clinic_name = :clinic_name,
        specialization = :specialization,
        status = :status,
        contract = :contract,
        diagnosis = :diagnosis,
        notes = :notes
        WHERE id = :id
    ");
    $stmt->execute([
        'name' => $name,
        'phone' => $phone,
        'clinic_name' => $clinic_name, // ← نحفظ الاسم هنا
        'specialization' => $specialization,
        'status' => $status,
        'contract' => $contract,
        'diagnosis' => $diagnosis,
        'notes' => $notes,
        'id' => $patient_id
    ]);

    $_SESSION['success_message'] = "تم تعديل بيانات المريض بنجاح.";
    header("Location: view_patient.php?id=" . $patient_id);
    exit;
}
    
// جلب العيادات من قاعدة البيانات
$stmt_clinics = $pdo->query("SELECT id, name FROM clinics ORDER BY name ASC");
$clinics = $stmt_clinics->fetchAll(PDO::FETCH_ASSOC);

// حالات محددة مسبقًا
$status_options = ['كشف', 'مراجعة'];
$specialization_options = [
    'كسور',
    'أطفال',
    'مفاصل',
    'طب رياضي',
    'أطفال CP'
];
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تعديل بيانات المريض</title>
    <link rel="stylesheet" href="../styles/style.css">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        form { max-width: 600px; margin: auto; }
        label { display: block; margin-top: 15px; font-weight: bold; }
        input, textarea { width: 100%; padding: 8px; margin-top: 5px; }
        button { margin-top: 20px; padding: 10px 20px; background-color: #28a745; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h2>تعديل بيانات المريض</h2>

    <?php if (isset($_SESSION['success_message'])): ?>
        <p style="color: green;"><?= $_SESSION['success_message'] ?></p>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    

    <form method="POST" action="">
        <label for="name">الاسم:</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($patient['name']) ?>" required>

        <label for="phone">رقم الهاتف:</label>
        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($patient['phone']) ?>">

        <!-- العيادة -->
<!-- العيادة -->
<label for="clinic_name">العيادة:</label>
<select id="clinic_name" name="clinic_name" required>
    <option value="">اختر العيادة</option>
    <?php foreach ($clinics as $clinic): ?>
        <option value="<?= htmlspecialchars($clinic['name']) ?>"
            <?= ($patient['clinic_name'] ?? '') == $clinic['name'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($clinic['name']) ?>
        </option>
    <?php endforeach; ?>
</select>

<!-- الحالة -->
<label for="status">الحالة:</label>
<select id="status" name="status" required>
    <option value="">اختر الحالة</option>
    <?php foreach ($status_options as $stat): ?>
        <option value="<?= htmlspecialchars($stat) ?>"
            <?= ($patient['status'] ?? '') == $stat ? 'selected' : '' ?>>
            <?= htmlspecialchars($stat) ?>
        </option>
    <?php endforeach; ?>
</select>

<!-- التخصص الدقيق -->
<label for="specialization">التخصص الدقيق:</label>
<select id="specialization" name="specialization" required>
    <option value="">اختر التخصص</option>
    <?php foreach ($specialization_options as $spec): ?>
        <option value="<?= htmlspecialchars($spec) ?>"
            <?= ($patient['specialization'] ?? '') == $spec ? 'selected' : '' ?>>
            <?= htmlspecialchars($spec) ?>
        </option>
    <?php endforeach; ?>
</select>
        <label for="contract">التعاقد:</label>
        <input type="text" id="contract" name="contract" value="<?= htmlspecialchars($patient['contract'] ?? '') ?>">

        <label for="diagnosis">التشخيص:</label>
        <textarea id="diagnosis" name="diagnosis"><?= htmlspecialchars($patient['diagnosis'] ?? '') ?></textarea>

        <label for="notes">الملاحظات:</label>
        <textarea id="notes" name="notes"><?= htmlspecialchars($patient['notes'] ?? '') ?></textarea>

        <button type="submit">حفظ التعديلات</button>
    </form>
</body>
</html>