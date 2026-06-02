<?php
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/config.php';
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/captcha.php';
require_once __DIR__ . '/mailer.php';

function loadHeader($title, $activeMenu = '', $userType = 'personal') {
    $userId = getUserId($userType);
    $userName = '';
    if ($userId) {
        if ($userType === 'personal') {
            $user = dbGetRow("SELECT real_name FROM users WHERE id = ?", [$userId]);
        } else {
            $user = dbGetRow("SELECT company_name as real_name FROM enterprises WHERE id = ?", [$userId]);
        }
        $userName = $user['real_name'] ?? '用户';
    }
    include __DIR__ . '/../templates/header.php';
}

function loadFooter() {
    include __DIR__ . '/../templates/footer.php';
}

function loadSidebar($activeMenu = '', $userType = 'personal') {
    include __DIR__ . '/../templates/sidebar_' . $userType . '.php';
}

function checkPermission($requiredRole) {
    if (!isset($_SESSION['admin_id'])) {
        redirect('../index.php');
    }
}

function hasPendingApplication($userId, $type, $db) {
    $sql = "SELECT COUNT(*) as cnt FROM applications 
            WHERE user_id = ? AND user_type = 'personal' AND type = ? 
            AND status IN ('pending', 'processing')";
    $result = dbGetRow($sql, [$userId, $type]);
    return $result['cnt'] > 0;
}

function hasActiveLoan($userId, $type = 'personal') {
    $userType = $type === 'personal' ? 'personal' : 'enterprise';
    $sql = "SELECT COUNT(*) as cnt FROM loans 
            WHERE user_id = ? AND user_type = ? 
            AND status IN ('pending', 'approved', 'contract_signed')";
    $result = dbGetRow($sql, [$userId, $userType]);
    return $result['cnt'] > 0;
}

function logOperation($operatorId, $operatorType, $action, $details, $targetId = 0, $targetType = '') {
    $sql = "INSERT INTO operation_logs (operator_id, operator_type, action, details, target_id, target_type, ip, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, '127.0.0.1', '', NOW())";
    dbExecute($sql, [$operatorId, $operatorType, $action, $details, $targetId, $targetType]);
}

function encryptData($data, $key = null) {
    if ($key === null) {
        $key = DB_NAME;
    }
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

function decryptData($data, $key = null) {
    if ($key === null) {
        $key = DB_NAME;
    }
    $parts = explode('::', base64_decode($data), 2);
    if (count($parts) !== 2) {
        return false;
    }
    list($encrypted, $iv) = $parts;
    return openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
}

function generateCsrfToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function validateCsrfToken($token) {
    if (empty($_SESSION[CSRF_TOKEN_NAME]) || !hash_equals($_SESSION[CSRF_TOKEN_NAME], $token)) {
        showError('CSRF验证失败', 'index.php');
        exit;
    }
    return true;
}

function csrfField() {
    return '<input type="hidden" name="'.CSRF_TOKEN_NAME.'" value="'.generateCsrfToken().'">';
}


