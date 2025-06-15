<?php
// register.php

// بدء الجلسة فقط إذا لم تكن نشطة
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

// إعادة التوجيه إذا كان المستخدم قد سجل دخوله بالفعل
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: ../index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // تحديد الدور - الأدمن يمكنه اختيار الدور، والموظف دائمًا يكون role = employee
    $role = 'employee'; // الافتراضي هو موظف
    if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin') {
        $role = $_POST['role'] ?? 'employee';
    }

    try {
        // التحقق من صحة البريد الإلكتروني
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "البريد الإلكتروني غير صحيح.";
        } elseif ($password !== $confirm_password) {
            $error = "كلمة المرور غير متطابقة.";
        } elseif (strlen($password) < 6) {
            $error = "كلمة المرور يجب أن تكون على الأقل 6 أحرف.";
        } else {
            // التحقق من وجود البريد بالفعل
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $error = "البريد الإلكتروني مستخدم بالفعل.";
            } else {
                // تشفير كلمة المرور
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // إدخال الحساب إلى قاعدة البيانات
                $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
                if ($stmt->execute([$email, $hashed_password, $role])) {
                    $success = "تم تسجيل الحساب بنجاح. يمكنك الآن تسجيل الدخول.";
                } else {
                    $error = "حدث خطأ أثناء تسجيل الحساب.";
                }
            }
        }
    } catch (PDOException $e) {
        die("فشل الاتصال: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إنشاء حساب</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .register_container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }

        h2 {
            text-align: center;
            color: #333;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-top: 15px;
            font-weight: bold;
        }

        input[type="email"],
        input[type="password"],
        select {
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            margin-top: 20px;
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #218838;
        }

        p.error {
            color: red;
            text-align: center;
            margin-top: 15px;
        }

        p.success {
            color: green;
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>

<div class="register_container">
    <h2>إنشاء حساب جديد</h2>

    <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <p class="success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form action="register.php" method="POST">
        <div>
            <label for="email">البريد الإلكتروني:</label>
            <input type="email" id="email" name="email" required>
        </div>

        <div>
            <label for="password">كلمة المرور:</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div>
            <label for="confirm_password">تأكيد كلمة المرور:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>

        <!-- اختيار الدور - متاح للأدمن فقط -->
        <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin'): ?>
            <div>
                <label for="role">الدور:</label>
                <select id="role" name="role">
                    <option value="employee">موظف</option>
                    <option value="admin">مسؤول</option>
                </select>
            </div>
        <?php else: ?>
            <!-- إذا لم يكن الأدمن مسجل دخوله، يتم تعيين الدور كـ employee تلقائيًا -->
            <input type="hidden" name="role" value="employee">
            <p style="text-align:center; color:green;">سيتم إنشاء حساب كموظف تلقائيًا.</p>
        <?php endif; ?>

        <button type="submit">إنشاء الحساب</button>
    </form>

    <p style="text-align: center; margin-top: 15px;">
        <a href="login.php">لديك حساب بالفعل؟ سجل دخولك</a>
    </p>
</div>

</body>
</html>