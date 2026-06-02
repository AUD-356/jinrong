<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/captcha.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('forgot_password.php');
}

$phone = sanitize($_POST['phone'] ?? '');
$captcha = sanitize($_POST['captcha'] ?? '');

if (!validatePhone($phone)) {
    $_SESSION['error'] = '请输入正确的手机号码';
    redirect('forgot_password.php');
}

if (!verifyCaptcha($captcha)) {
    $_SESSION['error'] = '验证码错误';
    redirect('forgot_password.php');
}

$enterprise = dbGetRow("SELECT id, company_name FROM enterprises WHERE phone = ?", [$phone]);

if (!$enterprise) {
    $_SESSION['error'] = '该手机号未注册';
    redirect('forgot_password.php');
}

$token = md5($enterprise['id'] . time() . 'reset_pwd');
$expireTime = date('Y-m-d H:i:s', time() + 3600);

dbUpdate('enterprises', ['reset_token' => $token, 'reset_token_expire' => $expireTime], 'id = ?', [$enterprise['id']]);

$_SESSION['success'] = '验证码已发送至您的手机';
redirect('reset_password.php?token=' . $token);
?>