<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['enterprise_id'])) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('cards_apply.php');
}

$enterpriseId = $_SESSION['enterprise_id'];
$enterprise = dbGetRow("SELECT * FROM enterprises WHERE id = ?", [$enterpriseId]);

$cardType = sanitize($_POST['card_type'] ?? 'debit');
$cardHolder = sanitize($_POST['card_holder'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$legalName = sanitize($_POST['legal_name'] ?? '');
$legalIdCard = sanitize($_POST['legal_id_card'] ?? '');

if (empty($cardHolder) || empty($phone) || empty($legalName) || empty($legalIdCard)) {
    $_SESSION['error'] = '请填写所有必填项';
    redirect('cards_apply.php');
}

if (!validatePhone($phone)) {
    $_SESSION['error'] = '请输入正确的手机号码';
    redirect('cards_apply.php');
}

if (!preg_match('/^\d{17}[\dXx]$/', $legalIdCard)) {
    $_SESSION['error'] = '请输入正确的身份证号码';
    redirect('cards_apply.php');
}

$cardCount = dbGetOne("SELECT COUNT(*) FROM cards WHERE user_id = ? AND user_type = 'enterprise'", [$enterpriseId]);
if ($cardCount >= 10) {
    $_SESSION['error'] = '您已达到最大开户数量（10张）';
    redirect('cards_apply.php');
}

function uploadCardFile($file, $type) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    $allowedTypes = ['image/jpeg', 'image/png'];
    if (!in_array($file['type'], $allowedTypes)) {
        return null;
    }
    
    $maxSize = 2 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return null;
    }
    
    $uploadDir = __DIR__ . '/../uploads/cards/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '_' . $type . '.' . $extension;
    $targetPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return 'uploads/cards/' . $fileName;
    }
    
    return null;
}

$businessLicense = uploadCardFile($_FILES['business_license'] ?? [], 'license');

if (!$businessLicense) {
    $_SESSION['error'] = '营业执照上传失败';
    redirect('cards_apply.php');
}

$receiptNo = generateReceiptNo();

dbBeginTransaction();
try {
    $cardId = dbInsert('cards', [
        'user_id' => $enterpriseId,
        'user_type' => 'enterprise',
        'card_no' => 'PENDING',
        'card_pwd' => '',
        'bank_code' => 'PENDING',
        'card_type' => $cardType,
        'card_holder' => $cardHolder,
        'phone' => $phone,
        'balance' => 0.00,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    error_log("Card ID created: " . $cardId);
    
    dbInsert('applications', [
        'receipt_no' => $receiptNo,
        'user_id' => $enterpriseId,
        'user_type' => 'enterprise',
        'type' => 'card_open',
        'title' => '企业银行卡开户申请',
        'content' => json_encode([
            'card_type' => $cardType,
            'card_holder' => $cardHolder,
            'phone' => $phone,
            'legal_name' => $legalName,
            'legal_id_card' => $legalIdCard,
            'company_name' => $enterprise['company_name'],
            'tax_id' => $enterprise['tax_id'],
            'card_id' => $cardId,
            'business_license' => $businessLicense
        ]),
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    error_log("Application created with receipt: " . $receiptNo);

    dbCommit();
    error_log("Transaction committed successfully");
} catch (Exception $e) {
    error_log("Error in card application: " . $e->getMessage());
    dbRollback();
    $_SESSION['error'] = '申请失败，请稍后重试: ' . $e->getMessage();
    redirect('cards_apply.php');
}

$_SESSION['success'] = '开户申请已提交！回执单号：' . $receiptNo;
$_SESSION['receipt_no'] = $receiptNo;
redirect('cards_apply_status.php');
?>
