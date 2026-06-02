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
$companyName = sanitize($_POST['company_name'] ?? '');
$taxId = sanitize($_POST['tax_id'] ?? '');
$organizationCode = sanitize($_POST['organization_code'] ?? '');
$legalPerson = sanitize($_POST['legal_person'] ?? '');
$legalIdCard = sanitize($_POST['legal_id_card'] ?? '');
$contactPerson = sanitize($_POST['contact_person'] ?? '');
$contactPhone = sanitize($_POST['contact_phone'] ?? '');
$contactEmail = sanitize($_POST['contact_email'] ?? '');
$address = sanitize($_POST['address'] ?? '');
$captcha = sanitize($_POST['captcha'] ?? '');

// 验证必填项
if (empty($username) || empty($password) || empty($confirmPassword) || 
    empty($companyName) || empty($taxId) || empty($legalPerson) || 
    empty($legalIdCard) || empty($contactPerson) || empty($contactPhone)) {
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

// 验证统一社会信用代码
if (!preg_match('/^[0-9A-HJ-NPQRTUWXY]{2}\d{6}[0-9A-HJ-NPQRTUWXY]{10}$/', $taxId)) {
    $_SESSION['error'] = '请输入正确的18位统一社会信用代码';
    redirect('register.php');
}

// 验证法人身份证号
if (!validateIdCard($legalIdCard)) {
    $_SESSION['error'] = '请输入正确的18位法人身份证号';
    redirect('register.php');
}

// 验证联系电话
if (!validatePhone($contactPhone)) {
    $_SESSION['error'] = '请输入正确的11位手机号';
    redirect('register.php');
}

// 验证邮箱（可选）
if (!empty($contactEmail) && !validateEmail($contactEmail)) {
    $_SESSION['error'] = '请输入正确的邮箱地址';
    redirect('register.php');
}

// 验证验证码
if (!verifyCaptcha($captcha)) {
    $_SESSION['error'] = '验证码错误';
    redirect('register.php');
}

// 检查用户名是否已存在
$exists = dbGetOne("SELECT id FROM enterprises WHERE username = ?", [$username]);
if ($exists) {
    $_SESSION['error'] = '用户名已被注册';
    redirect('register.php');
}

// 检查统一社会信用代码是否已存在
$existsTaxId = dbGetOne("SELECT id FROM enterprises WHERE tax_id = ?", [$taxId]);
if ($existsTaxId) {
    $_SESSION['error'] = '该企业已注册';
    redirect('register.php');
}

$enterpriseId = dbInsert('enterprises', [
    'username' => $username,
    'password' => hashPassword($password),
    'company_name' => $companyName,
    'tax_id' => $taxId,
    'organization_code' => $organizationCode,
    'legal_person' => $legalPerson,
    'legal_id_card' => $legalIdCard,
    'contact_person' => $contactPerson,
    'contact_phone' => $contactPhone,
    'contact_email' => $contactEmail,
    'address' => $address,
    'status' => 'pending',
    'created_at' => date('Y-m-d H:i:s')
]);

$receiptNo = generateReceiptNo();
dbInsert('applications', [
    'receipt_no' => $receiptNo,
    'user_id' => $enterpriseId,
    'user_type' => 'enterprise',
    'type' => 'register',
    'title' => '企业用户注册 - ' . $companyName,
    'content' => json_encode([
        'username' => $username,
        'company_name' => $companyName,
        'tax_id' => $taxId,
        'legal_person' => $legalPerson,
        'contact_phone' => $contactPhone,
        'contact_email' => $contactEmail
    ]),
    'status' => 'pending',
    'created_at' => date('Y-m-d H:i:s')
]);

$_SESSION['register_receipt_no'] = $receiptNo;
$_SESSION['register_username'] = $username;

redirect('register_status.php');
