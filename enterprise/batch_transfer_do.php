<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['enterprise_id'])) {
    jsonResponse(['success' => false, 'message' => '请先登录']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => '请求方式错误']);
}

$enterpriseId = $_SESSION['enterprise_id'];

$fromCardId = intval($_POST['from_card_id'] ?? 0);
$payPassword = $_POST['pay_password'] ?? '';
$toCardNos = $_POST['to_card_no'] ?? [];
$toBanks = $_POST['to_bank'] ?? [];
$toNames = $_POST['to_name'] ?? [];
$itemAmounts = $_POST['item_amount'] ?? [];
$itemRemarks = $_POST['item_remark'] ?? [];

if ($fromCardId <= 0 || empty($toCardNos)) {
    jsonResponse(['success' => false, 'message' => '请填写所有必填项']);
}

$validItems = [];
for ($i = 0; $i < count($toCardNos); $i++) {
    if (!empty($toCardNos[$i]) && !empty($toBanks[$i]) && !empty($toNames[$i]) && !empty($itemAmounts[$i])) {
        $validItems[] = [
            'to_card_no' => sanitize($toCardNos[$i]),
            'to_bank' => sanitize($toBanks[$i]),
            'to_name' => sanitize($toNames[$i]),
            'amount' => floatval($itemAmounts[$i]),
            'remark' => sanitize($itemRemarks[$i] ?? '')
        ];
    }
}

if (empty($validItems)) {
    jsonResponse(['success' => false, 'message' => '请至少添加一条有效的转账记录']);
}

if (count($validItems) > 10) {
    jsonResponse(['success' => false, 'message' => '最多只能添加10个收款方']);
}

$fromCard = dbGetRow("SELECT * FROM cards WHERE id = ? AND user_id = ? AND user_type = 'enterprise' AND status = 'active'", 
    [$fromCardId, $enterpriseId]);

if (!$fromCard) {
    jsonResponse(['success' => false, 'message' => '转出账户不存在或已冻结']);
}

$totalAmount = 0;
foreach ($validItems as $item) {
    $totalAmount += $item['amount'];
}

if ($fromCard['balance'] < $totalAmount) {
    jsonResponse(['success' => false, 'message' => '余额不足']);
}

$enterprise = dbGetRow("SELECT pay_password FROM enterprises WHERE id = ?", [$enterpriseId]);
if (empty($enterprise['pay_password']) || !verifyPassword($payPassword, $enterprise['pay_password'])) {
    jsonResponse(['success' => false, 'message' => '支付密码错误']);
}

dbBeginTransaction();
try {
    $receiptNo = generateReceiptNo();
    
    dbInsert('batch_transfers', [
        'receipt_no' => $receiptNo,
        'from_user_id' => $enterpriseId,
        'from_user_type' => 'enterprise',
        'from_card_id' => $fromCardId,
        'total_amount' => $totalAmount,
        'total_count' => count($validItems),
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    foreach ($validItems as $item) {
        dbInsert('batch_transfer_items', [
            'batch_id' => dbLastInsertId(),
            'to_card_no' => $item['to_card_no'],
            'to_bank' => $item['to_bank'],
            'to_name' => $item['to_name'],
            'amount' => $item['amount'],
            'remark' => $item['remark'],
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        dbInsert('transfers', [
            'receipt_no' => generateReceiptNo(),
            'from_user_id' => $enterpriseId,
            'from_user_type' => 'enterprise',
            'from_card_id' => $fromCardId,
            'to_card_no' => $item['to_card_no'],
            'to_bank' => $item['to_bank'],
            'amount' => $item['amount'],
            'remark' => $item['remark'],
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    dbInsert('applications', [
        'receipt_no' => $receiptNo,
        'user_id' => $enterpriseId,
        'user_type' => 'enterprise',
        'type' => 'transfer',
        'title' => '企业批量转账申请（' . count($validItems) . '笔，共' . formatMoney($totalAmount) . '元）',
        'amount' => $totalAmount,
        'content' => json_encode($validItems),
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    dbCommit();
    
    jsonResponse([
        'success' => true, 
        'message' => '批量转账申请已提交，共' . count($validItems) . '笔，金额' . formatMoney($totalAmount) . '元，等待审核！回执单号：' . $receiptNo,
        'receipt_no' => $receiptNo
    ]);
    
} catch (Exception $e) {
    dbRollback();
    jsonResponse(['success' => false, 'message' => '提交失败：' . $e->getMessage()]);
}
