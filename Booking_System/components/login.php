<?php
include 'db_connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
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
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="..\styles\styles.css"> <!-- تأكد من أن اسم الملف صحيح -->
    <title>تسجيل الدخول</title>
    <!-- <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #333333;
        }

        label {
            display: block;
            margin: 10px 0 5px;
            color: #555555;
        }

        input[type="email"],
        input[type="password"],
        button {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #dddddd;
            border-radius: 4px;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #5b9bd5;
            outline: none;
        }

        button {
            background-color: #5b9bd5;
            color: #ffffff;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background-color: #4a8bb2;
        }

        .error-message {
            text-align: center;
            color: #ff0000; /* لون الرسائل */
            margin-top: 10px;
        }

        .link-button {
            background-color: #007bff; /* لون الأزرق */
            color: white;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
            display: block;
            margin-top: 10px; /* إضافة مسافة بين الزر والرسالة */
            text-decoration: none; /* إزالة خط التسطير */
        }

        .link-button:hover {
            background-color: #0056b3; /* لون أغمق عند التحويم */
        }
    </style> -->
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
                <p class="error-message"><?php echo $error_message; ?></p>
            <?php endif; ?>
        </form>

        <!-- زر الانتقال إلى صفحة الحجوزات -->
        <!-- <a href="../index.php" class="link-button">الذهاب إلى صفحة الحجوزات</a> -->
    </div>
</body>
</html>
