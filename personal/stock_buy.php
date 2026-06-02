<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function shouldReturnJson() {
    return isAjaxRequest() || stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
}

function respond($data, $code = 200) {
    if (shouldReturnJson()) {
        jsonResponse($data, $code);
    }

    $_SESSION[$data['success'] ? 'success' : 'error'] = $data['message'];
    header('Location: stock.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    respond(['success' => false, 'message' => '请先登录']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'message' => '请求方式错误']);
}

$userId = $_SESSION['user_id'];
$stockCode = sanitize($_POST['stock_code'] ?? '');
$quantity = intval($_POST['quantity'] ?? 0);

if (empty($stockCode) || $quantity <= 0) {
    respond(['success' => false, 'message' => '请填写所有必填项']);
}

$stock = dbGetRow("SELECT * FROM stocks WHERE code = ?", [$stockCode]);
if (!$stock) {
    respond(['success' => false, 'message' => '股票不存在']);
}

$totalShares = $quantity * 100;
$amount = $totalShares * $stock['current_price'];
$fee = $amount * 0.0015;

$cards = dbGetAll("SELECT * FROM cards WHERE user_id = ? AND user_type = 'personal' AND status = 'active'", [$userId]);
if (empty($cards)) {
    respond(['success' => false, 'message' => '您还没有银行卡']);
}

$totalBalance = 0;
foreach ($cards as $card) {
    $totalBalance += $card['balance'];
}

if ($totalBalance < $amount + $fee) {
    respond(['success' => false, 'message' => '余额不足']);
}

dbBeginTransaction();
try {
    $receiptNo = generateReceiptNo();
    
    $usedCard = null;
    $remaining = $amount + $fee;
    foreach ($cards as $card) {
        if ($card['balance'] >= $remaining) {
            dbExecute("UPDATE cards SET balance = balance - ? WHERE id = ?", [$remaining, $card['id']]);
            $usedCard = $card;
            $remaining = 0;
            break;
        } else {
            dbExecute("UPDATE cards SET balance = 0 WHERE id = ?", [$card['id']]);
            $remaining -= $card['balance'];
        }
    }
    
    $existing = dbGetRow("SELECT * FROM stock_holdings WHERE user_id = ? AND user_type = 'personal' AND stock_code = ?", 
        [$userId, $stockCode]);
    
    if ($existing) {
        $newTotalCost = $existing['total_cost'] + $amount;
        $newTotalShares = $existing['quantity'] + $totalShares;
        $newAvgCost = $newTotalCost / $newTotalShares;
        
        dbExecute("UPDATE stock_holdings SET quantity = ?, available_quantity = ?, avg_cost = ?, total_cost = ? WHERE id = ?", 
            [$newTotalShares, $newTotalShares, $newAvgCost, $newTotalCost, $existing['id']]);
    } else {
        dbInsert('stock_holdings', [
            'user_id' => $userId,
            'user_type' => 'personal',
            'stock_code' => $stockCode,
            'quantity' => $totalShares,
            'available_quantity' => $totalShares,
            'avg_cost' => $stock['current_price'],
            'total_cost' => $amount,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    dbInsert('stock_trades', [
        'receipt_no' => $receiptNo,
        'user_id' => $userId,
        'user_type' => 'personal',
        'stock_code' => $stockCode,
        'stock_name' => getStockName($stockCode),
        'type' => 'buy',
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
        'type' => 'stock_buy',
        'amount' => -($amount + $fee),
        'title' => '买入股票 - ' . getStockName($stockCode),
        'content' => '买入' . $totalShares . '股，价格：' . formatMoney($stock['current_price']),
        'related_type' => 'stock_trade',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    dbCommit();
    
    respond([
        'success' => true,
        'message' => '买入成功！回执单号：' . $receiptNo,
        'receipt_no' => $receiptNo
    ]);
    
} catch (Exception $e) {
    dbRollback();
    respond(['success' => false, 'message' => '买入失败：' . $e->getMessage()]);
}
