<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('settings.php');
}

$userId = $_SESSION['user_id'];
$oldPassword = $_POST['old_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
    $_SESSION['error'] = '请填写所有必填项';
    redirect('settings.php#password');
}

if ($newPassword !== $confirmPassword) {
    $_SESSION['error'] = '两次输入的密码不一致';
    redirect('settings.php#password');
}

if (strlen($newPassword) < 6) {
    $_SESSION['error'] = '密码长度不能少于6位';
    redirect('settings.php#password');
}

$user = dbGetRow("SELECT password FROM users WHERE id = ?", [$userId]);

if (!verifyPassword($oldPassword, $user['password'])) {
    $_SESSION['error'] = '当前密码错误';
    redirect('settings.php#password');
}

dbExecute("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?", 
    [hashPassword($newPassword), $userId]);

$_SESSION['success'] = '登录密码修改成功';

redirect('settings.php#password');
