<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../../manegment_system/components/auth.php';
require_once '../../manegment_system/components/db.php';

// session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// إذا كنت تريد أن تكون الصفحة متاحة للأدمن فقط
if ($_SESSION['admin_role'] !== 'admin') {
    die('ليس لديك صلاحية الوصول لهذه الصفحة.');
}

// --- START: Handling clinic deletion ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $clinic_id = $_POST['id'];

    try {
        // Begin transaction
        $pdo->beginTransaction();

        // أولًا: حذف الحجوزات المرتبطة بهذه العيادة
        $stmt = $pdo->prepare("DELETE FROM appointments WHERE clinic_id = ?");
        $stmt->execute([$clinic_id]);

        // ثانيًا: حذف العيادة نفسها
        $stmt = $pdo->prepare("DELETE FROM clinics WHERE id = ?");
        $stmt->execute([$clinic_id]);

        // إنهاء العملية بنجاح
        $pdo->commit();

        // إعادة توجيه المستخدم بعد الحذف
        header("Location: add_clinic.php");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack(); // التراجع إذا حدث خطأ
        echo "<script>alert('حدث خطأ أثناء محاولة الحذف: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        exit();
    }
}
// --- END: Handling clinic deletion ---

try {
    // Handle AJAX request to fetch governorates and cities
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_data'])) {
        // Fetch governorates
        $stmt = $pdo->query("SELECT id, name FROM governorates");
        $governorates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch cities
        $stmt = $pdo->query("SELECT id, name, governorate_id FROM cities");
        $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Return data as JSON
        header('Content-Type: application/json');
        echo json_encode(['governorates' => $governorates, 'cities' => $cities]);
        exit; // Stop further execution
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Retrieve form data
        $governorate_id = $_POST['governorate']; // Governorate ID
        $city_id = $_POST['city']; // City ID
        $name = $_POST['name'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];

        // Fetch governorate and city names based on their IDs
        $stmt = $pdo->prepare("SELECT name FROM governorates WHERE id = ?");
        $stmt->execute([$governorate_id]);
        $governorate = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT name FROM cities WHERE id = ?");
        $stmt->execute([$city_id]);
        $city = $stmt->fetchColumn();

        // Map English day names to Arabic
        $days_arabic = [
            "Monday" => "الإثنين",
            "Tuesday" => "الثلاثاء",
            "Wednesday" => "الأربعاء",
            "Thursday" => "الخميس",
            "Friday" => "الجمعة",
            "Saturday" => "السبت",
            "Sunday" => "الأحد"
        ];

        // Process selected days of the week
        $day_of_week = '';
        if (isset($_POST['day_of_week'])) {
            $selected_days = $_POST['day_of_week'];
            $translated_days = [];
            foreach ($selected_days as $day) {
                if (array_key_exists($day, $days_arabic)) {
                    $translated_days[] = $days_arabic[$day];
                }
            }
            $day_of_week = implode(", ", $translated_days);
        }

        // Insert data into the database
        $sql = "INSERT INTO clinics (governorate, city, name, start_time, end_time, day_of_week) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);

        if ($stmt) {
            $stmt->execute([$governorate, $city, $name, $start_time, $end_time, $day_of_week]);

            // Display success message and redirect
            echo "<script>alert('تم إضافة العيادة بنجاح!'); window.location.href = '';</script>";
            exit;
        } else {
            throw new Exception("خطأ في إعداد الاستعلام.");
        }
    }
} catch (PDOException $e) {
    // Handle database errors
    echo json_encode(['error' => 'حدث خطأ في قاعدة البيانات. يرجى المحاولة لاحقًا.', 'details' => $e->getMessage()]);
} catch (Exception $e) {
    // Handle general errors
    echo json_encode(['error' => 'حدث خطأ غير متوقع. يرجى المحاولة لاحقًا.', 'details' => $e->getMessage()]);
}

