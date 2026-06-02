<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('cards_apply.php');
}

$userId = $_SESSION['user_id'];
$user = dbGetRow("SELECT * FROM users WHERE id = ?", [$userId]);

$cardType = sanitize($_POST['card_type'] ?? 'debit');
$cardHolder = sanitize($_POST['card_holder'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$idCard = sanitize($_POST['id_card'] ?? '');

if (empty($cardHolder) || empty($phone) || empty($idCard)) {
    $_SESSION['error'] = '请填写所有必填项';
    redirect('cards_apply.php');
}

if (!validatePhone($phone)) {
    $_SESSION['error'] = '请输入正确的手机号码';
    redirect('cards_apply.php');
}

if (!preg_match('/^\d{17}[\dXx]$/', $idCard)) {
    $_SESSION['error'] = '请输入正确的身份证号码';
    redirect('cards_apply.php');
}

$cardCount = dbGetOne("SELECT COUNT(*) FROM cards WHERE user_id = ? AND user_type = 'personal'", [$userId]);
if ($cardCount >= 5) {
    $_SESSION['error'] = '您已达到最大开户数量（5张）';
    redirect('cards_apply.php');
}

$receiptNo = generateReceiptNo();

dbBeginTransaction();
try {
    $cardId = dbInsert('cards', [
        'user_id' => $userId,
        'user_type' => 'personal',
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
        'user_id' => $userId,
        'user_type' => 'personal',
        'type' => 'card_open',
        'title' => '银行卡开户申请',
        'content' => json_encode([
            'card_type' => $cardType,
            'card_holder' => $cardHolder,
            'phone' => $phone,
            'id_card' => $idCard,
            'real_name' => $user['real_name'],
            'card_id' => $cardId
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
