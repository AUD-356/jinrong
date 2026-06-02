<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['enterprise_id'])) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('loan.php');
}

$enterpriseId = $_SESSION['enterprise_id'];
$loanId = intval($_POST['loan_id'] ?? 0);
$signatureImage = $_POST['signature'] ?? '';

$loan = dbGetRow("SELECT * FROM loans WHERE id = ? AND user_id = ? AND user_type = 'enterprise' AND status = 'approved'", [$loanId, $enterpriseId]);

if (!$loan) {
    $_SESSION['error'] = '贷款记录不存在或状态不正确';
    redirect('loan.php');
}

$contractNo = 'HT' . date('Ymd') . $loan['id'];

// 安全更新，先尝试更新包含签名的版本，失败则更新不包含签名的版本
try {
    dbExecute("UPDATE loans SET contract_no = ?, contract_signed = 1, contract_signed_at = NOW(), signature_image = ?, status = 'contract_signed', updated_at = NOW() WHERE id = ?", 
        [$contractNo, $signatureImage, $loanId]);
} catch (Exception $e) {
    // 如果字段不存在，则只更新基本字段
    dbExecute("UPDATE loans SET contract_no = ?, contract_signed = 1, contract_signed_at = NOW(), status = 'contract_signed', updated_at = NOW() WHERE id = ?", 
        [$contractNo, $loanId]);
}

$term = $loan['term'];
$monthlyPayment = $loan['monthly_payment'];
$monthlyRate = $loan['rate'] / 100 / 12;
$principal = $loan['amount'];

for ($i = 1; $i <= $term; $i++) {
    $interest = $principal * $monthlyRate;
    $principalPayment = $monthlyPayment - $interest;
    $principal -= $principalPayment;
    
    $dueDate = date('Y-m-d', strtotime('+' . $i . ' month'));
    
    dbInsert('repayments', [
        'loan_id' => $loanId,
        'user_id' => $enterpriseId,
        'user_type' => 'enterprise',
        'period' => $i,
        'principal' => $principalPayment,
        'interest' => $interest,
        'amount' => $monthlyPayment,
        'due_date' => $dueDate,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s')
    ]);
}

$_SESSION['success'] = '合同签署成功！';
redirect('loan_detail.php?id=' . $loanId);
?>