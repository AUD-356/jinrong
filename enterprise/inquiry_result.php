<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['inquiry_result'])) {
    redirect('inquiry.php');
}

$application = $_SESSION['inquiry_result'];

$statusText = getStatusText($application['status']);
$statusClass = getStatusClass($application['status']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>查询结果 - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); min-height: 100vh; }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-card" style="width: 600px;">
            <div class="card-header" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                <h3><i class="bi bi-clipboard-data me-2"></i>申请详情</h3>
                <p class="mb-0 mt-2 opacity-75">以下是该申请的最新状态</p>
            </div>
            <div class="card-body">
                <div class="alert alert-<?php echo str_replace('bg-', '', $statusClass); ?> mb-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-hourglass-split fs-1 me-3"></i>
                        <div>
                            <h4 class="mb-1"><?php echo $statusText; ?></h4>
                            <p class="mb-0 opacity-75">最后更新时间：<?php echo formatDate($application['updated_at']); ?></p>
                        </div>
                    </div>
                </div>
                
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">回执单号</th>
                        <td><strong class="text-primary"><?php echo htmlspecialchars($application['receipt_no']); ?></strong></td>
                    </tr>
                    <tr>
                        <th>用户类型</th>
                        <td>企业用户</td>
                    </tr>
                    <tr>
                        <th>申请标题</th>
                        <td><?php echo htmlspecialchars($application['title']); ?></td>
                    </tr>
                    <?php if ($application['amount'] > 0): ?>
                    <tr>
                        <th>申请金额</th>
                        <td class="text-danger fw-bold"><?php echo formatMoney($application['amount']); ?> 元</td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th>当前状态</th>
                        <td><span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                    </tr>
                    <tr>
                        <th>提交时间</th>
                        <td><?php echo formatDate($application['created_at']); ?></td>
                    </tr>
                    <?php if ($application['processed_at']): ?>
                    <tr>
                        <th>处理时间</th>
                        <td><?php echo formatDate($application['processed_at']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($application['admin_remark'])): ?>
                    <tr>
                        <th>处理备注</th>
                        <td><?php echo htmlspecialchars($application['admin_remark']); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
                
                <div class="d-grid gap-2 mt-4">
                    <a href="inquiry.php" class="btn btn-outline-success">
                        <i class="bi bi-arrow-left me-2"></i>继续查询
                    </a>
                    <a href="login.php" class="btn btn-success">
                        <i class="bi bi-box-arrow-in-right me-2"></i>去登录
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
