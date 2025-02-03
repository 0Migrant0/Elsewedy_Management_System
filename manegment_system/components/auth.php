<?php
// Set secure session cookie parameters
ini_set('session.cookie_httponly', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

session_start();

// Regenerate session ID to prevent session fixation
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// Check if user is authenticated
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
?>