try {
    $stmt = $pdo->query("SELECT id, governorate, city, name, start_time, end_time, day_of_week FROM clinics");
    $clinics = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo json_encode(['error' => 'حدث خطأ في قاعدة البيانات أثناء جلب بيانات العيادات.', 'details' => $e->getMessage()]);
}
?>

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة عيادة</title>
    <link rel="stylesheet" href="../styles/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <header>
        <nav>
            <div class="menu-toggle" onclick="toggleMenu()">
                <i class="fas fa-bars"></i>
            </div>
            <div class="menu">
                <a href="../../manegment_system/index.php"><i class="fas fa-home"></i> الرئيسية</a>
                <a href="../../manegment_system/components/add_patient.php"><i class="fas fa-user-plus"></i> إضافة
                    مريض</a>
                <a href="../dashboard.php"><i class="fas fa-calendar-alt"></i> الحجوزات</a>
                <a href="../../index.php"><i class="fas fa-calendar-check"></i> حجز موعد</a>
                <a href="../../manegment_system/components/logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
            </div>
        </nav>
    </header>
    <div class="manage-clinic-container">
        <div class="clinics-table-container">
            <div class="add-clinic-container-header">
                <h2><i class="fas fa-clinic-medical"></i> إدارة العيادات</h2>
                <h2><a href="#add-clinic-container"><i class="fas fa-plus-circle"></i> إضافة عيادة</a></h2>
            </div>
            <table border="1" cellpadding="10" cellspacing="0">
                <thead>
                    <tr>
                        <th>المحافظة</th>
                        <th>المدينة</th>
                        <th>اسم العيادة</th>
                        <th>وقت البدء</th>
                        <th>وقت الانتهاء</th>
                        <th>أيام العمل</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($clinics)): ?>
                        <?php foreach ($clinics as $clinic): ?>
                            <tr data-id="<?= htmlspecialchars($clinic['id']) ?>">
                                <!-- Governorate -->
                                <td class="editable" data-field="governorate"><?= htmlspecialchars($clinic['governorate']) ?>
                                </td>
                                <!-- City -->
                                <td class="editable" data-field="city"><?= htmlspecialchars($clinic['city']) ?></td>
                                <!-- Clinic Name -->
                                <td class="editable" data-field="name"><?= htmlspecialchars($clinic['name']) ?></td>
                                <!-- Start Time -->
                                <td class="editable" data-field="start_time"><?= htmlspecialchars($clinic['start_time']) ?></td>
                                <!-- End Time -->
                                <td class="editable" data-field="end_time"><?= htmlspecialchars($clinic['end_time']) ?></td>
                                <!-- Days of the Week -->
                                <td class="editable" data-field="day_of_week"><?= htmlspecialchars($clinic['day_of_week']) ?>
                                </td>
                                <!-- Actions -->
                                <td>
                                    <div class="actions_handler">
                                        <button class="btn-edit">تعديل</button>
                                        <form action="add_clinic.php" method="POST" style="display:inline;">
    <input type="hidden" name="id" value="<?= $clinic['id'] ?>">
    <input type="hidden" name="action" value="delete">
    <button type="submit" class="btn-delete"
        onclick="return confirm('هل أنت متأكد من حذف هذه العيادة؟');"><i
            class="fas fa-trash-alt"></i></button>
