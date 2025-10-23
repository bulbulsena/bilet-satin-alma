<?php
require_once 'config/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    $auth->logout();
    $_SESSION['success_message'] = 'Başarıyla çıkış yaptınız.';
    header('Location: index.php');
    exit;
}

// If not logged in, redirect to login
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Logout
$auth->logout();
$_SESSION['success_message'] = 'Başarıyla çıkış yaptınız.';
header('Location: index.php');
exit;
?>
