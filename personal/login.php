<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>个人登录 - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-card">
            <div class="card-header">
                <h3><i class="bi bi-person-circle me-2"></i>个人用户登录</h3>
                <p class="mb-0 mt-2 opacity-75">欢迎回到<?php echo SITE_NAME; ?></p>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                    <form action="login_do.php" method="POST" id="loginForm">
                        <?php echo csrfField(); ?>
                    <div class="mb-3">
                        <label for="username" class="form-label">用户名</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="username" name="username" 
                                   placeholder="请输入用户名" required autofocus>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">密码</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="请输入密码" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="captcha" class="form-label">验证码</label>
                        <div class="captcha-box">
                            <input type="text" class="form-control" id="captcha" name="captcha" 
                                   placeholder="请输入验证码" required maxlength="4">
                            <img src="../includes/captcha.php" alt="验证码" id="captchaImg" 
                                 onclick="this.src='../includes/captcha.php?t='+Date.now()" style="cursor:pointer;">
                            <button type="button" class="btn btn-outline-secondary" onclick="refreshCaptcha()">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">记住登录状态</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="bi bi-box-arrow-in-right me-2"></i>登录
                    </button>
                    
                    <div class="d-flex justify-content-between">
                        <a href="forgot_password.php" class="text-decoration-none">忘记密码？</a>
                        <a href="register.php" class="text-decoration-none">立即注册</a>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <p class="text-muted mb-2">其他登录方式</p>
                    <a href="../index.php" class="btn btn-outline-light btn-sm" style="font-weight: 700;">
                        <i class="bi bi-house me-1"></i>返回首页
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script>
        function refreshCaptcha() {
            document.getElementById('captchaImg').src = '../includes/captcha.php?t=' + Date.now();
        }
    </script>
</body>
</html>
