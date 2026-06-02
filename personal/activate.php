<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

// 检查是否有临时用户信息
if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['temp_user_type']) || 
    !in_array($_SESSION['temp_user_type'], ['personal', 'enterprise'])) {
    redirect('login.php');
}

$username = $_SESSION['temp_username'] ?? '';
$realName = $_SESSION['temp_real_name'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>账户激活 - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .activation-input { 
            font-family: 'Courier New', monospace; 
            font-size: 1.2rem; 
            letter-spacing: 2px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-card" style="width: 520px;">
            <div class="card-header">
                <h3><i class="bi bi-key me-2"></i>账户激活</h3>
                <p class="mb-0 mt-2 opacity-75">欢迎，<?php echo htmlspecialchars($realName); ?></p>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <div class="alert alert-info mb-4">
                    <i class="bi bi-info-circle me-2"></i>
                    您的账户已通过审核，请输入管理员提供的激活码来完成激活。
                </div>
                
                <form action="activate_do.php" method="POST" id="activateForm">
                    <?php echo csrfField(); ?>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">用户名</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($username); ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="activation_code" class="form-label">激活码</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-key"></i></span>
                            <input type="text" class="form-control activation-input" id="activation_code" name="activation_code" 
                                   placeholder="XXXXXX-XXXXXX-XXXXXX-XXXXXX" required autofocus maxlength="29">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="bi bi-check-circle me-2"></i>激活账户
                    </button>
                    
                    <div class="text-center">
                        <a href="login.php" class="text-decoration-none">
                            <i class="bi bi-arrow-left me-1"></i>返回登录
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 自动格式化激活码输入
        document.getElementById('activation_code').addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
            let formatted = '';
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 6 === 0) {
                    formatted += '-';
                }
                formatted += value[i];
            }
            e.target.value = formatted.substring(0, 29);
        });
    </script>
</body>
</html>
