<?php
session_start();
require_once '../../manegment_system/components/db.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $sql = "SELECT id, password FROM users WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                header("Location: ../dashboard.php");
                exit(); // تأكد من إنهاء البرنامج بعد إعادة التوجيه
            } else {
                $error_message = "كلمة المرور غير صحيحة";
            }
        } else {
            $error_message = "البريد الإلكتروني غير موجود";
        }

        $stmt = null;
    } else {
        $error_message = "البريد الإلكتروني غير صالح";
    }
}
$pdo = null;
?>

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="..\styles\styles.css"> <!-- تأكد من أن اسم الملف صحيح -->
    <title>تسجيل الدخول</title>
</head>

<body>
    <div class="login-container">
        <h2>تسجيل الدخول</h2>
        <form action="login.php" method="post">
            <label for="email">البريد الإلكتروني</label>
            <input type="email" name="email" id="email" required>

            <label for="password">كلمة المرور</label>
            <input type="password" name="password" id="password" required>

            <button type="submit">دخول</button>
            <?php if (isset($error_message)): ?>
                <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>
        </form>

        <!-- زر الانتقال إلى صفحة الحجوزات -->
        <!-- <a href="../index.php" class="link-button">الذهاب إلى صفحة الحجوزات</a> -->
    </div>
</body>

</html>