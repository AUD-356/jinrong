<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>申请情况查询 - <?php echo SITE_NAME; ?></title>
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
                <h3><i class="bi bi-search me-2"></i>申请情况查询</h3>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="inquiry_do.php">
                    <div class="mb-4">
                        <label for="receipt_no" class="form-label">回执单号</label>
                        <input type="text" class="form-control form-control-lg" id="receipt_no" name="receipt_no" 
                               placeholder="请输入回执单号" required>
                        <small class="text-muted">回执单号格式：RCxxxxxxxxxxxxxxx</small>
                    </div>
                    
                    <div class="mb-4">
                        <label for="captcha" class="form-label">验证码</label>
                        <div class="captcha-box">
                            <input type="text" class="form-control" id="captcha" name="captcha" 
                                   placeholder="验证码" required maxlength="4">
                            <img src="../includes/captcha.php" alt="验证码" id="captchaImg" 
                                 onclick="this.src='../includes/captcha.php?t='+Date.now()" style="cursor:pointer; height:38px;">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="bi bi-search me-2"></i>查询
                    </button>
                </form>
                
                <hr>
                
                <div class="text-center">
                    <p class="text-muted mb-2">我还没注册</p>
                    <a href="register.php" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-person-plus me-1"></i>立即注册
                    </a>
                    <a href="login.php" class="btn btn-outline-secondary btn-sm ms-2">
                        <i class="bi bi-box-arrow-in-right me-1"></i>用户登录
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
