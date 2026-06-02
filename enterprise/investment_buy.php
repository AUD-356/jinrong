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
$productId = intval($_POST['product_id'] ?? 0);
$amount = floatval($_POST['amount'] ?? 0);

if ($productId <= 0 || $amount <= 0) {
    jsonResponse(['success' => false, 'message' => '请填写所有必填项']);
}

$product = dbGetRow("SELECT * FROM investments WHERE id = ? AND status = 'active'", [$productId]);
if (!$product) {
    jsonResponse(['success' => false, 'message' => '产品不存在或已下架']);
}

if ($amount < $product['min_amount']) {
    jsonResponse(['success' => false, 'message' => '投资金额低于起投金额']);
}

if ($product['max_amount'] < 999999999 && $amount > $product['max_amount']) {
    jsonResponse(['success' => false, 'message' => '投资金额超出单笔限额']);
}

$cards = dbGetAll("SELECT * FROM cards WHERE user_id = ? AND user_type = 'enterprise' AND status = 'active'", [$enterpriseId]);
if (empty($cards)) {
    jsonResponse(['success' => false, 'message' => '您还没有银行卡']);
}

$totalBalance = 0;
foreach ($cards as $card) {
    $totalBalance += $card['balance'];
}

if ($totalBalance < $amount) {
    jsonResponse(['success' => false, 'message' => '余额不足']);
}

dbBeginTransaction();
try {
    $receiptNo = generateReceiptNo();
    
    $usedCard = null;
    $remaining = $amount;
    foreach ($cards as $card) {
        if ($card['balance'] >= $remaining) {
            dbExecute("UPDATE cards SET balance = balance - ? WHERE id = ?", [$remaining, $card['id']]);
            $usedCard = $card;
            break;
        }
    }
    
    $expectedProfit = $amount * $product['expected_rate'] / 100;
    $termDays = $product['term_days'];
    
    if ($product['type'] === 'flexible') {
        $startDate = date('Y-m-d');
        $endDate = null;
    } else {
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+' . $termDays . ' days'));
    }
    
    dbInsert('user_investments', [
        'receipt_no' => $receiptNo,
        'user_id' => $enterpriseId,
        'user_type' => 'enterprise',
        'product_id' => $productId,
        'product_name' => $product['name'],
        'amount' => $amount,
        'expected_rate' => $product['expected_rate'],
        'expected_profit' => $expectedProfit,
        'term_days' => $termDays,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'status' => 'invested',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    dbInsert('bills', [
        'user_id' => $enterpriseId,
        'user_type' => 'enterprise',
        'type' => 'investment',
        'amount' => -$amount,
        'title' => '购买理财产品 - ' . $product['name'],
        'content' => '投资金额：' . formatMoney($amount) . '，预期收益：' . formatMoney($expectedProfit),
        'related_type' => 'investment',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    dbCommit();
    
    jsonResponse([
        'success' => true, 
        'message' => '购买成功！回执单号：' . $receiptNo,
        'receipt_no' => $receiptNo
    ]);
    
} catch (Exception $e) {
    dbRollback();
    jsonResponse(['success' => false, 'message' => '购买失败：' . $e->getMessage()]);
}
