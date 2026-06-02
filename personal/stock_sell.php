<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ ::

'/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'message' => '请先登录']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => '请求方式错误']);
}

$userId = $_SESSION['user_id'];
$stockCode = sanitize($_POST['stock_code'] ?? '');
$quantity = intval($_POST['quantity'] ?? 0);

if (empty($stockCode) || $quantity <= 0) {
    jsonResponse(['success' => false, 'message' => '请填写所有必填项']);
}

$stock = dbGetRow("SELECT * FROM stocks WHERE code = ?", [$stockCode]);
if (!$stock) {
    jsonResponse(['success' => false, 'message' => '股票不存在']);
}

$holding = dbGetRow("SELECT * FROM stock_holdings WHERE user_id = ? AND user_type = 'personal' AND stock_code = ?", 
    [$userId, $stockCode]);

if (!$holding || $holding['available_quantity'] < $quantity * 100) {
    jsonResponse(['success' => false, 'message' => '持仓不足']);
}

$totalShares = $quantity * 100;
$amount = $totalShares * $stock['current_price'];
$fee = $amount * 0.0015;
$netAmount = $amount - $fee;

$cards = dbGetAll("SELECT * FROM cards WHERE user_id = ? AND user_type = 'personal' AND status = 'active' ORDER BY balance DESC LIMIT 1", [$userId]);
if (empty($cards)) {
    jsonResponse(['success' => false, 'message' => '您还没有银行卡']);
}

$card = $cards[0];

dbBeginTransaction();
try {
    $receiptNo = generateReceiptNo();
    
    dbExecute("UPDATE cards SET balance = balance + ? WHERE id = ?", [$netAmount, $card['id']]);
    
    dbExecute("UPDATE stock_holdings SET available_quantity = available_quantity - ? WHERE id = ?", 
        [$totalShares, $holding['id']]);
    
    $remainingQty = $holding['quantity'] - $totalShares;
    if ($remainingQty <= 0) {
        dbExecute("DELETE FROM stock_holdings WHERE id = ?", [$holding['id']]);
    } else {
        $costReduction = $holding['avg_cost'] * $totalShares;
        dbExecute("UPDATE stock_holdings SET quantity = ?, total_cost = total_cost - ? WHERE id = ?", 
            [$remainingQty, $costReduction, $holding['id']]);
    }
    
    dbInsert('stock_trades', [
        'receipt_no' => $receiptNo,
        'user_id' => $userId,
        'user_type' => 'personal',
        'stock_code' => $stockCode,
        'stock_name' => getStockName($stockCode),
        'type' => 'sell',
        'price' => $stock['current_price'],
        'quantity' => $totalShares,
        'amount' => $amount,
        'fee' => $fee,
        'status' => 'completed',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    dbInsert('bills', [
        'user_id' => $userId,
        'user_type' => 'personal',
        'type' => 'stock_sell',
        'amount' => $netAmount,
        'balance' => $card['balance'] + $netAmount,
        'title' => '卖出股票 - ' . getStockName($stockCode),
        'content' => '卖出' . $totalShares . '股，价格：' . formatMoney($stock['current_price']),
        'related_type' => 'stock_trade',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    dbCommit();
    
    jsonResponse([
        'success' => true, 
        'message' => '卖出成功！回执单号：' . $receiptNo,
        'receipt_no' => $receiptNo
    ]);
    
} catch (Exception $e) {
    dbRollback();
    jsonResponse(['success' => false, 'message' => '卖出失败：' . $e->getMessage()]);
}
