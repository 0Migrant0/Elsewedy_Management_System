<?php
// auth.php - متاح للموظف والأدمن
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$allowed_roles = ['admin', 'employee'];
if (!isset($_SESSION['admin_role']) || !in_array($_SESSION['admin_role'], $allowed_roles)) {
    die("ليس لديك صلاحية الوصول لهذه الصفحة.");
}
?>