<?php

require_once 'components/auth.php';
require_once 'components/db.php';

// معالجة تعديل الحالة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $patient_id_to_update = trim($_POST['update_status']);
    $new_status = trim($_POST['status']);
    if (!empty($patient_id_to_update) && !empty($new_status)) {
        $stmt = $pdo->prepare("UPDATE patients SET status = :status WHERE id = :id");
        $stmt->execute(['status' => $new_status, 'id' => $patient_id_to_update]);

        // تخزين رسالة النجاح
        $_SESSION['update_message'] = "تم تحديث الحالة بنجاح.";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// معالجة طلب الحذف بناءً على رقم id
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_patient'])) {
    $patient_id_to_delete = trim($_POST['delete_patient']);
    if (!empty($patient_id_to_delete)) {
        $stmt = $pdo->prepare("DELETE FROM patients WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $patient_id_to_delete]);

        // تخزين رسالة الحذف
        $_SESSION['delete_message'] = "تم حذف المريض بنجاح.";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}
// المتغيرات الافتراضية
$name_query = trim($_POST['name_query'] ?? '');
$medical_id_query = trim($_POST['medical_id_query'] ?? '');
$date_query = trim($_POST['date_query'] ?? '');
$show_all = isset($_POST['show_all']) ? true : false;

// بناء الاستعلام
$query = "SELECT * FROM patients WHERE 1";
$params = [];

if (!$show_all) { // تطبيق التصفية فقط إذا لم يتم النقر على زر "عرض جميع المرضى"
    if (!empty($name_query)) {
        $query .= " AND name LIKE :name_query";
        $params['name_query'] = "%$name_query%";
    }

    if (!empty($medical_id_query)) {
        $query .= " AND medical_id LIKE :medical_id_query";
        $params['medical_id_query'] = "%$medical_id_query%";
    }

    if (!empty($date_query)) {
        $query .= " AND created_at LIKE :date_query";
        $params['date_query'] = "%$date_query%";
    }
}

// ترتيب النتائج
$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>قائمة المرضى</title>
</head>

<body>
    <header>
        <nav>
            <div class="menu-toggle" onclick="toggleMenu()">
                <i class="fas fa-bars"></i>
            </div>
            <div class="menu">
                <a href="index.php"><i class="fas fa-home"></i> الرئيسية</a>
                <a href="components/add_patient.php"><i class="fas fa-user-plus"></i> إضافة مريض</a>
                <a href="../booking_system/dashboard.php"><i class="fas fa-calendar-alt"></i> الحجوزات</a>
                <a href="../index.php"><i class="fas fa-calendar-check"></i> حجز موعد</a>
                <a href="components/logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
            </div>
        </nav>
    </header>

    <div class="container dashboard">
        <h1>قائمة المرضى</h1>

        <!-- عرض الرسائل -->
        <?php
        if (isset($_SESSION['update_message'])) {
            echo "<script>alert('" . $_SESSION['update_message'] . "');</script>";
            unset($_SESSION['update_message']);
        }
        if (isset($_SESSION['delete_message'])) {
            echo "<script>alert('" . $_SESSION['delete_message'] . "');</script>";
            unset($_SESSION['delete_message']);
        }
        ?>
        <form class="patient-filter" method="POST" action="">
            <div>
                <label for="name_query">بحث بالاسم:</label>
                <input type="text" id="name_query" name="name_query" value="<?= htmlspecialchars($name_query) ?>"
                    placeholder="اسم المريض">
            </div>
            <div>
                <label for="medical_id_query">بحث بالرقم المرضي:</label>
                <input type="text" id="medical_id_query" name="medical_id_query"
                    value="<?= htmlspecialchars($medical_id_query) ?>" 
                    placeholder="الرقم المرضي"
                    oninput="convertToEnglishDigits(this)"
                    onpaste="handlePaste(event)">
            </div>

            <script>
                // تحويل الأرقام أثناء الكتابة
                function convertToEnglishDigits(input) {
                    const arabicDigits = /[\u0660-\u0669]/g; // أرقام عربية ٠١٢٣٤٥٦٧٨٩
                    const persianDigits = /[\u06F0-\u06F9]/g; // أرقام فارسية ۰۱۲۳۴۵۶۷۸۹

                    input.value = input.value
                        .replace(arabicDigits, d => String.fromCharCode(d.charCodeAt(0) - 0x0630)) // ← عربي إلى إنجليزي
                        .replace(persianDigits, d => String.fromCharCode(d.charCodeAt(0) - 0x06F0)); // ← فارسي إلى إنجليزي
                }

                // دعم اللصق (onpaste)
                function handlePaste(event) {
                    event.preventDefault();
                    const pastedText = event.clipboardData.getData('text');

                    const convertedText = pastedText
                        .replace(/[\u0660-\u0669]/g, d => String.fromCharCode(d.charCodeAt(0) - 0x0630))
                        .replace(/[\u06F0-\u06F9]/g, d => String.fromCharCode(d.charCodeAt(0) - 0x06F0));

                    document.execCommand('insertText', false, convertedText);
                }
            </script>
            <div>
                <label for="date_query">بحث بالتاريخ:</label>
                <input type="date" id="date_query" name="date_query" value="<?= htmlspecialchars($date_query) ?>">
            </div>
            <div>
                <button type="submit">
                    <i class="fas fa-search"></i> بحث
                </button>
                <button type="submit" name="show_all" value="1">
                    <i class="fas fa-eye"></i> عرض جميع المرضى
                </button>
            </div>
        </form>

        <table>
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>الرقم المرضي</th>
                    <th>رقم الهاتف</th>
                    <th>الحالة</th>
                    <th>شركة التعاقد</th>
                    <th>التخصص الدقيق</th>
                    <th>التاريخ</th>
                    <th>عرض</th>
                    <!-- <th>حذف</th> -->
                </tr>
            </thead>
            <tbody>
                <?php if (count($patients) > 0): ?>
                    <?php foreach ($patients as $patient): ?>
                        <tr>
                            <td data-label="الاسم"><?= htmlspecialchars($patient['name']) ?></td>
                            <td data-label="الرقم المرضي"><?= htmlspecialchars($patient['medical_id']) ?></td>
                            <td data-label="رقم الهاتف"><?= htmlspecialchars($patient['phone']) ?></td>
                            <td data-label="الحالة">
                                <form method="POST" action="">
                                    <select name="status" required>
                                        <option value="كشف" <?= $patient['status'] === 'كشف' ? 'selected' : '' ?>>كشف</option>
                                        <option value="مراجعة" <?= $patient['status'] === 'مراجعة' ? 'selected' : '' ?>>مراجعة</option>
                                    </select>
                                    <button type="submit" name="update_status" value="<?= htmlspecialchars($patient['id']) ?>">
                                        <i class="fas fa-sync-alt"></i> تحديث
                                    </button>
                                </form>
                            </td>
                            <td data-label="شركة التعاقد"><?= htmlspecialchars($patient['contract'] ?? 'غير محدد') ?></td>
                            <td data-label="التخصص الدقيق">
                                <p><?= htmlspecialchars($patient['specialization'] ?? 'غير محدد') ?></p>
                            </td>
                            <td data-label="التاريخ"><?= htmlspecialchars($patient['created_at']) ?></td>
                            <td data-label="تعديل">
                                <a href="components/view_patient.php?id=<?= htmlspecialchars($patient['id']) ?>">عرض</a>
                            </td>
                            <!-- زر الحذف -->
                            <!-- <td data-label="حذف">
                                <form method="POST" action="" onsubmit="return confirm('هل أنت متأكد أنك تريد حذف هذا المريض؟');">
                                    <button type="submit" name="delete_patient" value="<?= htmlspecialchars($patient['id']) ?>">
                                        <i class="fas fa-trash-alt"></i> حذف
                                    </button>
                                </form>
                            </td> -->
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10">لا توجد نتائج مطابقة.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <script src="script/script.js"></script>
</body>

</html>