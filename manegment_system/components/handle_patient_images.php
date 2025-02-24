<?php
require_once 'auth.php';
require_once 'db.php';

// الحصول على معرف المريض من سلسلة الاستعلام
$patient_id = $_GET['id'] ?? null;

if (!$patient_id) {
    die("معرف المريض غير صالح.");
}

// جلب تفاصيل المريض
$stmt = $pdo->prepare("SELECT * FROM patients WHERE id = :id");
$stmt->execute(['id' => $patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    die("لم يتم العثور على المريض.");
}

// معالجة تحميل الصورة/الملف
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {

    // تعريف دليل التحميل الأساسي
    $upload_dir = '../uploads/';

    // تحديد العمود بناءً على نوع الصورة
    $image_type = $_POST['image_type'] ?? 'other';
    $column = '';
    if ($image_type === 'xray') {
        $column = 'xray_images';
    } elseif ($image_type === 'test') {
        $column = 'test_files';
    } elseif ($image_type === 'prescription') {
        $column = 'prescriptions';
    } else {
        $column = 'file_path';
    }

    // التأكد من تضمين دليل medical_id في المسار
    $current_date = date('Y-m-d'); // الحصول على التاريخ الحالي
    $medical_id = $patient['medical_id'] ?? 'default'; // استخدام القيمة الافتراضية إذا لم يتم تعيين medical_id

    // إنشاء مسار الدليل بدون تكرار "uploads/"
    $file_type_dir = $upload_dir . $column . '/' . $current_date . '/' . $medical_id . '/';

    // إنشاء الأدلة بشكل متكرر إذا لم تكن موجودة
    if (!file_exists($file_type_dir)) {
        if (!mkdir($file_type_dir, 0777, true) && !is_dir($file_type_dir)) {
            die("فشل في إنشاء الدليل: $file_type_dir");
        }
    }

    // إنشاء اسم ملف فريد ونقل الملف المحمل
    $file_name = basename($_FILES['image']['name']);
    $file_path_relative = $column . '/' . $current_date . '/' . $medical_id . '/' . uniqid() . '_' . $file_name; // المسار النسبي لتخزينه في قاعدة البيانات
    $file_path_full = $upload_dir . $file_path_relative; // المسار الكامل لنظام الملفات

    if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path_full)) {

        // إضافة "../" إلى المسار النسبي قبل تخزينه في قاعدة البيانات
        $db_file_path = '../uploads/' . $file_path_relative;

        // استرجاع مسارات الملفات الموجودة من قاعدة البيانات
        $existing_files_str = $patient[$column] ?? '[]'; // الافتراضي إلى مصفوفة JSON فارغة إذا لم تكن هناك ملفات موجودة
        $existing_files = json_decode($existing_files_str, true); // فك تشفير سلسلة JSON إلى مصفوفة

        // التأكد من أن $existing_files هي مصفوفة
        if (!is_array($existing_files)) {
            $existing_files = [];
        }

        // إضافة مسار الملف الجديد إلى القائمة
        $existing_files[] = $db_file_path;

        // تحويل مسارات الملفات مرة أخرى إلى سلسلة JSON مشفرة
        $new_files = json_encode(array_values($existing_files)); // إعادة فهرسة المصفوفة وترميزها كـ JSON

        // تحديث العمود المقابل في قاعدة البيانات
        try {
            $stmt = $pdo->prepare("UPDATE patients SET $column = :file_paths WHERE id = :id");
            if ($stmt->execute(['file_paths' => $new_files, 'id' => $patient_id])) {
                echo "تم تحديث قاعدة البيانات بنجاح.<br>";
                $_SESSION['success_message'] = "تم تحميل الصورة/الملف بنجاح.";
            } else {
                echo "فشل تحديث قاعدة البيانات: " . $stmt->errorInfo()[2] . "<br>";
            }
        } catch (\Exception $e) {
            echo "خطأ في قاعدة البيانات: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "فشل في نقل الملف المحمل.<br>";
        $_SESSION['error_message'] = "فشل في تحميل الصورة/الملف.";
    }

    header("Location: handle_patient_images.php?id=" . $patient_id);
    exit;
}

