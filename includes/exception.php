<?php

class AppException extends Exception {
    protected $code;
    protected $data;

    public function __construct($message, $code = 500, $data = null) {
        parent::__construct($message, $code);
        $this->data = $data;
    }

    public function getData() {
        return $this->data;
    }

    public function toArray() {
        return [
            'success' => false,
            'error' => [
                'code' => $this->getCode(),
                'message' => $this->getMessage(),
                'data' => $this->getData()
            ]
        ];
    }

    public function toJson() {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
}

class ValidationException extends AppException {
    public function __construct($message, $data = null) {
        parent::__construct($message, 400, $data);
    }
}

class AuthenticationException extends AppException {
    public function __construct($message = '未登录或登录已过期') {
        parent::__construct($message, 401);
    }
}

class AuthorizationException extends AppException {
    public function __construct($message = '权限不足') {
        parent::__construct($message, 403);
    }
}

class NotFoundException extends AppException {
    public function __construct($message = '资源不存在') {
        parent::__construct($message, 404);
    }
}

class DatabaseException extends AppException {
    public function __construct($message = '数据库操作失败') {
        parent::__construct($message, 500);
    }
}

class BusinessException extends AppException {
    public function __construct($message, $data = null) {
        parent::__construct($message, 422, $data);
    }
}

function handleException($exception) {
    if ($exception instanceof AppException) {
        http_response_code($exception->getCode());
        header('Content-Type: application/json');
        echo $exception->toJson();
    } else {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 500,
                'message' => '系统内部错误',
                'data' => null
            ]
        ], JSON_UNESCAPED_UNICODE);
    }
    exit();
}

function handleError($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return;
    }

    $error = new AppException($errstr, 500, [
        'file' => $errfile,
        'line' => $errline,
        'errno' => $errno
    ]);
    handleException($error);
}

function handleFatalError() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR || $error['type'] === E_COMPILE_ERROR)) {
        $exception = new AppException('致命错误: ' . $error['message'], 500, [
            'file' => $error['file'],
            'line' => $error['line']
        ]);
        handleException($exception);
    }
}

set_exception_handler('handleException');
set_error_handler('handleError');
register_shutdown_function('handleFatalError');

function validateRequired($value, $fieldName) {
    if (empty($value)) {
        throw new ValidationException("{$fieldName}不能为空");
    }
    return $value;
}

function validateEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new ValidationException('邮箱格式不正确');
    }
    return $email;
}

function validatePhone($phone) {
    if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
        throw new ValidationException('手机号码格式不正确');
    }
    return $phone;
}

function validateIdCard($idCard) {
    if (!preg_match('/^[1-9]\d{5}(18|19|20)\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])\d{3}[\dXx]$/', $idCard)) {
        throw new ValidationException('身份证号码格式不正确');
    }
    return $idCard;
}

function validateAmount($amount, $min = 0.01, $max = 999999999) {
    if (!is_numeric($amount)) {
        throw new ValidationException('金额必须是数字');
    }
    $amount = (float)$amount;
    if ($amount < $min || $amount > $max) {
        throw new ValidationException("金额必须在{$min}到{$max}之间");
    }
    return $amount;
}

function validatePassword($password) {
    if (strlen($password) < 6) {
        throw new ValidationException('密码长度不能少于6位');
    }
    return $password;
}

function assertLoggedIn($type = 'personal') {
    if (!isLoggedIn($type)) {
        throw new AuthenticationException();
    }
}

function assertAdmin() {
    if (!isset($_SESSION['admin_id'])) {
        throw new AuthenticationException('请先登录管理员账户');
    }
}

function assertPositive($value, $fieldName = '数值') {
    if ($value <= 0) {
        throw new ValidationException("{$fieldName}必须大于0");
    }
    return $value;
}

function assertGreaterThan($value, $min, $fieldName = '数值') {
    if ($value <= $min) {
        throw new ValidationException("{$fieldName}必须大于{$min}");
    }
    return $value;
}

function assertLessThan($value, $max, $fieldName = '数值') {
    if ($value >= $max) {
        throw new ValidationException("{$fieldName}必须小于{$max}");
    }
    return $value;
}

function assertEqual($value1, $value2, $message = '两个值不相等') {
    if ($value1 != $value2) {
        throw new ValidationException($message);
    }
    return $value1;
}

function assertNotEmptyArray($array, $message = '数组不能为空') {
    if (!is_array($array) || empty($array)) {
        throw new ValidationException($message);
    }
    return $array;
}
