<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام حجز المواعيد</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>

<body>
    <div class="aside-handel">
        <div class="book-container">
            <h1 class="appoint-title">حجز موعد</h1>
            <form class="form-con" action="components/book.php" method="post">
                <label for="client_name">اسم المريض</label>
                <input class="patient-name" type="text" name="client_name" id="client_name" required>

                <label for="clinic">اسم العيادة</label>
                <select class="clinic-select" id="clinic" name="clinic" onchange="fetchAvailableDays()" required>
                    <option value="">اختر العيادة</option>
                    <?php
                    include 'components/db_connection.php';
                    $sql = "SELECT id, name FROM clinics";
                    $result = $conn->query($sql);
                    while ($row = $result->fetch_assoc()) {
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
        <aside class="date-plan">
            <div>
                <h2>محافظة القاهرة</h2>
                    <p>
                        حدائق القبة: مستشفى الفؤاد (السبت) من <span>(5 م&nbsp;) - (7 م&nbsp;)</span>
                    </p>
            </div>
            <div>
                <h2>محافظة الشرقية</h2>
                <p>
                    العاشر من رمضان: مستشفى الفؤاد (الأثنين و ألاربعاء) من <span>(4 م&nbsp;) - (10 م&nbsp;)</span>
                </p>
                <p>
                    العاشر من رمضان: مستشفى شرف (ألاربعاء) من <span>(10 ص&nbsp;) - (12 ص&nbsp;)</span>
                </p>
                <p>
                    بلبيس: حي الزهور (الأحد و الخميس) من <span>(8 ص&nbsp;) - (10 م&nbsp;)</span>
                </p>
                <p>
                    بلبيس: حي الزهور (الثلاثاء) من <span>(2 م&nbsp;) - (11 م&nbsp;)</span>
                </p>

                <p>
                    بلبيس: الكفر القديم (الجمعة) من <span>(7 ص&nbsp;) - (9 ص&nbsp;)</span>
                </p>
                <p>
                    بلبيس: غيته (الجمعة) من <span>(9 ص&nbsp;) - (11 ص&nbsp;)</span>
                </p>
                <p>
                    ابو حماد: مستشفى نبراس التخصصي (الجمعة) من <span>(4 م&nbsp;) - (6 م&nbsp;)</span>
                </p>
                <p>
                    ابو حماد: مركز سويد الطبي (الجمعة) من <span>(6 م&nbsp;) - (8 م&nbsp;)</span>
                </p>
            </div>
            <div>
                <h2>محافظة السويس</h2>
                <p>
                    شارع صلاح الدين: بجوار صيدليه صلاح الدين (الأثنين) من <span>(11 ص&nbsp;) - (1 م&nbsp;)</span>
                </p>
            </div>
        </aside>
    </div>

    <script>
        const daysInArabic = ["الأحد", "الإثنين", "الثلاثاء", "الأربعاء", "الخميس", "الجمعة", "السبت"];
        const daysInEnglish = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

        function fetchAvailableDays() {
            const clinicId = document.getElementById('clinic').value;
            const appointmentDaySelect = document.getElementById('appointment_day');
            const appointmentDateField = document.getElementById('appointment_date');

            appointmentDaySelect.innerHTML = '<option value="">اختر اليوم</option>';

            if (clinicId) {
                const xhr = new XMLHttpRequest();
                xhr.open("GET", `components/available_days.php?clinic_id=${clinicId}`, true);
                xhr.onload = function () {
                    if (this.status == 200) {
                        const days = JSON.parse(this.responseText);
                        days.forEach(day => {
                            appointmentDaySelect.innerHTML += `<option value="${day}">${day}</option>`;
                        });

                        // اختيار اليوم الأول وتحديث التاريخ تلقائيًا
                        if (days.length > 0) {
                            appointmentDaySelect.value = days[0];
                            updateDateAndFetchSlots(); // تحديث التاريخ حسب اليوم الأول تلقائيًا
                        }
                    }
                };
                xhr.send();
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
            }
        }

        function fetchAvailableSlots() {
            const dayOfWeek = document.getElementById('appointment_day').value;
            const clinicId = document.getElementById('clinic').value;
            const selectedDate = document.getElementById('appointment_date').value;

            if (dayOfWeek && clinicId && selectedDate) {
                const xhr = new XMLHttpRequest();
                xhr.open("GET", `components/available_slots.php?day_of_week=${dayOfWeek}&clinic_id=${clinicId}&selected_date=${selectedDate}`, true);
                xhr.onload = function () {
                    if (this.status == 200) {
                        document.getElementById('time_slot').innerHTML = this.responseText;
                    }
                };
                xhr.send();
            }
        }
    </script>
</body>

</html>