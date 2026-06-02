<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('invoice.php');
}

$userId = $_SESSION['user_id'];

$type = sanitize($_POST['type'] ?? '');
$title = sanitize($_POST['title'] ?? '');
$taxId = sanitize($_POST['tax_id'] ?? '');
$amount = floatval($_POST['amount'] ?? 0);
$content = sanitize($_POST['content'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$address = sanitize($_POST['address'] ?? '');

if (empty($type) || empty($title) || $amount <= 0 || empty($content) || empty($email)) {
    $_SESSION['error'] = '请填写所有必填项';
    redirect('invoice.php');
}

if (!validateEmail($email)) {
    $_SESSION['error'] = '请输入正确的邮箱地址';
    redirect('invoice.php');
}

if ($type === 'enterprise' && empty($taxId)) {
    $_SESSION['error'] = '企业发票需要提供税号';
    redirect('invoice.php');
}

dbBeginTransaction();
try {
    $receiptNo = generateReceiptNo();
    
    dbInsert('applications', [
        'receipt_no' => $receiptNo,
        'user_id' => $userId,
        'user_type' => 'personal',
        'type' => 'other',
        'title' => '发票申请 - ' . $title,
        'amount' => $amount,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    dbInsert('invoices', [
        'receipt_no' => $receiptNo,
        'user_id' => $userId,
        'user_type' => 'personal',
        'type' => $type,
        'title' => $title,
        'tax_id' => $taxId,
        'amount' => $amount,
        'content' => $content,
        'email' => $email,
        'phone' => $phone,
        'address' => $address,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    dbCommit();
    
    $_SESSION['success'] = '发票申请已提交！回执单号：' . $receiptNo;
    redirect('invoice.php');
    
} catch (Exception $e) {
    dbRollback();
    $_SESSION['error'] = '申请失败：' . $e->getMessage();
    redirect('invoice.php');
}
