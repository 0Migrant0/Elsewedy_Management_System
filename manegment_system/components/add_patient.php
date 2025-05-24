<?php
require_once 'auth.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // تطهير والتحقق من المدخلات
    $name = htmlspecialchars(trim($_POST['name']));
    $medical_id = htmlspecialchars(trim($_POST['medical_id']));
    $phone = htmlspecialchars(trim($_POST['phone']));
    $status = htmlspecialchars(trim($_POST['status']));
    $notes = htmlspecialchars(trim($_POST['notes']));
    $diagnosis = htmlspecialchars(trim($_POST['diagnosis']));
    $contract = htmlspecialchars(trim($_POST['contract']));
    $clinic_id = htmlspecialchars(trim($_POST['clinic_id']));
    $specialization = htmlspecialchars(trim($_POST['specialization']));
    // $specialization = isset($_POST['specialization']) ? htmlspecialchars(trim($_POST['specialization'])) : null;

    $current_date = date('Y-m-d');
    // جلب اسم العيادة بناءً على الـ ID
    try {
        $stmt = $pdo->prepare("SELECT name FROM clinics WHERE id = ?");
        $stmt->execute([$clinic_id]);
        $clinic = $stmt->fetch(PDO::FETCH_ASSOC);
        $clinic_name = $clinic ? $clinic['name'] : 'غير محدد';
    } catch (PDOException $e) {
        echo "خطأ في استرجاع اسم العيادة: " . $e->getMessage();
    }

    // تعيين قيمة افتراضية للتعاقد إذا كانت فارغة
    if (empty($contract)) {
        $contract = "لا";
    }

    // التحقق من الحقول الإلزامية
    if (empty($name) || empty($medical_id) || empty($phone) || empty($clinic_id) || empty($specialization)) {
        echo "الاسم، الرقم المرضي، ورقم الهاتف، والعيادة،والتخصص الدقيق مطلوبين.";
        exit;
    }

    // Process file uploads
    $upload_dir = '../uploads/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $uploaded_files = [];
    foreach (['xray_images', 'test_files', 'prescriptions', 'file_path'] as $file_input_name) {
        if (isset($_FILES[$file_input_name]) && is_array($_FILES[$file_input_name]['name'])) {
            // Ensure the medical_id directory is included in the path
            $file_type_dir = $upload_dir . $file_input_name . '/' . $current_date . '/' . $medical_id . '/';
            if (!file_exists($file_type_dir)) {
                // Create directories recursively
                if (!mkdir($file_type_dir, 0777, true) && !is_dir($file_type_dir)) {
                    die("Failed to create directory: $file_type_dir");
                }
            }


            foreach ($_FILES[$file_input_name]['name'] as $key => $file_name) {
                if (!empty($file_name)) {
                    $tmp_name = $_FILES[$file_input_name]['tmp_name'][$key];
                    $new_file_name = $file_type_dir . uniqid() . "_" . basename($file_name);
                    if (move_uploaded_file($tmp_name, $new_file_name)) {
                        $uploaded_files[$file_input_name][] = $new_file_name;
                    } else {
                        echo "فشل في تحميل الملف: $file_name";
                    }
                }
            }
        }
    }


    // حفظ بيانات المريض في قاعدة البيانات
    try {
        // إدخال بيانات المريض
        $stmt = $pdo->prepare(
            "INSERT INTO patients (name, medical_id, phone, status, notes, diagnosis, xray_images, test_files, prescriptions,file_path,contract, clinic_id, clinic_name,specialization) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?)"
        );
        $stmt->execute([
            $name,
            $medical_id,
            $phone,
            $status,
            $notes,
            $diagnosis,
            json_encode($uploaded_files['xray_images'] ?? []),
            json_encode($uploaded_files['test_files'] ?? []),
            json_encode($uploaded_files['prescriptions'] ?? []),
            json_encode($uploaded_files['file_path'] ?? []),
            $contract,
            $clinic_id,
            $clinic_name,// حفظ اسم العيادة في قاعدة البيانات
            $specialization
        ]);

        // إعادة التوجيه إلى رسالة النجاح
        header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1');
        exit;

    } catch (PDOException $e) {
        echo "خطأ أثناء إضافة المريض: " . $e->getMessage();
    }
}

