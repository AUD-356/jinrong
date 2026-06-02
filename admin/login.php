<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (isset($_SESSION['admin_id'])) {
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
    <title>管理员登录 - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        /* 启动加载动画样式 */
        .loader-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }
        .loader-wrapper.hidden {
            opacity: 0;
            visibility: hidden;
        }
        .loader-logo {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.4);
            margin-bottom: 30px;
            animation: scaleIn 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        .loader-logo i {
            font-size: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .loader-title {
            color: white;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 40px;
            opacity: 0;
            animation: fadeInUp 0.6s ease forwards 0.3s;
        }
        .loader-progress {
            width: 200px;
            height: 6px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            overflow: hidden;
            opacity: 0;
            animation: fadeInUp 0.6s ease forwards 0.5s;
        }
        .loader-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #ffffff, #e0e7ff);
            border-radius: 10px;
            width: 0;
            animation: progressFill 2s ease forwards 0.6s;
        }
        .loader-text {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
            margin-top: 20px;
            opacity: 0;
            animation: fadeInUp 0.6s ease forwards 0.7s;
        }
        @keyframes scaleIn {
            0% { transform: scale(0); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        @keyframes fadeInUp {
            0% { transform: translateY(20px); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }
        @keyframes progressFill {
            0% { width: 0; }
            100% { width: 100%; }
        }

        /* 原页面样式 */
        body { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); min-height: 100vh; }
        .page-container {
            opacity: 0;
            transition: opacity 0.6s ease;
        }
        .page-container.visible {
            opacity: 1;
        }
        .login-card { background: #fff; border-radius: 16px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); width: 400px; }
        .login-card .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 30px; text-align: center; border: none; border-radius: 16px 16px 0 0; }
        .login-card .card-body { padding: 40px 30px; }
        .form-control:focus { border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25); }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 12px; font-weight: 500; }
        .btn-primary:hover { background: linear-gradient(135deg, #5a6fd6 0%, #6a4190 100%); }
        .captcha-box { display: flex; gap: 10px; }
        .captcha-box img { cursor: pointer; border-radius: 4px; height: 45px; }
    </style>
</head>
<body>
    <!-- 启动加载动画 -->
    <div class="loader-wrapper" id="loader">
        <div class="loader-logo">
            <i class="bi bi-shield-lock"></i>
        </div>
        <div class="loader-title">管理员登录</div>
        <div class="loader-progress">
            <div class="loader-progress-bar"></div>
        </div>
        <div class="loader-text">正在加载中...</div>
    </div>
    
    <div class="page-container" id="pageContainer">
        <div class="min-vh-100 d-flex align-items-center justify-content-center">
        <div class="login-card">
            <div class="card-header">
                <h3><i class="bi bi-shield-lock me-2"></i>管理员登录</h3>
                <p class="mb-0 mt-2 opacity-75">欢迎访问<?php echo SITE_NAME; ?>管理后台</p>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <form action="login_do.php" method="POST" id="loginForm">
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
                    
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="bi bi-box-arrow-in-right me-2"></i>登录
                    </button>
                    
                    <div class="text-center">
                        <a href="../index.php" class="text-decoration-none">
                            <i class="bi bi-house me-1"></i>返回首页
                        </a>
                    </div>
                </form>
            </div>
        </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function refreshCaptcha() {
            document.getElementById('captchaImg').src = '../includes/captcha.php?t=' + Date.now();
        }
        
        window.addEventListener('DOMContentLoaded', function() {
            // 获取当前网站的域名
            const currentHost = window.location.host;
            // 获取来源页面
            const referrer = document.referrer;
            let isExternalEntry = false;
            
            if (!referrer) {
                // 没有来源，是直接输入网址
                isExternalEntry = true;
            } else {
                // 检查来源域名是否与当前域名不同
                try {
                    const referrerHost = new URL(referrer).host;
                    isExternalEntry = referrerHost !== currentHost;
                } catch (e) {
                    isExternalEntry = true;
                }
            }
            
            if (!isExternalEntry) {
                // 内部跳转，直接显示页面内容
                const loader = document.getElementById('loader');
                loader.classList.add('hidden');
                const pageContainer = document.getElementById('pageContainer');
                pageContainer.classList.add('visible');
            } else {
                // 外部进入，显示启动动画
                const loadDuration = 3000;
                setTimeout(function() {
                    const loader = document.getElementById('loader');
                    loader.classList.add('hidden');
                    const pageContainer = document.getElementById('pageContainer');
                    pageContainer.classList.add('visible');
                }, loadDuration);
            }
        });
    </script>
</body>
</html>
