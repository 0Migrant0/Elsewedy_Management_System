<?php
include 'auth.php'; // تأكد من إضافة ملف التحقق من الهوية
include 'db_connection.php'; // تأكد من إضافة ملف الاتصال بقاعدة البيانات

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    // مصفوفة لتخزين الأسماء العربية لأيام الأسبوع
    $days_arabic = [
        "Monday" => "الإثنين",
        "Tuesday" => "الثلاثاء",
        "Wednesday" => "الأربعاء",
        "Thursday" => "الخميس",
        "Friday" => "الجمعة",
        "Saturday" => "السبت",
        "Sunday" => "الأحد"
    ];

    // جمع الأيام المحددة وتحويلها إلى نص واحد مفصول بفواصل باللغة العربية
    if (isset($_POST['day_of_week'])) {
        $selected_days = $_POST['day_of_week'];
        $day_of_week = [];
        foreach ($selected_days as $day) {
            // تحويل اسم اليوم من الإنجليزية إلى العربية
            if (array_key_exists($day, $days_arabic)) {
                $day_of_week[] = $days_arabic[$day];
            }
        }
        $day_of_week = implode(",", $day_of_week);
    } else {
        $day_of_week = "";
    }

    $sql = "INSERT INTO clinics (name, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ssss", $name, $day_of_week, $start_time, $end_time);

        if ($stmt->execute()) {
            // عرض رسالة نجاح وإعادة توجيه
            echo "<script>alert('تم إضافة العيادة بنجاح!'); window.location.href = '../dashboard.php';</script>";
            exit(); // إنهاء السكربت بعد إعادة التوجيه
        } else {
            echo "حدث خطأ أثناء تنفيذ الاستعلام: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "خطأ في إعداد الاستعلام: " . $conn->error;
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة عيادة</title>
    <link rel="stylesheet" href="../styles/styles.css"> <!-- تأكد من أن اسم الملف صحيح -->
</head>

<body>

    <div class="add-clinic-container">
        <h1>إضافة عيادة</h1>

        <!-- زر العودة إلى لوحة التحكم -->
        <!-- <div class="back-button">
        <a href="../dashboard.php">الرجوع إلى لوحة التحكم</a>
    </div> -->

        <form action="add_clinic.php" method="post">
            <label for="name">اسم العيادة</label>
            <input type="text" name="name" id="name" required>

            <label for="start_time">وقت البدء</label>
            <input type="time" name="start_time" id="start_time" required>

            <label for="end_time">وقت الانتهاء</label>
            <input type="time" name="end_time" id="end_time" required>

            <label>أيام العمل</label>
            <div class="work-days-checkbox">
                <div><label>السبت</label><input type="checkbox" name="day_of_week[]" value="Saturday"></div>
                <div><label>الأحد</label><input type="checkbox" name="day_of_week[]" value="Sunday"></div>
                <div><label>الإثنين</label><input type="checkbox" name="day_of_week[]" value="Monday"></div>
                <div><label>الثلاثاء</label><input type="checkbox" name="day_of_week[]" value="Tuesday"></div>
                <div><label>الأربعاء</label><input type="checkbox" name="day_of_week[]" value="Wednesday"></div>
                <div><label>الخميس</label><input type="checkbox" name="day_of_week[]" value="Thursday"></div>
                <div><label>الجمعة</label><input type="checkbox" name="day_of_week[]" value="Friday"></div>
            </div>

            <input class="add-clinic-btn" type="submit" value="إضافة عيادة">
        </form>
    </div>

</body>

</html>