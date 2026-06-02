<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'message' => '请先登录']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => '请求方式错误']);
}

$userId = $_SESSION['user_id'];

$fromCardId = intval($_POST['from_card_id'] ?? 0);
$toCardNo = sanitize($_POST['to_card_no'] ?? '');
$toBank = sanitize($_POST['to_bank'] ?? '');
$amount = floatval($_POST['amount'] ?? 0);
$remark = sanitize($_POST['remark'] ?? '');
$payPassword = $_POST['pay_password'] ?? '';

if ($fromCardId <= 0 || empty($toCardNo) || empty($toBank) || $amount <= 0) {
    jsonResponse(['success' => false, 'message' => '请填写所有必填项']);
}

if (!validateBankCard($toCardNo)) {
    jsonResponse(['success' => false, 'message' => '请输入正确的银行卡号']);
}

if (!validateAmount($amount, 0.01, 5000000)) {
    jsonResponse(['success' => false, 'message' => '转账金额超出限制']);
}

$fromCard = dbGetRow("SELECT * FROM cards WHERE id = ? AND user_id = ? AND user_type = 'personal' AND status = 'active'", 
    [$fromCardId, $userId]);

if (!$fromCard) {
    jsonResponse(['success' => false, 'message' => '转出账户不存在或已冻结']);
}

if ($fromCard['balance'] < $amount) {
    jsonResponse(['success' => false, 'message' => '余额不足']);
}

$user = dbGetRow("SELECT pay_password FROM users WHERE id = ?", [$userId]);
if (empty($user['pay_password']) || !verifyPassword($payPassword, $user['pay_password'])) {
    jsonResponse(['success' => false, 'message' => '支付密码错误']);
}

dbBeginTransaction();
try {
    $receiptNo = generateReceiptNo();
    
    $toCardId = null;
    $cleanToCardNo = normalizeCardNo($toCardNo);
    $toCard = dbGetRow("SELECT * FROM cards WHERE REPLACE(REPLACE(REPLACE(card_no, ' ', ''), '-', ''), '　', '') = ? AND status = 'active' LIMIT 1", [$cleanToCardNo]);
    if ($toCard) {
        $toCardId = $toCard['id'];
    }

    if ($amount > PERSONAL_TRANSFER_LIMIT) {
            $status = 'pending';
            dbInsert('applications', [
                'receipt_no' => $receiptNo,
                'user_id' => $userId,
                'user_type' => 'personal',
                'type' => 'transfer',
                'title' => '个人转账申请（待审核）',
                'amount' => $amount,
                'content' => json_encode([
                    'from_card_id' => $fromCardId,
                    'from_card_no' => $fromCard['card_no'],
                    'to_card_no' => $toCardNo,
                    'to_bank' => $toBank,
                    'remark' => $remark
                ]),
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            $status = 'completed';
            dbExecute("UPDATE cards SET balance = balance - ? WHERE id = ?", [$amount, $fromCardId]);
            
            // 获取更新后的转出账户余额
            $updatedFromCard = dbGetRow("SELECT balance FROM cards WHERE id = ?", [$fromCardId]);
            
            dbInsert('bills', [
                'user_id' => $userId,
                'user_type' => 'personal',
                'type' => 'transfer_out',
                'amount' => -$amount,
                'balance' => $updatedFromCard['balance'],
                'title' => '转账支出',
                'content' => '向' . getBankName($toBank) . '转账',
                'related_id' => $fromCardId,
                'related_type' => 'card',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            if ($toCard) {
                dbExecute("UPDATE cards SET balance = balance + ? WHERE id = ?", [$amount, $toCard['id']]);
                
                $updatedToCard = dbGetRow("SELECT balance FROM cards WHERE id = ?", [$toCard['id']]);
                
                dbInsert('bills', [
                    'user_id' => $toCard['user_id'],
                    'user_type' => $toCard['user_type'],
                    'type' => 'transfer_in',
                    'amount' => $amount,
                    'balance' => $updatedToCard['balance'],
                    'title' => '转账收入',
                    'content' => '从' . getBankName($fromCard['bank_code']) . '转入',
                    'related_id' => $toCard['id'],
                    'related_type' => 'card',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
    
    dbInsert('transfers', [
        'receipt_no' => $receiptNo,
        'from_user_id' => $userId,
        'from_user_type' => 'personal',
        'from_card_id' => $fromCardId,
        'to_card_id' => $toCardId,
        'to_card_no' => $toCardNo,
        'to_bank' => $toBank,
        'amount' => $amount,
        'remark' => $remark,
        'status' => $status,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    dbCommit();
    
    $message = $amount > PERSONAL_TRANSFER_LIMIT 
        ? '转账申请已提交，等待审核！回执单号：' . $receiptNo 
        : '转账成功！回执单号：' . $receiptNo;
    
    jsonResponse(['success' => true, 'message' => $message, 'receipt_no' => $receiptNo]);
    
} catch (Exception $e) {
    dbRollback();
    jsonResponse(['success' => false, 'message' => '转账失败：' . $e->getMessage()]);
}
