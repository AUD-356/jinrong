<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/captcha.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('forgot_password.php');
}

$username = sanitize($_POST['username'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$idCard = sanitize($_POST['id_card'] ?? '');
$captcha = sanitize($_POST['captcha'] ?? '');

if (empty($username) || empty($phone) || empty($idCard)) {
    $_SESSION['error'] = '请填写所有必填项';
    redirect('forgot_password.php');
}

if (!verifyCaptcha($captcha)) {
    $_SESSION['error'] = '验证码错误';
    redirect('forgot_password.php');
}

$user = dbGetRow("SELECT * FROM users WHERE username = ? AND phone = ? AND id_card = ?", 
    [$username, $phone, $idCard]);

if (!$user) {
    $_SESSION['error'] = '信息验证失败，请检查输入';
    redirect('forgot_password.php');
}

$receiptNo = generateReceiptNo();
dbInsert('applications', [
    'receipt_no' => $receiptNo,
    'user_id' => $user['id'],
    'user_type' => 'personal',
    'type' => 'forgot_password',
    'title' => '忘记密码申请',
    'content' => json_encode(['username' => $username, 'reason' => '用户忘记密码']),
    'status' => 'pending',
    'created_at' => date('Y-m-d H:i:s')
]);

$_SESSION['forgot_receipt_no'] = $receiptNo;
$_SESSION['forgot_user_id'] = $user['id'];
$_SESSION['forgot_type'] = 'personal';
redirect('forgot_password_set.php');
