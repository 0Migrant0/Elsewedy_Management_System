<?php
require_once 'auth.php';
require_once 'db.php';

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

// معالجة النموذج لتحديث البيانات والأسعار
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $consultation_price = $_POST['consultation_price'] ?? 0;
    $consultation_notes = $_POST['consultation_notes'] ?? '';

    $injection_price = $_POST['injection_price'] ?? 0;
    $injection_notes = $_POST['injection_notes'] ?? '';

    $medicine_price = $_POST['medicine_price'] ?? 0;
    $medicine_notes = $_POST['medicine_notes'] ?? '';

    $xray_price = $_POST['xray_price'] ?? 0;
    $xray_notes = $_POST['xray_notes'] ?? '';

    $test_price = $_POST['test_price'] ?? 0;
    $test_notes = $_POST['test_notes'] ?? '';

    $prescription_price = $_POST['prescription_price'] ?? 0;
    $prescription_notes = $_POST['prescription_notes'] ?? '';

    $total_price = $consultation_price + $injection_price + $medicine_price + $xray_price + $test_price + $prescription_price;

    $stmt = $pdo->prepare("
        UPDATE patients SET 
        consultation_price = :consultation_price,
        consultation_notes = :consultation_notes,
        injection_price = :injection_price,
        injection_notes = :injection_notes,
        medicine_price = :medicine_price,
        medicine_notes = :medicine_notes,
        xray_price = :xray_price,
        xray_notes = :xray_notes,
        test_price = :test_price,
        test_notes = :test_notes,
        prescription_price = :prescription_price,
        prescription_notes = :prescription_notes,
        total_price = :total_price
        WHERE id = :id
    ");
    $stmt->execute([
        'consultation_price' => $consultation_price,
        'consultation_notes' => $consultation_notes,
        'injection_price' => $injection_price,
        'injection_notes' => $injection_notes,
        'medicine_price' => $medicine_price,
        'medicine_notes' => $medicine_notes,
        'xray_price' => $xray_price,
        'xray_notes' => $xray_notes,
        'test_price' => $test_price,
        'test_notes' => $test_notes,
        'prescription_price' => $prescription_price,
        'prescription_notes' => $prescription_notes,
        'total_price' => $total_price,
        'id' => $patient_id
    ]);

    $_SESSION['success_message'] = "تم تحديث البيانات بنجاح.";
    header("Location: view_patient.php?id=" . $patient_id);
    exit;
}
?>

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>عرض المريض</title>
</head>

