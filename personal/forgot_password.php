<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (isset($_SESSION['user_id'])) {
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>忘记密码 - <?php echo SITE_NAME; ?></title>
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
                <h3><i class="bi bi-key me-2"></i>忘记密码</h3>
                <p class="mb-0 mt-2 opacity-75">通过身份验证重置密码</p>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="forgot_password_do.php">
                    <div class="mb-3">
                        <label class="form-label">用户名</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">手机号码</label>
                        <input type="text" class="form-control" name="phone" required maxlength="11">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">身份证号码</label>
                        <input type="text" class="form-control" name="id_card" required maxlength="18">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">验证码</label>
                        <div class="captcha-box">
                            <input type="text" class="form-control" name="captcha" required maxlength="4">
                            <img src="../includes/captcha.php" alt="验证码" onclick="this.src='../includes/captcha.php?t='+Date.now()" style="cursor:pointer; height:38px;">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mb-3">提交申请</button>
                    <div class="text-center">
                        <a href="login.php">返回登录</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
