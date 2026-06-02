<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['pending_username']) || !isset($_SESSION['pending_type'])) {
    redirect('login.php');
}

$username = $_SESSION['pending_username'];

// 清除session中的pending信息
unset($_SESSION['pending_username']);
unset($_SESSION['pending_type']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>申请待审核 - <?php echo SITE_NAME; ?></title>
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
                <h3><i class="bi bi-hourglass-split me-2"></i>申请待审核</h3>
                <p class="mb-0 mt-2 opacity-75">您的企业账户正在审核中</p>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <i class="bi bi-clock-history text-warning" style="font-size: 64px;"></i>
                    <h4 class="mt-3 text-warning">审核中</h4>
                </div>
                
                <div class="alert alert-info">
                    <div class="row">
                        <div class="col-4">用户名</div>
                        <div class="col-8"><strong><?php echo htmlspecialchars($username); ?></strong></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-4">当前状态</div>
                        <div class="col-8">
                            <span class="badge bg-warning text-dark">待审核</span>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-warning">
                    <i class="bi bi-info-circle me-2"></i>
                    您的企业注册申请正在审核中，请耐心等待管理员审核通过。审核通过后，您将获得激活码并可以登录系统。
                </div>
                
                <div class="d-grid gap-2">
                    <a href="login.php" class="btn btn-primary">
                        <i class="bi bi-arrow-left me-2"></i>返回登录
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
