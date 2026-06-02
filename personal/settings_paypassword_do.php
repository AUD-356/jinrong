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
$user = dbGetRow("SELECT pay_password FROM users WHERE id = ?", [$userId]);

$oldPayPassword = $_POST['old_pay_password'] ?? '';
$newPayPassword = $_POST['new_pay_password'] ?? '';
$confirmPayPassword = $_POST['confirm_pay_password'] ?? '';

if (!empty($user['pay_password'])) {
    if (empty($oldPayPassword)) {
        $_SESSION['error'] = '请输入当前支付密码';
        redirect('settings.php#paypassword');
    }
    
    if (!verifyPassword($oldPayPassword, $user['pay_password'])) {
        $_SESSION['error'] = '当前支付密码错误';
        redirect('settings.php#paypassword');
    }
}

if (empty($newPayPassword) || empty($confirmPayPassword)) {
    $_SESSION['error'] = '请填写所有必填项';
    redirect('settings.php#paypassword');
}

if ($newPayPassword !== $confirmPayPassword) {
    $_SESSION['error'] = '两次输入的支付密码不一致';
    redirect('settings.php#paypassword');
}

if (!preg_match('/^\d{6}$/', $newPayPassword)) {
    $_SESSION['error'] = '支付密码必须为6位数字';
    redirect('settings.php#paypassword');
}

dbExecute("UPDATE users SET pay_password = ?, updated_at = NOW() WHERE id = ?", 
    [hashPassword($newPayPassword), $userId]);

$_SESSION['success'] = '支付密码设置成功';

redirect('settings.php#paypassword');
