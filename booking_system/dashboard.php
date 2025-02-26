<?php
require_once '../manegment_system/components/auth.php';
?>

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام حجز المواعيد - لوحة التحكم</title>
    <link rel="stylesheet" href="styles/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>

    <header>
        <nav>
            <div class="menu-toggle" onclick="toggleMenu()">
                <i class="fas fa-bars"></i>
            </div>
            <div class="menu">
                <a href="../manegment_system/index.php"><i class="fas fa-home"></i> الرئيسية</a>
                <a href="../manegment_system\components/add_patient.php"><i class="fas fa-user-plus"></i> إضافة
                    مريض</a>
                <a href="dashboard.php"><i class="fas fa-calendar-alt"></i> الحجوزات</a>
                <a href="../index.php"><i class="fas fa-calendar-check"></i> حجز موعد</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
            </div>
        </nav>
    </header>

    <div class="dashboard-container">
        <h1>نظام حجز المواعيد - لوحة التحكم</h1>

        <!-- زر إضافة عيادة -->
        <div class="add-clinic">
            <a href="components/add_clinic.php" class="add-clinic-button"><i class="fas fa-clinic-medical"></i> ادارة
                العيادات</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>رقم الحجز</th>
                    <th>اسم العيادة</th>
                    <th>اسم المريض</th>
                    <th>تاريخ ووقت الحجز</th>
                    <th>رقم الهاتف</th>
                    <th>تاريخ التسجيل</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php
                ob_start(); // بدء تخزين الإخراج
                require_once '../manegment_system/components/db.php';


                // استعلام للحصول على الحجوزات
                $sql = "SELECT a.id, a.client_name, a.appointment_datetime, a.phone_number, a.registration_date, a.status, c.name 
                    FROM appointments a 
                    JOIN clinics c ON a.clinic_id = c.id";
                $result = $pdo->query($sql);

                if ($result && $result->rowCount() > 0) {
                    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                        echo "<tr>
                        <td data-label='رقم الحجز'>{$row['id']}</td>
                        <td data-label='اسم العيادة'>{$row['name']}</td>
                        <td data-label='اسم المريض'>{$row['client_name']}</td>
                        <td data-label='تاريخ ووقت الحجز'>{$row['appointment_datetime']}</td>
                        <td data-label='رقم الهاتف'>{$row['phone_number']}</td>
                        <td data-label='تاريخ التسجيل'>{$row['registration_date']}</td>
                        <td data-label='الحالة'>{$row['status']}</td>
                        <td data-label='إجراءات' class='buttons'>
                            <form method='POST' style='display:inline-block;'>
                                <input type='hidden' name='id' value='{$row['id']}'>
                                <input type='hidden' name='action' value='approve'>
                                <button type='submit' class='prov-button approve'>تم الكشف</button>
                            </form>
                            <form method='POST' style='display:inline-block;'>
                                <input type='hidden' name='id' value='{$row['id']}'>
                                <input type='hidden' name='action' value='delete'>
                                <button type='submit' class='del-button delete'>حذف</button>
                            </form>
                        </td>
                    </tr>";
                    }
                } else {
                    echo "<tr><td colspan='8'>لا توجد حجوزات حالياً</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <?php
    // معالجة إجراءات الحجز
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $id = $_POST['id'];
        $action = $_POST['action'];

        if ($action == 'approve') {
            // تحديث الحالة إلى "تم الكشف"
            $update_sql = "UPDATE appointments SET status = 'تم الكشف' WHERE id = ?";
            $stmt = $pdo->prepare($update_sql);
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $stmt->execute();
            unset($stmt);
            header("Location: dashboard.php"); // إعادة تحميل الصفحة
            exit();
        } elseif ($action == 'delete') {
            // حذف الحجز
            $delete_sql = "DELETE FROM appointments WHERE id = ?";
            $stmt = $pdo->prepare($delete_sql);
            $stmt->bindParam(1, $id, PDO::PARAM_INT); // Use positional placeholder
            $stmt->execute();
            unset($stmt);
            header("Location: dashboard.php"); // إعادة تحميل الصفحة
            exit();
        }
    }

    unset($stmt);
    ob_end_flush(); // إنهاء تخزين الإخراج
    ?>
    <script>
        // toggle menu
        function toggleMenu() {
            const menu = document.querySelector('nav .menu');
            menu.classList.toggle('active');
        }
    </script>
</body>

</html>