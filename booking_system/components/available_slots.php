<?php
require_once '../../manegment_system/components/db.php';


// Get parameters from GET request
$clinic_id = $_GET['clinic_id'];
$day_of_week = $_GET['day_of_week'];
$selected_date = $_GET['selected_date']; // Get the selected date from the query string

// Query to get the working hours of the clinic based on the day
$sql = "SELECT start_time, end_time FROM clinics WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(1, $clinic_id);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($row = $result[0]) {
    $start_time = $row['start_time'];
    $end_time = $row['end_time'];

    // Query to get booked appointment times for the selected date
    $booked_sql = "SELECT appointment_datetime FROM appointments WHERE clinic_id = ? AND DATE(appointment_datetime) = ?";
    $booked_stmt = $pdo->prepare($booked_sql);
    $booked_stmt->bindParam(1, $clinic_id);
    $booked_stmt->bindParam(2, $selected_date); // Use the selected date
    $booked_stmt->execute();
    $booked_result = $booked_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Extract booked slots in 24-hour format
    $booked_slots = [];
    foreach ($booked_result as $booked_row) {
        $booked_slots[] = date('H:i', strtotime($booked_row['appointment_datetime']));
    }

    // Generate available time slots
    $available_slots = [];
    $current_time = strtotime($start_time);
    $end_time_timestamp = strtotime($end_time);

    // Check if the working hours span midnight
    if ($end_time_timestamp < $current_time) {
        // Add 24 hours to $end_time_timestamp to handle the midnight crossover
        $end_time_timestamp += 24 * 60 * 60; // Add one day in seconds
    }

    while ($current_time <= $end_time_timestamp) {
        $slot_time_24h = date('H:i', $current_time); // Time in 24-hour format
        if (!in_array($slot_time_24h, $booked_slots)) {
            // Convert to 12-hour format and replace AM/PM with ص/م
            $slot_time_12h = date('g:i A', $current_time);
            $slot_time_arabic = str_replace(['AM', 'PM'], ['ص', 'م'], $slot_time_12h);

            // Add the formatted time slot to the list of available slots
            $available_slots[] = "<option value='$slot_time_24h'>$slot_time_arabic</option>";
        }
        $current_time = strtotime('+30 minutes', $current_time); // Increment by 30 minutes
    }

    // Check if there are any available slots
    if (empty($available_slots)) {
        echo "<option disabled>تم ملء جميع المواعيد في هذا التاريخ</option>"; // Display message if no slots are available
    } else {
        echo implode('', $available_slots); // Output all available slots
    }
}
?>