<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['register_receipt_no'])) {
    redirect('register.php');
}

$receiptNo = $_SESSION['register_receipt_no'];
$username = $_SESSION['register_username'];

$application = dbGetRow("SELECT * FROM applications WHERE receipt_no = ?", [$receiptNo]);

$statusText = getStatusText($application['status']);
$statusClass = getStatusClass($application['status']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注册状态 - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); min-height: 100vh; }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-card" style="width: 500px;">
            <div class="card-header" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                <h3><i class="bi bi-clipboard-check me-2"></i>注册申请提交</h3>
                <p class="mb-0 mt-2 opacity-75">请保存好您的回执单号</p>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <?php if ($application['status'] === 'pending'): ?>
                    <i class="bi bi-hourglass-split text-warning" style="font-size: 64px;"></i>
                    <h4 class="mt-3 text-warning">待审核</h4>
                    <?php elseif ($application['status'] === 'approved' || $application['status'] === 'completed'): ?>
                    <i class="bi bi-check-circle text-success" style="font-size: 64px;"></i>
                    <h4 class="mt-3 text-success">已通过</h4>
                    <?php else: ?>
                    <i class="bi bi-x-circle text-danger" style="font-size: 64px;"></i>
                    <h4 class="mt-3 text-danger">未通过</h4>
                    <?php endif; ?>
                </div>
                
                <div class="alert alert-info">
                    <div class="row">
                        <div class="col-4">用户名</div>
                        <div class="col-8"><?php echo htmlspecialchars($username); ?></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-4">回执单号</div>
                        <div class="col-8">
                            <strong class="text-primary" id="receiptNo"><?php echo htmlspecialchars($receiptNo); ?></strong>
                            <button class="btn btn-sm btn-outline-primary ms-2" onclick="copyReceipt()">
                                <i class="bi bi-clipboard"></i> 复制
                            </button>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-4">申请状态</div>
                        <div class="col-8">
                            <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-4">提交时间</div>
                        <div class="col-8"><?php echo formatDate($application['created_at']); ?></div>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <a href="login.php" class="btn btn-success">
                        <i class="bi bi-box-arrow-in-right me-2"></i>去登录
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyReceipt() {
            const text = document.getElementById('receiptNo').innerText;
            navigator.clipboard.writeText(text).then(function() {
                alert('回执单号已复制到剪贴板');
            });
        }
        
        setTimeout(function() {
            location.reload();
        }, 10000);
    </script>
</body>
</html>
