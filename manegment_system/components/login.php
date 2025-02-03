<?php
require_once 'db.php'; // Include your database configuration file

// Redirect to index.php if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: ../index.php');
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    try {
        // Prepare and execute
        $stmt = $pdo->prepare("SELECT password FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $hashed_password = $row['password'];

            // Validate login credentials
            if (password_verify($password, $hashed_password)) {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_email'] = $email; // Save additional session data if needed
                header('Location: ../index.php');
                exit;
            }
        }
        // Generic error message to prevent user enumeration
        $error = "البريد الإلكتروني أو كلمة المرور غير صحيحة!";
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
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