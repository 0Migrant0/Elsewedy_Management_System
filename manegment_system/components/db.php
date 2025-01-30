<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection details
$host = 'localhost';
$dbname = 'booking_system';
$username = 'root';
$password = '';

try {
    // Create a PDO instance with error handling
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Enable exceptions
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Default fetch mode
} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

?>


<?php if (isset($error)) {
    echo "<p>$error</p>";
} ?>