</form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">لا توجد عيادات مضافة.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>


        <div class="add-clinic-container" id="add-clinic-container">
            <h2 class="add-clinic-container-title"> إضافة عيادة</h2>
            <form action="add_clinic.php" method="post">
                <!-- Governorate -->
                <label for="governorate">المحافظة</label>
                <select id="governorate" name="governorate" required>
                    <option value="">اختر محافظة</option>
                </select>

                <!-- City -->
                <label for="city">المدينة</label>
                <select id="city" name="city" required>
                    <option value="">اختر مدينة</option>
                </select>

                <!-- Clinic Name -->
                <label for="name">اسم العيادة</label>
                <input type="text" name="name" id="name" required>

                <!-- Start Time -->
                <label for="start_time">وقت البدء</label>
                <input type="time" name="start_time" id="start_time" required>

                <!-- End Time -->
                <label for="end_time">وقت الانتهاء</label>
                <input type="time" name="end_time" id="end_time" required>

                <!-- Days of the Week -->
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

                <!-- Submit Button -->
                <input class="add-clinic-btn" type="submit" value="إضافة عيادة">
            </form>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const governorateSelect = document.getElementById('governorate');
            const citySelect = document.getElementById('city');

            // Fetch governorates and cities from the server
            fetch('fetch_data.php?fetch_data=true')
                .then(response => response.json())
                .then(data => {
                    const { governorates, cities } = data;

                    // Populate governorate dropdown
                    governorates.forEach(governorate => {
                        const option = document.createElement('option');
                        option.value = governorate.id; // Use id instead of name
                        option.textContent = governorate.name;
                        governorateSelect.appendChild(option);
                    });

                    // Populate city dropdown based on selected governorate
                    governorateSelect.addEventListener('change', function () {
                        const selectedGovernorateId = governorateSelect.value;
                        citySelect.innerHTML = '<option value="">اختر مدينة</option>'; // Reset city dropdown
                        const filteredCities = cities.filter(city => city.governorate_id == selectedGovernorateId);
                        filteredCities.forEach(city => {
                            const option = document.createElement('option');
                            option.value = city.id; // Use id instead of name
                            option.textContent = city.name;
                            citySelect.appendChild(option);
                        });
                    });
                })
                .catch(error => console.error('Error fetching data:', error));
        });

        document.addEventListener('DOMContentLoaded', function () {
            const table = document.querySelector('.clinics-table-container table tbody');

            // Fetch governorates and cities from the server
            let governorates = [];
            let cities = [];
            fetch('fetch_data.php?fetch_data=true')
                .then(response => response.json())
                .then(data => {
                    governorates = data.governorates;
                    cities = data.cities;
                })
                .catch(error => console.error('Error fetching data:', error));

            // Handle Edit Button Click
            table.addEventListener('click', function (e) {
                const target = e.target;
                if (target.classList.contains('btn-edit')) {
                    const row = target.closest('tr');
                    const id = row.dataset.id;

                    // Convert all editable cells into input fields
                    Array.from(row.querySelectorAll('.editable')).forEach(cell => {
                        const field = cell.dataset.field;
                        const value = cell.textContent.trim();

                        if (field === 'governorate') {
                            // Populate governorate dropdown
                            cell.innerHTML = `<select name="governorate"></select>`;
                            const select = cell.querySelector('select');
                            governorates.forEach(gov => {
                                const option = document.createElement('option');
                                option.value = gov.name; // Use name instead of id
                                option.textContent = gov.name;
                                option.selected = gov.name === value;
                                select.appendChild(option);
                            });

                            // Add event listener to populate cities based on selected governorate
                            select.addEventListener('change', function () {
                                const cityCell = row.querySelector('[data-field="city"]');
                                const selectedGovernorateName = select.value;
                                cityCell.innerHTML = '<select name="city"></select>';
                                const citySelect = cityCell.querySelector('select');
                                const filteredCities = cities.filter(city => {
                                    const governorate = governorates.find(gov => gov.id === city.governorate_id);
                                    return governorate && governorate.name === selectedGovernorateName;
                                });
                                filteredCities.forEach(city => {
                                    const option = document.createElement('option');
                                    option.value = city.name; // Use name instead of id
                                    option.textContent = city.name;
                                    citySelect.appendChild(option);
                                });
                            });

                            // Trigger change event to populate cities initially
                            select.dispatchEvent(new Event('change'));
                        } else if (field === 'city') {
                            // City will be populated dynamically by the governorate dropdown
                        } else if (field === 'day_of_week') {
                            // Convert days of the week into checkboxes
                            const daysArabic = ["السبت", "الأحد", "الإثنين", "الثلاثاء", "الأربعاء", "الخميس", "الجمعة"];
                            const selectedDays = value.split(', ');
                            cell.innerHTML = `
                                <div class="edit-work-days">
                                    ${daysArabic.map(day => `
                                        <label>
                                            <input type="checkbox" name="${field}[]" value="${day}" ${selectedDays.includes(day) ? 'checked' : ''}>
                                            ${day}
                                        </label>
                                    `).join('')}
                                </div>
                            `;
                        } else if (field === 'start_time' || field === 'end_time') {
                            // Convert time fields into time inputs
                            cell.innerHTML = `<input type="time" name="${field}" value="${value}">`;
                        } else {
                            // Convert other fields into text inputs
                            cell.innerHTML = `<input type="text" name="${field}" value="${value}">`;
                        }
                    });

                    // Replace Edit Button with Save Button
                    target.textContent = 'حفظ';
                    target.classList.remove('btn-edit');
                    target.classList.add('btn-save');
                } else if (target.classList.contains('btn-save')) {
                    const row = target.closest('tr');
                    const id = row.dataset.id;

                    // Collect updated data from the row
                    const data = {};
                    Array.from(row.querySelectorAll('.editable')).forEach(cell => {
                        const field = cell.dataset.field;

                        if (field === 'day_of_week') {
                            // Collect checked days of the week
                            data[field] = Array.from(cell.querySelectorAll('input[type="checkbox"]:checked'))
                                .map(checkbox => checkbox.value)
                                .join(', ');
                        } else if (field === 'governorate' || field === 'city') {
                            // Collect values from dropdowns
                            const select = cell.querySelector('select');
                            data[field] = select ? select.value : '';
                        } else {
                            // Collect values from input fields
                            const input = cell.querySelector('input');
                            data[field] = input ? input.value : '';
                        }
                    });

                    // Send AJAX request to update the database
                    fetch('update_clinic.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id, ...data })
                    })
                        .then(response => response.json())
                        .then(result => {
                            if (result.success) {
                                alert('تم تحديث العيادة بنجاح!');
                                location.reload(); // Reload the page to reflect changes
                            } else {
                                alert('حدث خطأ أثناء تحديث العيادة.');
                            }
                        })
                        .catch(error => console.error('Error:', error));
                }
            });
        });

        // toggle menu
        function toggleMenu() {
            const menu = document.querySelector('nav .menu');
            menu.classList.toggle('active');
        }

    </script>
</body>

</html>