<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['enterprise_id'])) {
    redirect('login.php');
}

$enterpriseId = $_SESSION['enterprise_id'];
$productId = intval($_GET['id'] ?? 0);

if ($productId <= 0) {
    $_SESSION['error'] = '参数错误';
    redirect('investment.php');
}

$investment = dbGetRow("SELECT i.*, p.name as product_name, p.type as product_type 
    FROM user_investments i 
    LEFT JOIN investments p ON i.product_id = p.id 
    WHERE i.id = ? AND i.user_id = ? AND i.user_type = 'enterprise'", [$productId, $enterpriseId]);

if (!$investment) {
    $_SESSION['error'] = '投资记录不存在';
    redirect('investment.php');
}

if ($investment['product_type'] !== 'flexible' && $investment['status'] === 'invested') {
    $_SESSION['error'] = '该产品尚未到期，无法赎回';
    redirect('investment.php');
}

$card = dbGetRow("SELECT * FROM cards WHERE user_id = ? AND user_type = 'enterprise' AND status = 'active' ORDER BY balance DESC LIMIT 1", [$enterpriseId]);

dbBeginTransaction();
try {
    $actualProfit = $investment['expected_profit'];
    $redeemAmount = $investment['amount'] + $actualProfit;
    
    if ($card) {
        dbExecute("UPDATE cards SET balance = balance + ? WHERE id = ?", [$redeemAmount, $card['id']]);
    }
    
    dbExecute("UPDATE user_investments SET status = 'redeemed', actual_profit = ?, redeemed_at = NOW() WHERE id = ?", 
        [$actualProfit, $investment['id']]);
    
    dbInsert('bills', [
        'user_id' => $enterpriseId,
        'user_type' => 'enterprise',
        'type' => 'investment_redeem',
        'amount' => $redeemAmount,
        'balance' => $card ? $card['balance'] + $redeemAmount : 0,
        'title' => '理财产品赎回 - ' . $investment['product_name'],
        'content' => '赎回本金：' . formatMoney($investment['amount']) . '，收益：' . formatMoney($actualProfit),
        'related_type' => 'investment',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    dbCommit();
    
    $_SESSION['success'] = '赎回成功！本金：' . formatMoney($investment['amount']) . '，收益：' . formatMoney($actualProfit);
    
} catch (Exception $e) {
    dbRollback();
    $_SESSION['error'] = '赎回失败：' . $e->getMessage();
}

redirect('investment.php');
