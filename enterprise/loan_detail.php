<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['enterprise_id'])) {
    redirect('login.php');
}

$enterpriseId = $_SESSION['enterprise_id'];
$loanId = intval($_GET['id'] ?? 0);

if ($loanId <= 0) {
    $_SESSION['error'] = '参数错误';
    redirect('loan.php');
}

$loan = dbGetRow("SELECT * FROM loans WHERE id = ? AND user_id = ? AND user_type = 'enterprise'", [$loanId, $enterpriseId]);

if (!$loan) {
    $_SESSION['error'] = '贷款记录不存在';
    redirect('loan.php');
}

$statusText = getLoanStatusText($loan['status']);
$statusClass = getLoanStatusClass($loan['status']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>贷款详情 - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="sidebar">
        <?php $userName = $_SESSION['company_name']; $activeMenu = 'loan'; include __DIR__ . '/../templates/sidebar_enterprise.php'; ?>
    </div>
    
    <div class="main-content">
        <div class="page-header">
            <h2><i class="bi bi-file-earmark-text me-2"></i>贷款详情</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">首页</a></li>
                    <li class="breadcrumb-item"><a href="loan.php">贷款</a></li>
                    <li class="breadcrumb-item active">详情</li>
                </ol>
            </nav>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card-box">
                    <div class="card-title">
                        <i class="bi bi-cash-stack me-2"></i>贷款信息
                    </div>
                    
                    <table class="table table-borderless">
                        <tr>
                            <th width="35%">回执单号</th>
                            <td><?php echo htmlspecialchars($loan['receipt_no']); ?></td>
                        </tr>
                        <tr>
                            <th>贷款用途</th>
                            <td><?php echo htmlspecialchars($loan['purpose']); ?></td>
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
                            <td class="text-success fw-bold"><?php echo formatMoney($loan['monthly_payment']); ?> 元</td>
                        </tr>
                        <tr>
                            <th>总还款</th>
                            <td><?php echo formatMoney($loan['total_payment']); ?> 元</td>
                        </tr>
                        <tr>
                            <th>月营业额</th>
                            <td><?php echo formatMoney($loan['income']); ?> 元</td>
                        </tr>
                        <tr>
                            <th>企业经营年限</th>
                            <td><?php echo htmlspecialchars($loan['employment_status']); ?></td>
                        </tr>
                        <tr>
                            <th>申请时间</th>
                            <td><?php echo formatDate($loan['created_at']); ?></td>
                        </tr>
                        <tr>
                            <th>当前状态</th>
                            <td><span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                        </tr>
                    </table>
                    
                    <?php if ($loan['contract_signed'] && isset($loan['signature_image']) && !empty($loan['signature_image'])): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <i class="bi bi-pen-fill me-2"></i>乙方法定代表人签名
                        </div>
                        <div class="card-body text-center">
                            <img src="<?php echo htmlspecialchars($loan['signature_image']); ?>" alt="乙方法定代表人签名" style="max-width: 300px; border: 1px solid #ddd; padding: 10px; background: #fff;">
                            <p class="text-center text-muted mt-2 small">签署时间：<?php echo formatDate($loan['contract_signed_at']); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($loan['status'] === 'approved'): ?>
                    <div class="alert alert-success mt-4">
                        <i class="bi bi-check-circle me-2"></i>
                        您的贷款申请已通过审核，请尽快签署合同。
                    </div>
                    <a href="loan_contract.php?id=<?php echo $loan['id']; ?>" class="btn btn-success w-100">
                        <i class="bi bi-file-earmark-text me-2"></i>立即签署合同
                    </a>
                    <?php elseif ($loan['status'] === 'disbursed'): ?>
                    <div class="alert alert-info mt-4">
                        <i class="bi bi-info-circle me-2"></i>
                        贷款已发放至您的企业账户。
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card-box">
                    <div class="card-title">
                        <i class="bi bi-question-circle me-2"></i>还款说明
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            每月固定日期自动扣款
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            请确保企业银行卡余额充足
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            逾期将产生滞纳金
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            如有疑问请联系客服
                        </li>
                    </ul>
                </div>
                
                <div class="card-box mt-4">
                    <div class="card-title">
                        <i class="bi bi-telephone me-2"></i>联系客服
                    </div>
                    <p class="text-muted">
                        如有贷款相关问题，请拨打客服热线：<br>
                        <strong>400-888-8888</strong><br>
                        服务时间：周一至周五 9:00-18:00
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
</body>
</html>
