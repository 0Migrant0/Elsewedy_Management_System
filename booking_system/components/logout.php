<?php
session_start(); // بدء الجلسة

// تحقق مما إذا كان المستخدم قد سجل الدخول
if (isset($_SESSION['user_id'])) {
    // إنهاء الجلسة
    session_unset();
    session_destroy();

    // إعادة توجيه المستخدم إلى صفحة تسجيل الدخول
    header("Location: login.php");
    exit();
} else {
    // إذا لم يكن هناك جلسة، إعادة التوجيه إلى صفحة تسجيل الدخول
    header("Location: login.php");
    exit();
}
?>
