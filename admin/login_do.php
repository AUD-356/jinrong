<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login.php');
}

$username = sanitize($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$captcha = sanitize($_POST['captcha'] ?? '');

if (empty($username) || empty($password) || empty($captcha)) {
    $_SESSION['error'] = '请填写所有必填项';
    redirect('login.php');
}

if (!isset($_SESSION['captcha']) || strtolower($_SESSION['captcha']) !== strtolower($captcha)) {
    $_SESSION['error'] = '验证码错误';
    redirect('login.php');
}

unset($_SESSION['captcha']);

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? AND status = 'active'");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    if (!$admin || !password_verify($password, $admin['password'])) {
        $_SESSION['error'] = '用户名或密码错误';
        redirect('login.php');
    }
    
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_real_name'] = $admin['real_name'];
    $_SESSION['admin_role'] = $admin['role'];
    
    $updateStmt = $pdo->prepare("UPDATE admin_users SET last_login = NOW(), last_ip = ? WHERE id = ?");
    $updateStmt->execute([$_SERVER['REMOTE_ADDR'] ?? '', $admin['id']]);
    
    $logStmt = $pdo->prepare("INSERT INTO operation_logs (operator_id, operator_type, action, details, ip, created_at) VALUES (?, 'admin', 'login', ?, ?, NOW())");
    $logStmt->execute([$admin['id'], '管理员登录系统', $_SERVER['REMOTE_ADDR'] ?? '']);
    
    redirect('index.php');
    
} catch (Exception $e) {
    $_SESSION['error'] = '系统错误，请稍后重试';
    redirect('login.php');
}
