<?php
session_start();

// Redirect to add_patient.php if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: add_patient.php');
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    // Replace this hardcoded admin login with a database check
    $admin_email = "a@gmail.com"; // Replace with a database query
    $admin_password = password_hash("123", PASSWORD_DEFAULT); // Simulate stored hashed password

    // Validate login credentials
    if ($email === $admin_email && password_verify($password, $admin_password)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_email'] = $email; // Save additional session data if needed
        header('Location: add_patient.php');
        exit;
    } else {
        $error = "البريد الإلكتروني أو كلمة المرور غير صحيحة!";
    }
}
?>

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول</title>
    <link rel="stylesheet" href="../styles/style.css">
</head>

<body>
    <div class="login_container">
        <h2>تسجيل الدخول</h2>

        <form action="login.php" method="POST">
            <div>
                <label for="email">البريد الإلكتروني:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div>
                <label for="password">كلمة المرور:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">دخول</button>
        </form>
    </div>
    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
</body>

</html>