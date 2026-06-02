<?php
session_start();

$userType = '';
if (isset($_SESSION['admin_id'])) {
    $userType = 'admin';
} elseif (isset($_SESSION['user_id'])) {
    $userType = 'personal';
} elseif (isset($_SESSION['enterprise_id'])) {
    $userType = 'enterprise';
}

if ($userType === 'admin') {
    require_once __DIR__ . '/includes/config.php';
    if (isset($_SESSION['admin_id'])) {
        try {
            $pdo = getDB();
            $logStmt = $pdo->prepare("INSERT INTO operation_logs (operator_id, operator_type, action, details, ip, created_at) VALUES (?, 'admin', 'logout', '管理员退出登录', ?, NOW())");
            $logStmt->execute([$_SESSION['admin_id'], $_SERVER['REMOTE_ADDR'] ?? '']);
        } catch (Exception $e) {
        }
    }
    session_destroy();
    header("Location: admin/login.php");
} elseif ($userType === 'personal') {
    require_once __DIR__ . '/includes/config.php';
    session_destroy();
    header("Location: personal/login.php");
} elseif ($userType === 'enterprise') {
    require_once __DIR__ . '/includes/config.php';
    session_destroy();
    header("Location: enterprise/login.php");
} else {
    session_destroy();
    header("Location: index.html");
}
exit;
?>