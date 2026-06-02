<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ ::

'/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('settings.php');
}

$userId = $_SESSION['user_id'];
$realName = sanitize($_POST['real_name'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$address = sanitize($_POST['address'] ?? '');

if (empty($realName)) {
    $_SESSION['error'] = '真实姓名不能为空';
    redirect('settings.php');
}

if (!empty($email) && !validateEmail($email)) {
    $_SESSION['error'] = '请输入正确的邮箱地址';
    redirect('settings.php');
}

dbExecute("UPDATE users SET real_name = ?, email = ?, address = ?, updated_at = NOW() WHERE id = ?", 
    [$realName, $email, $address, $userId]);

$_SESSION['real_name'] = $realName;
$_SESSION['success'] = '基本信息修改成功';

redirect('settings.php');