if (isset($_GET['delete_image'])) {
    $image_path = $_GET['delete_image'];
    $image_type = $_GET['type'];

    // تحديد العمود بناءً على نوع الصورة
    $column = '';
    if ($image_type === 'xray') {
        $column = 'xray_images';
    } elseif ($image_type === 'test') {
        $column = 'test_files';
    } elseif ($image_type === 'prescription') {
        $column = 'prescriptions';
    } else {
        $column = 'file_path';
    }

    // إزالة الملف من الخادم
    if (file_exists($image_path)) {
        unlink($image_path);
    }

    // إزالة مسار الملف من قاعدة البيانات
    $existing_files = json_decode($patient[$column], true) ?? [];
    $existing_files = array_filter($existing_files, function ($file) use ($image_path) {
        return $file !== $image_path;
    });
    $new_files = json_encode(array_values($existing_files)); // إعادة فهرسة المصفوفة

    $stmt = $pdo->prepare("UPDATE patients SET $column = :file_paths WHERE id = :id");
    $stmt->execute([
        'file_paths' => $new_files,
        'id' => $patient_id
    ]);

    $_SESSION['success_message'] = "تم حذف الصورة/الملف بنجاح.";
    header("Location: handle_patient_images.php?id=" . $patient_id); // إعادة التوجيه لتحديث الصفحة
    exit;
} ?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة صور المريض</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 80%;
            margin: auto;
            overflow: hidden;
        }

        h1,
        h2,
        h3 {
            color: #333;
        }

        form {
            background: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        label {
            display: block;
            margin-bottom: 10px;
            color: #333;
        }

        input[type="file"],
        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #f5891e;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #c46e18;
        }

        .file-sections {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .file-section {
            flex: 1;
            min-width: 250px;
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .file-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 20px;
        }

        .file-item {
            text-align: center;
        }

        .file-item img {
            width: 100%;
            height: auto;
            display: block;
            margin-bottom: 10px;
            border-radius: 5px;
        }

        .file-item a {
            color: #f5891e;
            text-decoration: none;
            font-weight: bold;
        }

        .file-item a:hover {
            color: #c46e18;
        }

        .icon {
            margin-right: 5px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>إدارة ملفات المريض: <?= htmlspecialchars($patient['name']) ?></h1>

        <!-- نموذج التحميل -->
        <h2>تحميل صورة جديد</h2>
        <form action="" method="POST" enctype="multipart/form-data">
            <label for="image">اختصر صورة:</label>
            <input type="file" name="image" id="image" required><br><br>

            <label for="image_type">نوع الملف:</label>
            <select name="image_type" id="image_type">
                <option value="xray">صور الأشعة</option>
                <option value="test">ملفات التحاليل</option>
                <option value="prescription">الوصفات الطبية</option>
                <option value="file_path">الملفات</option>
            </select><br><br>

            <button type="submit" class="btn"><span class="icon">📤</span>تحميل</button>
        </form>

        <h2>الملفات الموجودة</h2>
        <div class="file-sections">
            <?php
            $base_url = "/Management_system/manegment_system/uploads/"; // تعريف عنوان URL الأساسي لمجلد التحميلات

            $columns = ['xray_images', 'test_files', 'prescriptions', 'file_path'];
            foreach ($columns as $column) {
                // فك تشفير مصفوفة JSON من قاعدة البيانات
                $files = json_decode($patient[$column], true) ?? [];
                switch ($column) {
                    case 'xray_images':
                        $column_name = 'صور الأشعة';
                        break;
                    case 'prescriptions':
                        $column_name = 'الوصفات الطبية';
                        break;
                    case 'test_files':
                        $column_name = 'ملفات التحاليل';
                        break;
                    default:
                        $column_name = 'الملفات';
                        break;
                }
                echo "<div class='file-section'>";
                echo "<h3>" . $column_name . "</h3>";
                if ($files) {
                    echo "<div class='file-list'>";
                    foreach ($files as $file) {
                        // تحويل مسار الملف إلى عنوان URL يمكن الوصول إليه عبر الويب
                        $web_accessible_path = str_replace('../uploads/', $base_url, $file);

                        echo "<div class='file-item'>";
                        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $web_accessible_path)) {
                            echo "<img src='" . htmlspecialchars($web_accessible_path) . "'><br>";
                        } else {
                            echo "<p>الملف غير موجود: " . htmlspecialchars($web_accessible_path) . "</p>";
                        }
                        echo "<a href='?id=$patient_id&delete_image=" . urlencode($file) . "&type=" . urlencode($column) . "' onclick='return confirm(\"هل أنت متأكد؟\")'><span class='icon'>🗑️</span>حذف</a>";
                        echo "</div>";
                    }
                    echo "</div>";
                } else {
                    echo "<p>لايوجد صورٌ في $column_name.</p>";
                }
                echo "</div>";
            }
            ?>
        </div>
    </div>
</body>

</html>