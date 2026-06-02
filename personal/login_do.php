<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/captcha.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login.php');
}

// Validate CSRF token
if (empty($_POST[CSRF_TOKEN_NAME]) || !validateCsrfToken($_POST[CSRF_TOKEN_NAME])) {
    $_SESSION['error'] = '安全验证失败，请重试';
    redirect('login.php');
}

$username = sanitize($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$captcha = sanitize($_POST['captcha'] ?? '');

if (empty($username) || empty($password) || empty($captcha)) {
    $_SESSION['error'] = '请填写所有必填项';
    redirect('login.php');
}

if (!verifyCaptcha($captcha)) {
    $_SESSION['error'] = '验证码错误';
    redirect('login.php');
}

// 查询用户信息
$user = dbGetRow("SELECT * FROM users WHERE username = ?", [$username]);

if (!$user) {
    $_SESSION['error'] = '用户名或密码错误';
    redirect('login.php');
}

if ($user['status'] === 'frozen') {
    $_SESSION['error'] = '账户已被冻结，原因：' . $user['freeze_reason'];
    redirect('login.php');
}

if ($user['status'] === 'closed') {
    $_SESSION['error'] = '账户已注销';
    redirect('login.php');
}

if ($user['status'] === 'pending') {
    // 显示申请状态弹窗提示
    $_SESSION['pending_username'] = $username;
    $_SESSION['pending_type'] = 'personal';
    redirect('pending_status.php');
}

if (!verifyPassword($password, $user['password'])) {
    $_SESSION['error'] = '用户名或密码错误';
    redirect('login.php');
}

// 检查是否需要激活码验证
$hasActivationCode = isset($user['activation_code']) && !empty($user['activation_code']);
$isActivated = isset($user['is_activated']) && $user['is_activated'] == 1;

// 如果有激活码但未激活，就需要输入激活码
$needActivation = $hasActivationCode && !$isActivated;

if ($needActivation) {
    // 需要激活码验证，保存用户信息到session并跳转到激活页面
    $_SESSION['temp_user_id'] = $user['id'];
    $_SESSION['temp_user_type'] = 'personal';
    $_SESSION['temp_username'] = $user['username'];
    $_SESSION['temp_real_name'] = $user['real_name'];
    redirect('activate.php');
}

// 无需激活码验证，直接登录
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_type'] = 'personal';
$_SESSION['username'] = $user['username'];
$_SESSION['real_name'] = $user['real_name'];

dbExecute("UPDATE users SET last_login = NOW(), last_ip = ? WHERE id = ?", 
    [$_SERVER['REMOTE_ADDR'] ?? '', $user['id']]);

redirect('index.php');
