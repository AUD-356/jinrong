<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (isset($_SESSION['admin_id'])) {
    try {
        $pdo = getDB();
        $logStmt = $pdo->prepare("INSERT INTO operation_logs (operator_id, operator_type, action, details, ip, created_at) VALUES (?, 'admin', 'logout', '管理员退出登录', ?, NOW())");
        $logStmt->execute([$_SESSION['admin_id'], $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (Exception $e) {
    }
}

session_destroy();
redirect('login.php');
