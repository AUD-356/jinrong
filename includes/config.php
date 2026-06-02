<?php
// Enhanced session security
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_secure' => false,
        'cookie_httponly' => true,
        'use_strict_mode' => true
    ]);
}

// Database configuration - default values
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'root');
define('DB_PORT', '3306');

// Security constants
define('CSRF_TOKEN_NAME', 'csrf_token');

define('BASE_PATH', __DIR__);
define('ASSETS_PATH', BASE_PATH . '/assets');
define('UPLOADS_PATH', BASE_PATH . '/uploads');

define('SITE_NAME', '金融练习靶场');
define('SITE_URL', 'http://localhost/bachang');

define('PERSONAL_TRANSFER_LIMIT', 5000);
define('ENTERPRISE_TRANSFER_LIMIT', 10000);

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die('数据库连接失败: ' . $e->getMessage());
        }
    }
    return $pdo;
}

function isLoggedIn($type = 'personal') {
    $key = $type === 'personal' ? 'user_id' : 'enterprise_id';
    return isset($_SESSION[$key]);
}

function getUserId($type = 'personal') {
    $key = $type === 'personal' ? 'user_id' : 'enterprise_id';
    return $_SESSION[$key] ?? 0;
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateReceiptNo() {
    return 'RC' . date('YmdHis') . rand(1000, 9999);
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function formatMoney($amount) {
    return number_format($amount, 2, '.', ',');
}

function formatDate($date) {
    return date('Y-m-d H:i:s', strtotime($date));
}

function formatDateShort($date) {
    return date('Y-m-d', strtotime($date));
}

function getStatusText($status) {
    $statusMap = [
        'pending' => '待处理',
        'processing' => '处理中',
        'approved' => '已通过',
        'rejected' => '已拒绝',
        'completed' => '已完成',
        'frozen' => '已冻结',
        'active' => '正常',
        'inactive' => '未激活'
    ];
    return $statusMap[$status] ?? $status;
}

function getStatusClass($status) {
    $classMap = [
        'pending' => 'bg-warning',
        'processing' => 'bg-info',
        'approved' => 'bg-success',
        'rejected' => 'bg-danger',
        'completed' => 'bg-success',
        'frozen' => 'bg-secondary',
        'active' => 'bg-success',
        'inactive' => 'bg-secondary'
    ];
    return $classMap[$status] ?? 'bg-secondary';
}

function getLoanStatusText($status) {
    $statusMap = [
        'pending' => '待审核',
        'approved' => '已批准(待签约)',
        'contract_signed' => '已签约(待放款)',
        'disbursed' => '已放款',
        'rejected' => '已拒绝',
        'paid' => '已还清'
    ];
    return $statusMap[$status] ?? $status;
}

function getLoanStatusClass($status) {
    $classMap = [
        'pending' => 'bg-warning',
        'approved' => 'bg-info',
        'contract_signed' => 'bg-primary',
        'disbursed' => 'bg-success',
        'rejected' => 'bg-danger',
        'paid' => 'bg-secondary'
    ];
    return $classMap[$status] ?? 'bg-secondary';
}

function getCardStatusText($status) {
    $statusMap = [
        'active' => '正常',
        'frozen' => '已冻结',
        'closed' => '已销户'
    ];
    return $statusMap[$status] ?? $status;
}

function getTransferStatusText($status) {
    $statusMap = [
        'pending' => '待审核',
        'approved' => '已通过',
        'rejected' => '已拒绝',
        'processing' => '处理中',
        'completed' => '已完成',
        'failed' => '失败',
        'refunded' => '已退回'
    ];
    return $statusMap[$status] ?? $status;
}

function getInvoiceStatusText($status) {
    $statusMap = [
        'pending' => '待处理',
        'approved' => '已通过',
        'rejected' => '已拒绝',
        'sent' => '已发送'
    ];
    return $statusMap[$status] ?? $status;
}

function generateCsrfToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function validateCsrfToken($token) {
    if (empty($_SESSION[CSRF_TOKEN_NAME]) || !hash_equals($_SESSION[CSRF_TOKEN_NAME], $token)) {
        return false;
    }
    return true;
}

function csrfField() {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . generateCsrfToken() . '">';
}