// جلب العيادات للاختيار منها
try {
    $stmt = $pdo->prepare("SELECT id, name FROM clinics");
    $stmt->execute();
    $clinics = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "خطأ في استرجاع العيادات: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة مريض</title>
    <link rel="stylesheet" href="../styles/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
                <a href="components/logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
            </div>
        </nav>
    </header>

    <div class="add-patient_container">
        <h2>إضافة مريض جديد</h2>

        <?php
        if (isset($_GET['success']) && $_GET['success'] == 1) {
            echo "<div id='successAlert' style='
        position: fixed; 
        top: -50px; /* Start position above the screen */
        left: 50%; 
        transform: translateX(-50%); 
        padding: 15px; 
        background-color: green; 
        color: white; 
        font-weight: bold; 
        border-radius: 5px; 
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); 
        z-index: 9999;
        opacity: 0; /* Start with zero opacity */
        animation: slideDown 0.5s forwards, fadeOut 0.5s 2.5s forwards; /* Slide down and then fade out */
    '>تم إضافة المريض بنجاح!</div>";
        } else if (isset($_GET['error']) && $_GET['error'] == 1) {
            echo "<div id='errorAlert' style='
                position: fixed; 
                top: -50px; /* Start position above the screen */
                left: 50%; 
                transform: translateX(-50%); 
                padding: 15px; 
                background-color: red; 
                color: white; 
                font-weight: bold; 
                border-radius: 5px; 
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); 
                z-index: 9999;
                opacity: 0; /* Start with zero opacity */
                animation: slideDown 0.5s forwards, fadeOut 0.5s 2.5s forwards; /* Slide down and then fade out */
            '>حدث خطأ! لم يتم إضافة المريض.</div>";
        }
        ?>

        <form id="patientForm" action="add_patient.php" method="POST" enctype="multipart/form-data">
            <div>
                <label for="name">اسم المريض:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div>
                <label for="medical_id">الرقم المرضي:</label>
                <input type="text" id="medical_id" name="medical_id" required oninput="convertToEnglishDigits(this)">
            </div>

            <script>
                function convertToEnglishDigits(input) {
                    // خريطة للأرقام العربية والفارسية إلى الإنجليزية
                    const arabicDigits = /[\u0660-\u0669]/g; // ٠١٢٣٤٥٦٧٨٩
                    const persianDigits = /[\u06F0-\u06F9]/g; // ۰۱۲۳۴۵۶۷۸۹

                    input.value = input.value.replace(arabicDigits, d => String.fromCharCode(d.charCodeAt(0) - 0x0630))
                                            .replace(persianDigits, d => String.fromCharCode(d.charCodeAt(0) - 0x06F0));
                }
            </script>
            <div>
                <label for="phone">رقم الهاتف:</label>
                <input type="text" id="phone" name="phone" required>
            </div>

            <div>
                <label for="diagnosis">التشخيص:</label>
                <textarea id="diagnosis" name="diagnosis"></textarea>
            </div>
            <div>
                <label for="notes">الملاحظات:</label>
                <textarea id="notes" name="notes"></textarea>
            </div>
            <div>
                <label for="contract">تعاقد:</label>
                <input type="text" id="contract" name="contract"
                    placeholder="اسم الشركة (اتركه فارغًا إذا لم يكن هناك شركة)">
            </div>
            <div>
                <!-- اختيار العيادة -->
                <label for="clinic_id">العيادة:</label>
                <select id="clinic_id" name="clinic_id" required>
                    <option value="">اختار العيادة</option>
                    <?php foreach ($clinics as $clinic): ?>
                        <option value="<?= htmlspecialchars($clinic['id']) ?>"><?= htmlspecialchars($clinic['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="status">الحالة:</label>
                <select id="status" name="status" required>
                    <option value="كشف">كشف</option>
                    <option value="مراجعة">مراجعة</option>
                </select>
            </div>
            <div>
                <label for="specialization">التخصص الدقيق:</label>
                <select id="specialization" name="specialization" required>
                    <option value="">اختر التخصص</option>
                    <option value="كسور"> كسور </option>
                    <option value="أطفال">أطفال </option>
                    <option value="مفاصل">مفاصل </option>
                    <option value="طب رياضي">طب رياضي </option>
                    <option value="أطفال CP "> أطفال CP </option>
                </select>
            </div>
            <div>
                <label for="xray_images">صور الأشعة:</label>
                <input type="file" id="xray_images" name="xray_images[]" multiple>
            </div>
            <div>
                <label for="test_files">ملفات التحاليل:</label>
                <input type="file" id="test_files" name="test_files[]" multiple>
            </div>
            <div>
                <label for="prescriptions">الوصفات الطبية:</label>
                <input type="file" id="prescriptions" name="prescriptions[]" multiple>
            </div>
            <div>
                <label for="file_path">الملفات:</label>
                <input type="file" id="file_path" name="file_path[]" multiple>
            </div>
            <button type="submit"><i class="fas fa-user-plus"></i> إضافة المريض</button>
        </form>
    </div>

    <script src="../script/script.js">
        setTimeout(function () {
            var alert = document.getElementById('successAlert');
            if (alert) {
                alert.style.display = 'none';
            }
            const url = new URL(window.location);
            url.searchParams.delete('success');
            url.searchParams.delete('error');
            window.history.replaceState({}, document.title, url.toString());
        }, 2000);
    </script>
</body>

</html>