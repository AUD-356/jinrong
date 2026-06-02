<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['loan_receipt_no'])) {
    redirect('loan.php');
}

$receiptNo = $_SESSION['loan_receipt_no'];
$loan = dbGetRow("SELECT * FROM loans WHERE receipt_no = ?", [$receiptNo]);

$statusText = getLoanStatusText($loan['status']);
$statusClass = getLoanStatusClass($loan['status']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>贷款申请提交 - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-card" style="width: 550px;">
            <div class="card-header">
                <h3><i class="bi bi-check-circle me-2"></i>贷款申请已提交</h3>
                <p class="mb-0 mt-2 opacity-75">请等待审核结果</p>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <?php if ($loan['status'] === 'pending'): ?>
                    <i class="bi bi-hourglass-split text-warning" style="font-size: 64px;"></i>
                    <?php elseif ($loan['status'] === 'approved'): ?>
                    <i class="bi bi-check-circle text-success" style="font-size: 64px;"></i>
                    <?php elseif ($loan['status'] === 'contract_signed'): ?>
                    <i class="bi bi-file-earmark-check text-primary" style="font-size: 64px;"></i>
                    <?php else: ?>
                    <i class="bi bi-clock text-info" style="font-size: 64px;"></i>
                    <?php endif; ?>
                </div>
                
                <div class="alert alert-<?php echo str_replace('bg-', '', $statusClass); ?> mb-4">
                    <h4 class="alert-heading mb-0">
                        <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                    </h4>
                </div>
                
                <table class="table table-bordered">
                    <tr>
                        <th width="35%">回执单号</th>
                        <td><strong class="text-primary"><?php echo htmlspecialchars($receiptNo); ?></strong></td>
                    </tr>
                    <tr>
                        <th>贷款金额</th>
                        <td class="text-danger fw-bold fs-5"><?php echo formatMoney($loan['amount']); ?> 元</td>
                    </tr>
                    <tr>
                        <th>贷款期限</th>
                        <td><?php echo $loan['term']; ?>个月</td>
                    </tr>
                    <tr>
                        <th>年利率</th>
                        <td><?php echo $loan['rate']; ?>%</td>
                    </tr>
                    <tr>
                        <th>月供</th>
                        <td class="text-primary fw-bold"><?php echo formatMoney($loan['monthly_payment']); ?> 元</td>
                    </tr>
                    <tr>
                        <th>总还款</th>
                        <td><?php echo formatMoney($loan['total_payment']); ?> 元</td>
                    </tr>
                    <tr>
                        <th>提交时间</th>
                        <td><?php echo formatDate($loan['created_at']); ?></td>
                    </tr>
                </table>
                
                <?php if ($loan['status'] === 'approved'): ?>
                <div class="alert alert-success mt-4">
                    <i class="bi bi-check-circle me-2"></i>
                    恭喜！您的贷款申请已通过审核。请点击下方按钮签署合同。
                </div>
                <a href="loan_contract.php?id=<?php echo $loan['id']; ?>" class="btn btn-success w-100 mb-2">
                    <i class="bi bi-file-earmark-text me-2"></i>立即签署合同
                </a>
                <?php elseif ($loan['status'] === 'contract_signed'): ?>
                <div class="alert alert-info mt-4">
                    <i class="bi bi-info-circle me-2"></i>
                    合同已签署，等待放款中。
                </div>
                <?php else: ?>
                <div class="alert alert-info mt-4">
                    <i class="bi bi-lightbulb me-2"></i>
                    请保存好回执单号，审核结果将会通过系统通知您。审核通常需要1-3个工作日。
                </div>
                <?php endif; ?>
                
                <div class="d-grid gap-2 mt-4">
                    <a href="loan.php" class="btn btn-primary">
                        <i class="bi bi-cash-stack me-2"></i>返回贷款页面
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
