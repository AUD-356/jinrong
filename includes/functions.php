<?php

function formatMoneyCN($amount) {
    $cnNumbers = ['零', '壹', '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖'];
    $cnUnits = ['', '拾', '佰', '仟', '万', '拾', '佰', '仟', '亿'];
    
    $num = round($amount, 2);
    $parts = explode('.', $num);
    $intPart = $parts[0];
    $decPart = isset($parts[1]) ? $parts[1] : '00';
    
    $result = '';
    $len = strlen($intPart);
    
    for ($i = 0; $i < $len; $i++) {
        $digit = (int)$intPart[$i];
        $unit = $cnUnits[$len - $i - 1];
        if ($digit != 0) {
            $result .= $cnNumbers[$digit] . $unit;
        } else if (substr($result, -1) != '零' && $unit == '万') {
            $result .= '零';
        }
    }
    
    if (empty($result)) {
        $result = '零';
    }
    
    $result .= '元';
    
    if ($decPart[0] != '0') {
        $result .= $cnNumbers[(int)$decPart[0]] . '角';
    }
    if (strlen($decPart) > 1 && $decPart[1] != '0') {
        $result .= $cnNumbers[(int)$decPart[1]] . '分';
    }
    
    return $result;
}

function getCardStatusClass($status) {
    $classMap = [
        'active' => 'bg-success',
        'frozen' => 'bg-warning text-dark',
        'closed' => 'bg-secondary',
        'pending' => 'bg-info'
    ];
    return $classMap[$status] ?? 'bg-secondary';
}

function getTransferStatusClass($status) {
    $classMap = [
        'pending' => 'bg-warning text-dark',
        'approved' => 'bg-info',
        'rejected' => 'bg-danger',
        'processing' => 'bg-primary',
        'completed' => 'bg-success',
        'failed' => 'bg-secondary',
        'refunded' => 'bg-warning'
    ];
    return $classMap[$status] ?? 'bg-secondary';
}

function getInvoiceStatusClass($status) {
    $classMap = [
        'pending' => 'bg-warning text-dark',
        'approved' => 'bg-success',
        'rejected' => 'bg-danger',
        'sent' => 'bg-info'
    ];
    return $classMap[$status] ?? 'bg-secondary';
}

function validatePhone($phone) {
    return preg_match('/^1[3-9]\d{9}$/', $phone);
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validateIdCard($idCard) {
    // 简单格式验证：18位数字或最后一位X/x
    return preg_match('/^\d{17}[\dXx]$/', $idCard);
}

function validateBankCard($cardNo) {
    return preg_match('/^\d{16,19}$/', str_replace(' ', '', $cardNo));
}

function normalizeCardNo($cardNo) {
    return preg_replace(['/\s+/', '/-/', '/　/'], ['', '', ''], $cardNo);
}

function validateAmount($amount, $min = 0.01, $max = 999999999) {
    return is_numeric($amount) && $amount >= $min && $amount <= $max;
}

function validatePassword($password) {
    return strlen($password) >= 6;
}

function generateCardNumber() {
    return '6228' . rand(1000, 9999) . rand(1000, 9999) . rand(1000, 9999) . rand(10, 99);
}

function generateCardPwd() {
    return rand(100000, 999999);
}

function getBankName($bankCode) {
    $banks = [
        'ICBC' => '中国工商银行',
        'ABC' => '中国农业银行',
        'BOC' => '中国银行',
        'CCB' => '中国建设银行',
        'COMM' => '交通银行',
        'CMB' => '招商银行',
        'CITIC' => '中信银行',
        'CEB' => '中国光大银行',
        'HXB' => '华夏银行',
        'CMBC' => '民生银行',
        'GDB' => '广发银行',
        'PAB' => '平安银行',
        'PSBC' => '中国邮政储蓄银行',
        'SPDB' => '上海浦东发展银行',
        'CIB' => '兴业银行'
    ];
    return $banks[$bankCode] ?? '未知银行';
}

function getStockName($stockCode) {
    $stocks = [
        '600000' => '浦发银行',
        '600036' => '招商银行',
        '600519' => '贵州茅台',
        '601398' => '工商银行',
        '601288' => '农业银行',
        '601988' => '中国银行',
        '601318' => '中国平安',
        '600028' => '中国石化',
        '601857' => '中国石油',
        '600016' => '民生银行',
        '000001' => '平安银行',
        '000002' => '万科A',
        '000858' => '五粮液',
        '600887' => '伊利股份',
        '601888' => '中国国旅',
        '600276' => '恒瑞医药',
        '002594' => '比亚迪',
        '600030' => '中信证券',
        '601012' => '隆基绿能',
        '300750' => '宁德时代'
    ];
    return $stocks[$stockCode] ?? $stockCode;
}

function getStockFullName($stockCode) {
    $stocks = [
        '600000' => '上海浦东发展银行股份有限公司',
        '600036' => '招商银行股份有限公司',
        '600519' => '贵州茅台酒股份有限公司',
        '601398' => '中国工商银行股份有限公司',
        '601288' => '中国农业银行股份有限公司',
        '601988' => '中国银行股份有限公司',
        '601318' => '中国平安保险(集团)股份有限公司',
        '600028' => '中国石油化工股份有限公司',
        '601857' => '中国石油天然气股份有限公司',
        '600016' => '中国民生银行股份有限公司',
        '000001' => '平安银行股份有限公司',
        '000002' => '万科企业股份有限公司',
        '000858' => '宜宾五粮液股份有限公司',
        '600887' => '内蒙古伊利实业集团股份有限公司',
        '601888' => '中国国旅股份有限公司',
        '600276' => '江苏恒瑞医药股份有限公司',
        '002594' => '比亚迪股份有限公司',
        '600030' => '中信证券股份有限公司',
        '601012' => '隆基绿能科技股份有限公司',
        '300750' => '宁德时代新能源科技股份有限公司'
    ];
    return $stocks[$stockCode] ?? $stockCode;
}

function formatStockPrice($price) {
    return number_format($price, 2, '.', '');
}

function formatStockCode($code) {
    if (substr($code, 0, 1) === '6') {
        return 'sh' . $code;
    } else {
        return 'sz' . $code;
    }
}

function calculateStockProfit($buyPrice, $currentPrice, $quantity) {
    return ($currentPrice - $buyPrice) * $quantity;
}

function calculateStockProfitRate($buyPrice, $currentPrice) {
    if ($buyPrice == 0) return 0;
    return round((($currentPrice - $buyPrice) / $buyPrice) * 100, 2);
}

function getProfitClass($rate) {
    if ($rate > 0) return 'text-danger';
    if ($rate < 0) return 'text-success';
    return 'text-muted';
}

function getProfitSign($rate) {
    if ($rate > 0) return '+';
    return '';
}

function showError($message, $redirectUrl = '') {
    $_SESSION['error'] = $message;
    if ($redirectUrl) {
        redirect($redirectUrl);
    }
}

function showSuccess($message, $redirectUrl = '') {
    $_SESSION['success'] = $message;
    if ($redirectUrl) {
        redirect($redirectUrl);
    }
}

function getFlashMessage($type = 'success') {
    $key = $type;
    $message = $_SESSION[$key] ?? '';
    unset($_SESSION[$key]);
    return $message;
}

function paginate($sql, $params = [], $page = 1, $pageSize = 20) {
    $page = max(1, intval($page));
    $pageSize = max(1, min(100, intval($pageSize)));
    $offset = ($page - 1) * $pageSize;
    
    $countSql = preg_replace('/SELECT.*?FROM/i', 'SELECT COUNT(*) as total FROM', $sql, 1);
    $total = dbGetOne($countSql, $params);
    
    $sql .= " LIMIT {$pageSize} OFFSET {$offset}";
    $items = dbGetAll($sql, $params);
    
    return [
        'items' => $items,
        'total' => $total,
        'page' => $page,
        'pageSize' => $pageSize,
        'totalPages' => ceil($total / $pageSize)
    ];
}

function generateRandomString($length = 32) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $result .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $result;
}

