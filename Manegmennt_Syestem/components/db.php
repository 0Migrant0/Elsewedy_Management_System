<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection details
$host = 'localhost';
$dbname = 'booking_system'; // Ensure this is your database name
$username = 'root';       // Your custom username
$password = '';         // Your custom password

try {
    // Create a PDO instance with error handling
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Enable exceptions
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Default fetch mode
} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}



// التحقق من بيانات الدخول
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // استرجاع بيانات المستخدم من قاعدة البيانات
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // تحقق من وجود المستخدم وكلمة المرور
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = "البريد الإلكتروني أو كلمة المرور غير صحيحة.";
    }
}
?>


<?php if (isset($error)) {
    echo "<p>$error</p>";
} ?>