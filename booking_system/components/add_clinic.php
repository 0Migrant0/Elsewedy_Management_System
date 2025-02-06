<?php
require_once '../../manegment_system/components/auth.php';
require_once '../../manegment_system/components/db.php';


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
        $governorate = $_POST['governorate']; // Governorate name
        $city = $_POST['city']; // City name
        $name = $_POST['name'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];

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
?>

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة عيادة</title>
    <link rel="stylesheet" href="../styles/styles.css"> <!-- تأكد من أن اسم الملف صحيح -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <header>
        <nav>
            <div>
                <a href="../../manegment_system/index.php"><i class="fas fa-home"></i> الرئيسية</a>
                <a href="../../manegment_system/components/add_patient.php"><i class="fas fa-user-plus"></i> إضافة
                    مريض</a>
                <a href="../dashboard.php"><i class="fas fa-calendar-alt"></i> الحجوزات</a>
                <a href="../index.php"><i class="fas fa-calendar-check"></i> حجز موعد</a>
            </div>
            <div>
                <a href="components/logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
            </div>
        </nav>
    </header>

    <div class="add-clinic-container">
        <h1>إضافة عيادة</h1>

        <form action="add_clinic.php" method="post">
            <!-- حقل المحافظة -->
            <label for="governorate">المحافظة</label>
            <select id="governorate" name="governorate" required>
                <option value="">اختر محافظة</option>
            </select>

            <!-- حقل المدينة -->
            <label for="city">المدينة</label>
            <select id="city" name="city" required>
                <option value="">اختر مدينة</option>
            </select>

            <!-- حقل اسم العيادة -->
            <label for="name">اسم العيادة</label>
            <input type="text" name="name" id="name" required>

            <!-- حقل وقت البدء -->
            <label for="start_time">وقت البدء</label>
            <input type="time" name="start_time" id="start_time" required>

            <!-- حقل وقت الانتهاء -->
            <label for="end_time">وقت الانتهاء</label>
            <input type="time" name="end_time" id="end_time" required>

            <!-- أيام العمل -->
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

            <!-- زر إضافة العيادة -->
            <input class="add-clinic-btn" type="submit" value="إضافة عيادة">
        </form>
    </div>
    <script>
        // Fetch data from the server
        fetch('add_clinic.php?fetch_data=true')
            .then(response => response.json())
            .then(data => {
                const governorates = data.governorates;
                const cities = data.cities;

                // Populate governorate dropdown
                const governorateSelect = document.getElementById('governorate');
                governorates.forEach(gov => {
                    const option = document.createElement('option');
                    option.value = gov.name; // Use the governorate name as the value
                    option.textContent = gov.name; // Display the governorate name
                    governorateSelect.appendChild(option);
                });

                // Populate city dropdown based on selected governorate
                governorateSelect.addEventListener('change', function () {
                    const selectedGovernorateName = governorateSelect.value; // Get the selected governorate name

                    // Clear previous options
                    const citySelect = document.getElementById('city');
                    citySelect.innerHTML = '<option value="">اختر مدينة</option>';

                    if (selectedGovernorateName) {
                        // Filter cities by the selected governorate name
                        const filteredCities = cities.filter(city => {
                            const governorateName = governorates.find(gov => gov.id === city.governorate_id)?.name;
                            return governorateName === selectedGovernorateName;
                        });
                        filteredCities.forEach(city => {
                            const option = document.createElement('option');
                            option.value = city.name; // Use the city name as the value
                            option.textContent = city.name; // Display the city name
                            citySelect.appendChild(option);
                        });
                    }
                });
            })
            .catch(error => console.error('Error fetching data:', error)); F
    </script>
</body>

</html>