function generateActivationCode() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $parts = [];
    for ($i = 0; $i < 4; $i++) {
        $part = '';
        for ($j = 0; $j < 6; $j++) {
            $part .= $chars[rand(0, strlen($chars) - 1)];
        }
        $parts[] = $part;
    }
    return implode('-', $parts);
}

function isLocalAccess() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return $ip === '127.0.0.1' || $ip === '::1' || $ip === 'localhost';
}

function encryptPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function isEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isPhone($phone) {
    return preg_match('/^1[3-9]\d{9}$/', $phone);
}

function maskPhone($phone) {
    return preg_replace('/(\d{3})\d{4}(\d{4})/', '$1****$2', $phone);
}

function maskIdCard($idCard) {
    return preg_replace('/(\d{4})\d+(\d{4})/', '$1**********$2', $idCard);
}

function maskBankCard($cardNo) {
    $len = strlen($cardNo);
    return preg_replace('/(\d{4})\d+' . ($len - 8) . '(\d{4})/', '$1 **** **** $2', $cardNo);
}

function maskTaxId($taxId) {
    return preg_replace('/(.{4}).*(.{4})/', '$1**********$2', $taxId);
}

function getAge($birthDate) {
    return date('Y') - date('Y', strtotime($birthDate));
}

function getGenderFromIdCard($idCard) {
    if (strlen($idCard) === 18) {
        return (int)substr($idCard, 16, 1) % 2 === 1 ? '男' : '女';
    }
    return '未知';
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

function uploadFile($file, $allowedTypes = [], $maxSize = 5242880) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => '文件上传失败'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => '文件大小超过限制'];
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!empty($allowedTypes) && !in_array($ext, $allowedTypes)) {
        return ['success' => false, 'message' => '不支持的文件类型'];
    }
    
    $uploadDir = __DIR__ . '/../uploads/' . date('Ymd') . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => '/uploads/' . date('Ymd') . '/' . $filename
        ];
    }
    
    return ['success' => false, 'message' => '文件保存失败'];
}

function getBillTypeText($type) {
    $types = [
        'transfer_in' => '转入',
        'transfer_out' => '转出',
        'loan_disbursed' => '贷款发放',
        'loan_repayment' => '还款',
        'stock_buy' => '股票买入',
        'stock_sell' => '股票卖出',
        'investment' => '理财投资',
        'investment_redeem' => '理财赎回',
        'invoice' => '发票',
        'fee' => '手续费',
        'other' => '其他'
    ];
    return $types[$type] ?? $type;
}

function getBillTypeClass($type) {
    $classes = [
        'transfer_in' => 'bg-success',
        'transfer_out' => 'bg-danger',
        'loan_disbursed' => 'bg-success',
        'loan_repayment' => 'bg-warning text-dark',
        'stock_buy' => 'bg-info',
        'stock_sell' => 'bg-success',
        'investment' => 'bg-primary',
        'investment_redeem' => 'bg-success',
        'invoice' => 'bg-secondary',
        'fee' => 'bg-danger',
        'other' => 'bg-secondary'
    ];
    return $classes[$type] ?? 'bg-secondary';
}
?>