<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/captcha.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('register.php');
}

$username = sanitize($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$realName = sanitize($_POST['real_name'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$idCard = sanitize($_POST['id_card'] ?? '');
$address = sanitize($_POST['address'] ?? '');
$captcha = sanitize($_POST['captcha'] ?? '');

// 验证必填项
if (empty($username) || empty($password) || empty($confirmPassword) || 
    empty($realName) || empty($phone) || empty($idCard)) {
    $_SESSION['error'] = '请填写所有必填项';
    redirect('register.php');
}

// 验证用户名长度
if (strlen($username) < 4 || strlen($username) > 20) {
    $_SESSION['error'] = '用户名长度需要在4-20位之间';
    redirect('register.php');
}

// 验证用户名格式
if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $_SESSION['error'] = '用户名只能包含字母、数字和下划线';
    redirect('register.php');
}

// 验证密码
if ($password !== $confirmPassword) {
    $_SESSION['error'] = '两次输入的密码不一致';
    redirect('register.php');
}

if (strlen($password) < 6) {
    $_SESSION['error'] = '密码长度不能少于6位';
    redirect('register.php');
}

// 验证手机号
if (!validatePhone($phone)) {
    $_SESSION['error'] = '请输入正确的11位手机号';
    redirect('register.php');
}

// 验证身份证号
if (!validateIdCard($idCard)) {
    $_SESSION['error'] = '请输入正确的18位身份证号';
    redirect('register.php');
}

// 验证邮箱（可选）
if (!empty($email) && !validateEmail($email)) {
    $_SESSION['error'] = '请输入正确的邮箱地址';
    redirect('register.php');
}

// 验证验证码
if (!verifyCaptcha($captcha)) {
    $_SESSION['error'] = '验证码错误';
    redirect('register.php');
}

// 检查用户名是否已存在
$exists = dbGetOne("SELECT id FROM users WHERE username = ?", [$username]);
if ($exists) {
    $_SESSION['error'] = '用户名已被注册';
    redirect('register.php');
}

// 检查手机号是否已存在
$existsPhone = dbGetOne("SELECT id FROM users WHERE phone = ?", [$phone]);
if ($existsPhone) {
    $_SESSION['error'] = '手机号已被注册';
    redirect('register.php');
}

$userId = dbInsert('users', [
    'username' => $username,
    'password' => hashPassword($password),
    'real_name' => $realName,
    'phone' => $phone,
    'email' => $email,
    'id_card' => $idCard,
    'address' => $address,
    'status' => 'pending',
    'created_at' => date('Y-m-d H:i:s')
]);

$receiptNo = generateReceiptNo();
dbInsert('applications', [
    'receipt_no' => $receiptNo,
    'user_id' => $userId,
    'user_type' => 'personal',
    'type' => 'register',
    'title' => '个人用户注册',
    'content' => json_encode([
        'username' => $username,
        'real_name' => $realName,
        'phone' => $phone,
        'email' => $email,
        'id_card' => $idCard
    ]),
    'status' => 'pending',
    'created_at' => date('Y-m-d H:i:s')
]);

$_SESSION['register_receipt_no'] = $receiptNo;
$_SESSION['register_username'] = $username;

redirect('register_status.php');
