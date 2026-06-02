<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$userId = $_SESSION['user_id'];
$loanId = intval($_GET['id'] ?? 0);

$loan = dbGetRow("SELECT * FROM loans WHERE id = ? AND user_id = ? AND user_type = 'personal'", [$loanId, $userId]);

if (!$loan) {
    $_SESSION['error'] = '贷款记录不存在';
    redirect('loan.php');
}

$repayments = dbGetAll("SELECT * FROM repayments WHERE loan_id = ? ORDER BY period", [$loanId]);
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
        <?php $userName = $_SESSION['real_name']; $activeMenu = 'loan'; include __DIR__ . '/../templates/sidebar_personal.php'; ?>
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
            <div class="col-md-6">
                <div class="card-box">
                    <div class="card-title">
                        <i class="bi bi-info-circle me-2"></i>贷款信息
                    </div>
                    <table class="table table-bordered">
                        <tr>
                            <th width="40%">回执单号</th>
                            <td><?php echo $loan['receipt_no']; ?></td>
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
                            <th>贷款用途</th>
                            <td><?php echo htmlspecialchars($loan['purpose']); ?></td>
                        </tr>
                        <tr>
                            <th>贷款状态</th>
                            <td><span class="badge <?php echo getLoanStatusClass($loan['status']); ?>"><?php echo getLoanStatusText($loan['status']); ?></span></td>
                        </tr>
                        <tr>
                            <th>申请时间</th>
                            <td><?php echo formatDate($loan['created_at']); ?></td>
                        </tr>
                        <?php if ($loan['processed_at']): ?>
                        <tr>
                            <th>处理时间</th>
                            <td><?php echo formatDate($loan['processed_at']); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
                
                <?php if ($loan['contract_signed'] && isset($loan['signature_image']) && !empty($loan['signature_image'])): ?>
                <div class="card-box mt-4">
                    <div class="card-title">
                        <i class="bi bi-pen-fill me-2"></i>乙方签名
                    </div>
                    <div class="text-center">
                        <img src="<?php echo htmlspecialchars($loan['signature_image']); ?>" alt="乙方签名" style="max-width: 300px; border: 1px solid #ddd; padding: 10px; background: #fff;">
                    </div>
                    <p class="text-center text-muted mt-2 small">签署时间：<?php echo formatDate($loan['contract_signed_at']); ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-6">
                <?php if ($loan['status'] === 'approved'): ?>
                <div class="card-box">
                    <div class="card-title">
                        <i class="bi bi-file-text me-2"></i>合同签署
                    </div>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i>
                        您的贷款申请已通过审核，请签署合同以完成放款。
                    </div>
                    <a href="loan_contract.php?id=<?php echo $loan['id']; ?>" class="btn btn-success w-100">
                        <i class="bi bi-pen me-2"></i>立即签署合同
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="card-box">
                    <div class="card-title">
                        <i class="bi bi-calendar me-2"></i>还款计划
                    </div>
                    
                    <?php if (empty($repayments)): ?>
                    <p class="text-muted text-center">暂无还款计划</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>期数</th>
                                    <th>到期日</th>
                                    <th>应还本金</th>
                                    <th>应还利息</th>
                                    <th>应还总额</th>
                                    <th>状态</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($repayments as $rp): ?>
                                <tr>
                                    <td><?php echo $rp['period']; ?>/<?php echo $loan['term']; ?></td>
                                    <td><?php echo formatDateShort($rp['due_date']); ?></td>
                                    <td><?php echo formatMoney($rp['principal']); ?></td>
                                    <td><?php echo formatMoney($rp['interest']); ?></td>
                                    <td class="fw-bold"><?php echo formatMoney($rp['amount']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $rp['status'] === 'paid' ? 'bg-success' : ($rp['status'] === 'overdue' ? 'bg-danger' : 'bg-warning text-dark'); ?>">
                                            <?php echo $rp['status'] === 'paid' ? '已还' : ($rp['status'] === 'overdue' ? '逾期' : '待还'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
