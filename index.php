<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>金融练习靶场</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* 启动加载动画样式 */
        .loader-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.25);
            margin-bottom: 30px;
            animation: scaleIn 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        .loader-logo i {
            font-size: 60px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
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
        body { 
            display: flex; 
            align-items: center;
            justify-content: center;
            min-height: 100vh; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            font-family: 'Inter', 'PingFang SC', 'Microsoft YaHei', sans-serif;
        }
        .page-content {
            width: 100%;
            max-width: 800px;
            padding: 20px;
            opacity: 0;
            transition: opacity 0.6s ease;
        }
        .page-content.visible {
            opacity: 1;
        }
        .logo-container {
            width: 100px;
            height: 100px;
            margin: 0 auto 30px;
            background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%);
            border-radius: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        .logo-icon {
            font-size: 50px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .card-hover {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.15);
        }
        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border: none;
            padding: 12px 32px;
            border-radius: 12px;
            font-weight: 600;
        }
        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            padding: 12px 32px;
            border-radius: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- 启动加载动画 -->
    <div class="loader-wrapper" id="loader">
        <div class="loader-logo">
            <i class="bi bi-shield-check"></i>
        </div>
        <div class="loader-title">金融练习靶场</div>
        <div class="loader-progress">
            <div class="loader-progress-bar"></div>
        </div>
        <div class="loader-text">正在加载中...</div>
    </div>
    <div class="page-content">
        <div class="text-center mb-8">
            <div class="logo-container">
                <i class="logo-icon bi bi-shield-check"></i>
            </div>
            <h1 class="display-4 fw-bold text-white mb-2">金融练习靶场</h1>
        </div>
        
        <div class="row g-6 justify-content-center">
            <div class="col-md-5">
                <div class="card card-hover h-100 bg-white rounded-2xl">
                    <div class="card-body text-center py-8">
                        <h3 class="card-title text-xl font-bold text-gray-800 mb-6">个人用户</h3>
                        <a href="/personal/login.php" class="btn btn-primary px-8 py-3 rounded-xl font-medium">
                            <i class="bi bi-box-arrow-in-right me-2"></i>立即登录
                        </a>
                        <div class="mt-4">
                            <small class="text-gray-400">还没有账号？<a href="/personal/register.php" class="text-primary hover-underline">立即注册</a></small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-5">
                <div class="card card-hover h-100 bg-white rounded-2xl">
                    <div class="card-body text-center py-8">
                        <h3 class="card-title text-xl font-bold text-gray-800 mb-6">企业用户</h3>
                        <a href="/enterprise/login.php" class="btn btn-success px-8 py-3 rounded-xl font-medium">
                            <i class="bi bi-box-arrow-in-right me-2"></i>立即登录
                        </a>
                        <div class="mt-4">
                            <small class="text-gray-400">还没有账号？<a href="/enterprise/register.php" class="text-success hover-underline">立即注册</a></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
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
                const pageContent = document.querySelector('.page-content');
                pageContent.classList.add('visible');
            } else {
                // 外部进入，显示启动动画
                const loadDuration = 3000;
                setTimeout(function() {
                    const loader = document.getElementById('loader');
                    loader.classList.add('hidden');
                    const pageContent = document.querySelector('.page-content');
                    pageContent.classList.add('visible');
                }, loadDuration);
            }
        });
    </script>
</body>
</html>