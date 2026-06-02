<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['forgot_receipt_no'])) {
    redirect('forgot_password.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('forgot_password_set.php');
}

$receiptNo = sanitize($_POST['receipt_no'] ?? '');
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($receiptNo !== $_SESSION['forgot_receipt_no']) {
    redirect('forgot_password.php');
}

if ($newPassword !== $confirmPassword) {
    $_SESSION['error'] = '两次输入的密码不一致';
    redirect('forgot_password_set.php');
}

if (strlen($newPassword) < 6) {
    $_SESSION['error'] = '密码长度不能少于6位';
    redirect('forgot_password_set.php');
}

$userId = $_SESSION['forgot_user_id'] ?? 0;

dbExecute("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?", 
    [hashPassword($newPassword), $userId]);

dbExecute("UPDATE applications SET status = 'completed', processed_at = NOW() WHERE receipt_no = ?", [$receiptNo]);

session_destroy();

$_SESSION['success'] = '密码重置成功，请使用新密码登录';
header("Location: login.php");
exit;
