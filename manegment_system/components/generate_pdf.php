<?php
require_once __DIR__ . '../../../composer/vendor/autoload.php'; // Load mPDF library

use Mpdf\Mpdf;

session_start();
include 'db.php';
// Fetch patient ID from URL parameter
$patient_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$patient_id) {
    die("لم يتم تحديد مريض.");
}

// Query the database for patient details
$stmt = $pdo->prepare("SELECT * FROM patients WHERE id = :id");
$stmt->execute(['id' => $patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    die("لا توجد بيانات للمريض.");
}

// Start generating the PDF content
ob_start(); // Start output buffering
?>
<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <title>تفاصيل المريض</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            direction: rtl;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: right;
        }

        th {
            background-color: #f4f4f4;
        }

        img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>

<body>

    <h2>تفاصيل المريض</h2>

    <table>
        <tr>
            <th>الاسم</th>
            <td><?= htmlspecialchars($patient['name'] ?? 'غير معروف') ?></td>
        </tr>
        <tr>
            <th>رقم المريض</th>
            <td><?= htmlspecialchars($patient['medical_id'] ?? 'غير محدد') ?></td>
        </tr>
        <tr>
            <th>رقم الهاتف</th>
            <td><?= htmlspecialchars($patient['phone'] ?? 'غير محدد') ?></td>
        </tr>
        <tr>
            <th>العيادة</th>
            <td><?= htmlspecialchars($patient['clinic_name'] ?? 'غير محدد') ?></td>
        </tr>
        <tr>
            <th>التخصص الدقيق</th>
            <td><?= htmlspecialchars($patient['specialization'] ?? 'غير محدد') ?></td>
        </tr>
        <tr>
            <th>الحالة</th>
            <td><?= htmlspecialchars($patient['status'] ?? 'غير محدد') ?></td>
        </tr>
        <tr>
            <th>التعاقد</th>
            <td><?= htmlspecialchars($patient['contract'] ?? 'لا يوجد تعاقد') ?></td>
        </tr>
        <tr>
            <th>التشخيص</th>
            <td><?= htmlspecialchars($patient['diagnosis'] ?? 'لا يوجد تشخيص') ?></td>
        </tr>
        <tr>
            <th>الملاحظات</th>
            <td><?= htmlspecialchars($patient['notes'] ?? 'لا يوجد ملاحظات') ?></td>
        </tr>
    </table>

    <h3>تفاصيل التكلفة</h3>
    <table>
        <tr>
            <th>سعر الاستشارة</th>
            <td><?= htmlspecialchars($patient['consultation_price'] ?? 0) ?> جنيه</td>
        </tr>
        <tr>
            <th>تفاصيل الاستشارة</th>
            <td><?= htmlspecialchars($patient['consultation_notes'] ?? 'لا توجد تفاصيل') ?></td>
        </tr>
        <tr>
            <th>سعر الحقن</th>
            <td><?= htmlspecialchars($patient['injection_price'] ?? 0) ?> جنيه</td>
        </tr>
        <tr>
            <th>تفاصيل الحقن</th>
            <td><?= htmlspecialchars($patient['injection_notes'] ?? 'لا توجد تفاصيل') ?></td>
        </tr>
        <tr>
            <th>سعر المستلزمات</th>
            <td><?= htmlspecialchars($patient['medicine_price'] ?? 0) ?> جنيه</td>
        </tr>
        <tr>
            <th>تفاصيل المستلزمات</th>
            <td><?= htmlspecialchars($patient['medicine_notes'] ?? 'لا توجد تفاصيل') ?></td>
        </tr>
        <tr>
            <th>سعر صورة الأشعة</th>
            <td><?= htmlspecialchars($patient['xray_price'] ?? 0) ?> جنيه</td>
        </tr>
        <tr>
            <th>تفاصيل الأشعة</th>
            <td><?= htmlspecialchars($patient['xray_notes'] ?? 'لا توجد تفاصيل') ?></td>
        </tr>
        <tr>
            <th>سعر الفحوصات</th>
            <td><?= htmlspecialchars($patient['test_price'] ?? 0) ?> جنيه</td>
        </tr>
        <tr>
            <th>تفاصيل الفحوصات</th>
            <td><?= htmlspecialchars($patient['test_notes'] ?? 'لا توجد تفاصيل') ?></td>
        </tr>
        <tr>
            <th>سعر الروشتة</th>
            <td><?= htmlspecialchars($patient['prescription_price'] ?? 0) ?> جنيه</td>
        </tr>
        <tr>
            <th>تفاصيل الروشتة</th>
            <td><?= htmlspecialchars($patient['prescription_notes'] ?? 'لا توجد تفاصيل') ?></td>
        </tr>
        <tr>
            <th>إجمالي التكلفة</th>
            <td><?= htmlspecialchars($patient['total_price'] ?? 0) ?> جنيه</td>
        </tr>
    </table>

</body>

</html>
<?php

// Get the buffered content
$html = ob_get_clean();

// Initialize mPDF
$mpdf = new Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'orientation' => 'P',
]);

// Write the HTML content to the PDF
$mpdf->WriteHTML($html);

// Output the PDF to the browser
$mpdf->Output('PatientDetails_' . $patient['id'] . '.pdf', 'D'); // 'D' forces download
?>