<body>
    <header>
        <nav>
            <div class="menu-toggle" onclick="toggleMenu()">
                <i class="fas fa-bars"></i>
            </div>
            <div class="menu">
                <a href="../index.php"><i class="fas fa-home"></i> الرئيسية</a>
                <a href="add_patient.php"><i class="fas fa-user-plus"></i> إضافة مريض</a>
                <a href="../../booking_system/dashboard.php"><i class="fas fa-calendar-alt"></i> الحجوزات</a>
                <a href="../../index.php"><i class="fas fa-calendar-check"></i> حجز موعد</a>
                <a href="generate_pdf.php?id=<?= htmlspecialchars($patient['id']) ?>" class="btn btn-primary pdf-btn">
                    <i class="fas fa-file-pdf"></i> تحميل PDF
                </a>
                <a href="components/logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
            </div>
        </nav>
    </header>

    <div class="container patiet-details">
        <div class="patient-info">
            <div>
                <h2>تفاصيل المريض</h2>
                <p>الاسم: <?= htmlspecialchars($patient['name']) ?></p>
                <p>رقم المريض: <?= htmlspecialchars($patient['medical_id']) ?></p>
                <p>رقم الهاتف: <?= htmlspecialchars($patient['phone']) ?></p>
                <p>العيادة: <?= htmlspecialchars($patient['clinic_name'] ?? 'غير محدد') ?></p>
                <p>التخصص الدقيق: <?= htmlspecialchars($patient['specialization'] ?? 'غير محدد') ?></p>
                <p>الحالة: <?= htmlspecialchars($patient['status']) ?></p>
                <p>التعاقد: <?= htmlspecialchars($patient['contract'] ?? 'لا يوجد تعاقد') ?></p>

            </div>
            <div>
                <h2>بيانات إضافية</h2>
                <p><span>التشخيص:</span> <?= htmlspecialchars($patient['diagnosis'] ?? 'لا يوجد تشخيص') ?></p>
                <p><span>الملاحظات:</span> <?= htmlspecialchars($patient['notes'] ?? 'لا يوجد ملاحظات  ') ?></p>
                <a href="handle_patient_images.php?id=<?= $patient['id'] ?>"
                    class="btnm gradient-btn hover-shadow ripple-animation rounded-corners hover-color transition-effect">
                    Manage Images
                </a>
            </div>
        </div>
        <div class="files">
            <div>
                <h2>صور الأشعة:</h2>
                <div class="image-scroll-wrapper">
                    <div class="image-container">
                        <?php
                        // عرض صور الأشعة
                        if (!empty($patient['xray_images'])):
                            $xray_images = json_decode($patient['xray_images'], true) ?? [];
                            foreach ($xray_images as $image): ?>
                                <div class="image-wrapper">
                                    <img src="<?= htmlspecialchars($image) ?>" alt="صورة الأشعة" class="popup-image">
                                </div>
                            <?php endforeach;
                        else: ?>
                            <p>لا توجد صور أشعة</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div>
                <h2>ملفات التحاليل:</h2>
                <div class="image-scroll-wrapper">
                    <div class="image-container">
                        <?php
                        if (!empty($patient['test_files'])):
                            $test_files = json_decode($patient['test_files'], true) ?? [];
                            foreach ($test_files as $file): ?>
                                <div class="image-wrapper">
                                    <img src="<?= htmlspecialchars($file) ?>" alt="ملف التحليل" class="popup-image">
                                </div>
                            <?php endforeach;
                        else: ?>
                            <p>لا توجد ملفات تحليل</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div>
                <h2>الوصفات الطبية:</h2>
                <div class="image-scroll-wrapper">
                    <div class="image-container">
                        <?php
                        if (!empty($patient['prescriptions'])):
                            $prescriptions = json_decode($patient['prescriptions'], true) ?? [];
                            foreach ($prescriptions as $prescription): ?>
                                <div class="image-wrapper">
                                    <img src="<?= htmlspecialchars($prescription) ?>" alt="الوصفة الطبية" class="popup-image">
                                </div>
                            <?php endforeach;
                        else: ?>
                            <p>لا توجد وصفات طبية</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div>
                <h2> الملفات:</h2>
                <div class="image-scroll-wrapper">
                    <div class="image-container">
                        <?php
                        if (!empty($patient['file_path'])):
                            $prescriptions = json_decode($patient['file_path'], true) ?? [];
                            foreach ($prescriptions as $prescription): ?>
                                <div class="image-wrapper">
                                    <img src="<?= htmlspecialchars($prescription) ?>" alt=" الملفات" class="popup-image">
                                </div>
                            <?php endforeach;
                        else: ?>
                            <p>لا توجد ملفات</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Popup Modal -->
        <div id="imageModal" class="modal">
            <span class="close">&times;</span>
            <img class="modal-content" id="modalImage">
        </div>

        <div class="price-details">
            <h2>تفاصيل التكلفة</h2>
            <?php if (isset($_SESSION['success_message'])): ?>
                <p><?= $_SESSION['success_message'] ?></p>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>


            <form method="POST" action="" class="price-form">

                <!-- عرض بيانات الأسعار -->
                <label for="consultation_price">سعر الاستشارة:</label>
                <input type="number" id="consultation_price" name="consultation_price" step="0.01"
                    value="<?= htmlspecialchars($patient['consultation_price'] ?? 0) ?>">
                <label for="consultation_notes">تفاصيل الاستشارة:</label>
                <input type="text" id="consultation_notes" name="consultation_notes"
                    value="<?= htmlspecialchars($patient['consultation_notes'] ?? '') ?>">

                <label for="injection_price">سعر الحقن:</label>
                <input type="number" id="injection_price" name="injection_price" step="0.01"
                    value="<?= htmlspecialchars($patient['injection_price'] ?? 0) ?>">
                <label for="injection_notes">تفاصيل الحقن:</label>
                <input type="text" id="injection_notes" name="injection_notes"
                    value="<?= htmlspecialchars($patient['injection_notes'] ?? '') ?>">

                <label for="medicine_price">سعر المستلزمات:</label>
                <input type="number" id="medicine_price" name="medicine_price" step="0.01"
                    value="<?= htmlspecialchars($patient['medicine_price'] ?? 0) ?>">
                <label for="medicine_notes">تفاصيل المستلزمات:</label>
                <input type="text" id="medicine_notes" name="medicine_notes"
                    value="<?= htmlspecialchars($patient['medicine_notes'] ?? '') ?>">

                <label for="xray_price">سعر صورة الأشعة:</label>
                <input type="number" id="xray_price" name="xray_price" step="0.01"
                    value="<?= htmlspecialchars($patient['xray_price'] ?? 0) ?>">
                <label for="xray_notes">تفاصيل الأشعة:</label>
                <input type="text" id="xray_notes" name="xray_notes"
                    value="<?= htmlspecialchars($patient['xray_notes'] ?? '') ?>">

                <label for="test_price">سعر الفحوصات:</label>
                <input type="number" id="test_price" name="test_price" step="0.01"
                    value="<?= htmlspecialchars($patient['test_price'] ?? 0) ?>">
                <label for="test_notes">تفاصيل الفحوصات:</label>
                <input type="text" id="test_notes" name="test_notes"
                    value="<?= htmlspecialchars($patient['test_notes'] ?? '') ?>">

                <label for="prescription_price">سعر الروشتة:</label>
                <input type="number" id="prescription_price" name="prescription_price" step="0.01"
                    value="<?= htmlspecialchars($patient['prescription_price'] ?? 0) ?>">
                <label for="prescription_notes">تفاصيل الروشتة:</label>
                <input type="text" id="prescription_notes" name="prescription_notes"
                    value="<?= htmlspecialchars($patient['prescription_notes'] ?? '') ?>">

                <button type="submit">حفظ التحديثات</button>
            </form>

            <h3>إجمالي التكلفة: <?= htmlspecialchars($patient['total_price'] ?? 0) ?> جنيه</d>
                </p>
        </div>
        <script src="../script/script.js"></script>
</body>

</html>