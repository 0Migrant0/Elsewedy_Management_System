<?php
require_once 'manegment_system/components/db.php';
?>

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام حجز المواعيد</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="booking_system/styles/styles.css">
</head>

<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <ul class="nav-list">
            <li class="nav-item">
                <img src="booking_system/images/logo2.png" alt="Logo" class="logo">
                <a href="#" class="nav-link" id="about-doctor-btn">دكتور محمد أحمد فهمي السويدي</a>
            </li>
            <li class="nav-item">
                <a href="ticket.html" class="nav-link">مواعيدي</a>
            </li>
        </ul>
    </nav>

    <!-- Popup Container -->
    <div class="popup-container" id="popup">
        <div class="popup-content">
            <span class="close-btn" id="close-popup">&times;</span>
            <h2 class="popup-title">دكتور محمد أحمد فهمي السويدي</h2>
            <!-- <img src="images/about_doctor.png" alt="Dr. Mohamed Ahmed Fawzy" class="doctor-image"> -->
            <ul class="popup-text">
                <li>استشاري جراحة العظام وعظام الأطفال، يتميز دكتور محمد أحمد فهمي السويدي بخبرته الواسعة في مجال
                    الجراحة.</li>
                <li>حصل على بكالوريوس طب وجراحة من جامعة الأزهر - أسيوط عام 2002.</li>
                <li>حاصل على ماجستير جراحة العظام من جامعة عين شمس.</li>
                <li>حاصل على دبلوم إدارة المستشفيات من أكاديمية السادات للعلوم الإدارية.</li>
                <li>كما حصل على دبلوم متخصص في جراحة عظام الأطفال من المعهد القومي للحركة.</li>
            </ul>
        </div>
    </div>

    <div class="aside-handel">
        <div class="book-container">
            <h1 class="appoint-title">حجز موعد</h1>
            <form class="form-con" action="booking_system/components/book.php" method="post">
                <label for="client_name">اسم المريض</label>
                <input class="patient-name" type="text" name="client_name" id="client_name" required>

                <label for="clinic">اسم العيادة</label>
                <select class="clinic-select" id="clinic" name="clinic" onchange="fetchAvailableDays()" required>
                    <option value="">اختر العيادة</option>
                    <?php
                    $sql = "SELECT id, name FROM clinics";
                    $result = $pdo->query($sql);
                    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                        echo "<option value='{$row['id']}'>{$row['name']}</option>";
                    }
                    ?>
                </select>

                <label for="appointment_day">اليوم</label>
                <select class="day-select" id="appointment_day" name="day_of_week" onchange="updateDateAndFetchSlots()"
                    required>
                    <option value="">اختر اليوم</option>
                </select>

                <label for="appointment_date">تاريخ الموعد</label>
                <input class="date-input" type="text" id="appointment_date" name="appointment_date" readonly required>

                <label for="time_slot">الوقت</label>
                <select class="time-select" id="time_slot" name="time_slot" required>
                    <!-- سيتم ملء الفترات الزمنية المتاحة هنا -->
                </select>

                <label for="phone_number">رقم الهاتف</label>
                <input class="phone-number-input" type="text" name="phone_number" id="phone_number" required>

                <input class="button" type="submit" value="حجز موعد" class="submit-button">
            </form>
        </div>
        <?php
        // Fetch data from the database
        $sql = "SELECT id, name, day_of_week, start_time, end_time, city, governorate 
        FROM clinics
        ORDER BY governorate, city, name, day_of_week";

        $stmt = $pdo->query($sql);

        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Convert time to 12-hour format
            $start_time = date("g:i A", strtotime($row['start_time']));
            $end_time = date("g:i A", strtotime($row['end_time']));
            $start_time = str_replace(['AM', 'PM'], ['ص', 'م'], $start_time);
            $end_time = str_replace(['AM', 'PM'], ['ص', 'م'], $end_time);

            // Organize data by government, city, and clinic
            $data[$row['governorate']][$row['city']][] = [
                'clinic_name' => $row['name'],
                'day_of_week' => $row['day_of_week'],
                'start_time' => $start_time,
                'end_time' => $end_time,
            ];
        }
        ?>

        <!-- Generate HTML -->
        <aside class="date-plan">
            <?php foreach ($data as $government => $cities): ?>
                <div>
                    <h2><?= htmlspecialchars($government) ?></h2>
                    <?php foreach ($cities as $city => $clinics): ?>
                        <?php foreach ($clinics as $clinic): ?>
                            <p>
                                <?= htmlspecialchars($city) ?>:
                                <?= htmlspecialchars($clinic['clinic_name']) ?>
                                (<?= htmlspecialchars($clinic['day_of_week']) ?>)
                                من <span>(<?= htmlspecialchars($clinic['start_time']) ?>) -
                                    (<?= htmlspecialchars($clinic['end_time']) ?>)</span>
                            </p>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </aside>
    </div>

    <script>
        //navbar//
        // Select elements
        const aboutDoctorBtn = document.getElementById('about-doctor-btn');
        const popup = document.getElementById('popup');
        const closePopupBtn = document.getElementById('close-popup');

        // Open popup
        aboutDoctorBtn.addEventListener('click', () => {
            popup.style.display = 'flex';
        });

        // Close popup
        closePopupBtn.addEventListener('click', () => {
            popup.style.display = 'none';
        });

        // Close popup if user clicks outside of it
        window.addEventListener('click', (event) => {
            if (event.target === popup) {
                popup.style.display = 'none';
            }
        });

        //Days handel//
        const daysInArabic = ["الأحد", "الإثنين", "الثلاثاء", "الأربعاء", "الخميس", "الجمعة", "السبت"];
        const daysInEnglish = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

        function fetchAvailableDays() {
            const clinicId = document.getElementById('clinic').value;
            const appointmentDaySelect = document.getElementById('appointment_day');
            const appointmentDateField = document.getElementById('appointment_date');

            appointmentDaySelect.innerHTML = '<option value="">اختر اليوم</option>';

            if (clinicId) {
                const xhr = new XMLHttpRequest();
                xhr.open("GET", `booking_system/components/available_days.php?clinic_id=${clinicId}`, true);
                xhr.onload = function() {
                    if (this.status == 200) {

                        const days = JSON.parse(this.responseText);
                        days.forEach(day => {
                            const trimmedDay = day.trim(); // Trim the day name
                            appointmentDaySelect.innerHTML += `<option value="${trimmedDay}">${trimmedDay}</option>`;
                        });

                        // اختيار اليوم الأول وتحديث التاريخ تلقائيًا
                        if (days.length > 0) {
                            appointmentDaySelect.value = days[0].trim(); // Trim the first day name
                            updateDateAndFetchSlots(); // تحديث التاريخ حسب اليوم الأول تلقائيًا
                        }
                    } else {
                        console.error(`Failed to fetch available days. Status: ${this.status}`);
                    }
                };
                xhr.onerror = function() {
                    console.error('Request error...');
                };
                xhr.send();
            } else {
                console.error('Clinic ID is missing.');
            }
        }

        function updateDateAndFetchSlots() {
            const selectedDay = document.getElementById('appointment_day').value;
            const clinicId = document.getElementById('clinic').value;
            const appointmentDateField = document.getElementById('appointment_date');

            if (selectedDay && clinicId) {
                const today = new Date();
                let selectedDate = new Date();

                let selectedDayIndex = daysInArabic.indexOf(selectedDay);
                if (selectedDayIndex === -1) {
                    selectedDayIndex = daysInEnglish.indexOf(selectedDay);
                }

                if (selectedDayIndex === -1) {
                    appointmentDateField.value = ""; // إذا لم يتم العثور على اليوم
                    console.error("Selected day not found in both Arabic and English arrays.");
                    return;
                }

                let dayDifference = selectedDayIndex - today.getDay();
                if (dayDifference < 0) {
                    dayDifference += 7;
                }

                selectedDate.setDate(today.getDate() + dayDifference);

                const year = selectedDate.getFullYear();
                const month = String(selectedDate.getMonth() + 1).padStart(2, '0');
                const day = String(selectedDate.getDate()).padStart(2, '0');
                appointmentDateField.value = `${year}-${month}-${day}`;

                fetchAvailableSlots();
            } else {
                console.error("Selected day or clinic ID is missing.");
            }
        }

        function fetchAvailableSlots() {
            const dayOfWeek = document.getElementById('appointment_day').value;
            const clinicId = document.getElementById('clinic').value;
            const selectedDate = document.getElementById('appointment_date').value;

            if (dayOfWeek && clinicId && selectedDate) {
                const xhr = new XMLHttpRequest();
                xhr.open("GET", `booking_system/components/available_slots.php?day_of_week=${dayOfWeek}&clinic_id=${clinicId}&selected_date=${selectedDate}`, true);
                xhr.onload = function() {
                    if (this.status == 200) {
                        document.getElementById('time_slot').innerHTML = this.responseText;
                    }
                };
                xhr.send();
            }
        }
        // Function to validate phone number
        function validatePhoneNumber(phoneNumber) {
            const phonePattern = /^0[0-9]{10}$/; // Adjust the pattern to ensure the number starts with 0 and is 11 digits long
            return phonePattern.test(phoneNumber);
        }

        // Add event listener to the form submission
        document.querySelector('.form-con').addEventListener('submit', function(event) {
            const phoneNumberInput = document.getElementById('phone_number');
            const phoneNumber = phoneNumberInput.value;

            if (!validatePhoneNumber(phoneNumber)) {
                alert('يرجى إدخال رقم هاتف صحيح مكون من 11 رقمًا يبدأ بـ 0.');
                event.preventDefault(); // Prevent form submission
            }
        });
    </script>
</body>

</html>