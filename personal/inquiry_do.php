<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/captcha.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('inquiry.php');
}

$receiptNo = sanitize($_POST['receipt_no'] ?? '');
$captcha = sanitize($_POST['captcha'] ?? '');

if (empty($receiptNo)) {
    $_SESSION['error'] = '请输入回执单号';
    redirect('inquiry.php');
}

if (!verifyCaptcha($captcha)) {
    $_SESSION['error'] = '验证码错误';
    redirect('inquiry.php');
}

$application = dbGetRow("SELECT * FROM applications WHERE receipt_no = ?", [$receiptNo]);

if (!$application) {
    $_SESSION['error'] = '未找到该回执单号的申请记录';
    redirect('inquiry.php');
}

$userTypeText = $application['user_type'] === 'personal' ? '个人用户' : '企业用户';
$typeText = getApplicationTypeText($application['type']);

$_SESSION['inquiry_result'] = $application;
redirect('inquiry_result.php');

function getApplicationTypeText($type) {
    $types = [
        'register' => '用户注册',
        'transfer' => '转账申请',
        'card_open' => '银行卡开户',
        'loan' => '贷款申请',
        'forgot_password' => '忘记密码',
        'other' => '其他申请'
    ];
    return $types[$type] ?? $type;
}
