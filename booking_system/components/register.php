<!-- <?php
// require_once '../../manegment_system/components/db.php';

// $message = ''; // متغير لتخزين الرسائل

// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $email = $_POST['email'];
//     $password = $_POST['password'];
//     $confirm_password = $_POST['confirm_password']; // كلمة المرور للتأكيد

//     // التحقق من تطابق كلمتي المرور
//     if ($password !== $confirm_password) {
//         $message = "كلمتا المرور غير متطابقتين";
//     } elseif (strlen($password) < 8) { // التحقق من طول كلمة المرور
//         $message = "يجب أن تكون كلمة المرور لا تقل عن 8 خانات";
//     } else {
//         $hashed_password = password_hash($password, PASSWORD_BCRYPT);

//         $sql = "INSERT INTO users (email, password) VALUES (?, ?)";
//         $stmt = $pdo->prepare($sql);
//         $stmt->bindValue(1, $email);
//         $stmt->bindValue(2, $hashed_password);

//         if ($stmt->execute()) {
//             $message = "تم التسجيل بنجاح";
//         } else {
//             $message = "حدث خطأ: " . $stmt->errorInfo()[2];
//         }

//         $stmt = null;
//     }
// }
// $pdo = null;
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التسجيل</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 400px;
            margin: 50px auto;
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

        p {
            text-align: center;
            color: #ff0000; /* لون الرسائل */
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>التسجيل</h2>
        <form action="register.php" method="post">
            <label for="email">البريد الإلكتروني:</label>
            <input type="email" name="email" id="email" required>

            <label for="password">كلمة المرور:</label>
            <input type="password" name="password" id="password" required>

            <label for="confirm_password">تأكيد كلمة المرور:</label>
            <input type="password" name="confirm_password" id="confirm_password" required>

            <button type="submit">تسجيل</button>
            <?php if ($message): ?>
                <p><?php echo $message; ?></p>
            <?php endif; ?>
        </form>
    </div>
</body>
</html> -->
