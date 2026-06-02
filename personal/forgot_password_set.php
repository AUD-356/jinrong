<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ ::

'/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['forgot_receipt_no'])) {
    redirect('forgot_password.php');
}

$receiptNo = $_SESSION['forgot_receipt_no'];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>设置新密码 - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-card" style="width: 500px;">
            <div class="card-header">
                <h3><i class="bi bi-check-circle me-2"></i>身份验证成功</h3>
                <p class="mb-0 mt-2 opacity-75">请设置您的新密码</p>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-4">
                    回执单号：<strong><?php echo $receiptNo; ?></strong>
                </div>
                
                <form method="POST" action="forgot_password_save.php">
                    <input type="hidden" name="receipt_no" value="<?php echo $receiptNo; ?>">
                    <div class="mb-3">
                        <label class="form-label">新密码</label>
                        <input type="password" class="form-control" name="new_password" required minlength="6">
                        <small class="text-muted">密码长度至少6位</small>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">确认新密码</label>
                        <input type="password" class="form-control" name="confirm_password" required minlength="6">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mb-3">重置密码</button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
