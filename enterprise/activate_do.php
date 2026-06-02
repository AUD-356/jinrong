<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login.php');
}

// 验证session中是否有临时用户信息
if (!isset($_SESSION['temp_user_id']) || !in_array($_SESSION['temp_user_type'], ['personal', 'enterprise'])) {
    $_SESSION['error'] = '请先登录';
    redirect('login.php');
}

// Validate CSRF token
if (empty($_POST[CSRF_TOKEN_NAME]) || !validateCsrfToken($_POST[CSRF_TOKEN_NAME])) {
    $_SESSION['error'] = '安全验证失败，请重试';
    redirect('activate.php');
}

$activationCode = $_POST['activation_code'] ?? '';

if (empty($activationCode)) {
    $_SESSION['error'] = '请输入激活码';
    redirect('activate.php');
}

$userType = $_SESSION['temp_user_type'];
$userId = $_SESSION['temp_user_id'];

// 根据用户类型查询不同的表
if ($userType === 'personal') {
    $user = dbGetRow("SELECT * FROM users WHERE id = ?", [$userId]);
    $table = 'users';
} else {
    $user = dbGetRow("SELECT * FROM enterprises WHERE id = ?", [$userId]);
    $table = 'enterprises';
}

if (!$user) {
    $_SESSION['error'] = '用户不存在';
    redirect('login.php');
}

// 验证激活码（不区分大小写）
if (strcasecmp(trim($user['activation_code']), trim($activationCode)) !== 0) {
    $_SESSION['error'] = '激活码错误，请重试';
    redirect('activate.php');
}

// 更新用户激活状态
dbExecute("UPDATE {$table} SET is_activated = 1, activated_at = NOW() WHERE id = ?", 
    [$userId]);

// 清除临时session，设置正式登录session
unset($_SESSION['temp_user_id']);
unset($_SESSION['temp_user_type']);
unset($_SESSION['temp_username']);
unset($_SESSION['temp_real_name']);

if ($userType === 'personal') {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_type'] = 'personal';
    $_SESSION['username'] = $user['username'];
    $_SESSION['real_name'] = $user['real_name'];
    dbExecute("UPDATE users SET last_login = NOW(), last_ip = ? WHERE id = ?", 
        [$_SERVER['REMOTE_ADDR'] ?? '', $user['id']]);
} else {
    $_SESSION['enterprise_id'] = $user['id'];
    $_SESSION['enterprise_type'] = 'enterprise';
    $_SESSION['enterprise_username'] = $user['username'];
    $_SESSION['company_name'] = $user['company_name'];
    dbExecute("UPDATE enterprises SET last_login = NOW(), last_ip = ? WHERE id = ?", 
        [$_SERVER['REMOTE_ADDR'] ?? '', $user['id']]);
}

$_SESSION['success'] = '账户激活成功！';
redirect('index.php');
