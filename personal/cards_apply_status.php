<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['receipt_no'])) {
    redirect('cards.php');
}

$receiptNo = $_SESSION['receipt_no'];
$application = dbGetRow("SELECT * FROM applications WHERE receipt_no = ?", [$receiptNo]);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>开户申请提交 - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-card" style="width: 500px;">
            <div class="card-header">
                <h3><i class="bi bi-check-circle me-2"></i>申请提交成功</h3>
                <p class="mb-0 mt-2 opacity-75">您的开户申请已提交</p>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <i class="bi bi-hourglass-split text-warning" style="font-size: 64px;"></i>
                </div>
                
                <div class="alert alert-info">
                    <div class="row">
                        <div class="col-4">回执单号</div>
                        <div class="col-8">
                            <strong class="text-primary"><?php echo htmlspecialchars($receiptNo); ?></strong>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-4">申请类型</div>
                        <div class="col-8">银行卡开户</div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-4">当前状态</div>
                        <div class="col-8">
                            <span class="badge bg-warning text-dark">待审核</span>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-4">提交时间</div>
                        <div class="col-8"><?php echo formatDate($application['created_at']); ?></div>
                    </div>
                </div>
                
                <div class="alert alert-warning">
                    <i class="bi bi-lightbulb me-2"></i>
                    请保存好回执单号，您可以使用此回执单号查询审核进度。审核通常在1-3个工作日内完成。
                </div>
                
                <div class="d-grid gap-2">
                    <a href="cards.php" class="btn btn-primary">
                        <i class="bi bi-credit-card me-2"></i>返回银行卡页面
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
