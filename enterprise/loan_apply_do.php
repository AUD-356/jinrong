<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['enterprise_id'])) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('loan.php');
}

$debugLog = __DIR__ . '/../uploads/loan_apply_debug.log';
file_put_contents($debugLog, "[enterprise] " . date('Y-m-d H:i:s') . " POST=" . json_encode($_POST, JSON_UNESCAPED_UNICODE) . " SESSION=" . json_encode([ 'enterprise_id' => $_SESSION['enterprise_id'] ?? null, 'loan_receipt_no' => $_SESSION['loan_receipt_no'] ?? null ], JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);

$enterpriseId = $_SESSION['enterprise_id'];

$productId = intval($_POST['product_id'] ?? 0);
$amount = floatval(preg_replace('/[^\d.]/', '', $_POST['amount'] ?? '0'));
$term = intval($_POST['term'] ?? 0);
$rate = floatval($_POST['rate'] ?? 0);
$purpose = sanitize($_POST['purpose'] ?? '');
$income = floatval(preg_replace('/[^\d.]/', '', $_POST['income'] ?? '0'));
$employmentStatus = sanitize($_POST['employment_status'] ?? '');
$applicationContent = sanitize($_POST['application_content'] ?? '');

if ($amount <= 0 || $term <= 0 || $rate <= 0 || empty($purpose) || empty($employmentStatus) || empty($applicationContent)) {
    $_SESSION['error'] = '请填写所有必填项';
    redirect('loan_apply.php');
}

if ($income < 0 || $income > 99999999999.99) {
    $_SESSION['error'] = '月营业额金额超出范围，请输入合理的金额';
    redirect('loan_apply.php');
}

$monthlyRate = $rate / 100 / 12;
if ($monthlyRate > 0) {
    $monthlyPayment = $amount * $monthlyRate * pow(1 + $monthlyRate, $term) / (pow(1 + $monthlyRate, $term) - 1);
} else {
    $monthlyPayment = $amount / $term;
}
$totalPayment = $monthlyPayment * $term;

if ($productId > 0) {
    $product = dbGetRow("SELECT * FROM loan_products WHERE id = ?", [$productId]);
    $productName = $product['name'];
} else {
    $productName = '企业贷款';
}

dbBeginTransaction();
try {
    $receiptNo = generateReceiptNo();
    
    if (!dbColumnExists('loans', 'application_content')) {
        dbAddColumnText('loans', 'application_content');
    }
    
    dbInsert('applications', [
        'receipt_no' => $receiptNo,
        'user_id' => $enterpriseId,
        'user_type' => 'enterprise',
        'type' => 'loan',
        'title' => '企业贷款申请 - ' . $productName,
        'amount' => $amount,
        'content' => json_encode([
            'product_name' => $productName,
            'amount' => $amount,
            'term' => $term,
            'rate' => $rate,
            'monthly_payment' => $monthlyPayment,
            'total_payment' => $totalPayment,
            'purpose' => $purpose,
            'income' => $income,
            'employment_status' => $employmentStatus,
            'application_content' => $applicationContent
        ]),
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    dbInsert('loans', [
        'receipt_no' => $receiptNo,
        'user_id' => $enterpriseId,
        'user_type' => 'enterprise',
        'product_id' => $productId ?: null,
        'amount' => $amount,
        'term' => $term,
        'rate' => $rate,
        'monthly_payment' => $monthlyPayment,
        'total_payment' => $totalPayment,
        'purpose' => $purpose,
        'income' => $income,
        'employment_status' => $employmentStatus,
        'application_content' => $applicationContent,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    dbCommit();
    
    file_put_contents($debugLog, "[enterprise] " . date('Y-m-d H:i:s') . " SUCCESS receipt_no={$receiptNo}\n", FILE_APPEND);
    $_SESSION['loan_receipt_no'] = $receiptNo;
    redirect('loan_status.php');
    
} catch (Exception $e) {
    dbRollback();
    file_put_contents($debugLog, "[enterprise] " . date('Y-m-d H:i:s') . " ERROR=" . $e->getMessage() . "\n", FILE_APPEND);
    echo '<pre>申请失败：' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
    exit;
}
