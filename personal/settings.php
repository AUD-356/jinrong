<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$userId = $_SESSION['user_id'];
$activeMenu = 'settings';

$user = dbGetRow("SELECT * FROM users WHERE id = ?", [$userId]);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>账户设置 - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="sidebar">
        <?php $userName = $_SESSION['real_name']; include __DIR__ . '/../templates/sidebar_personal.php'; ?>
    </div>
    
    <div class="main-content">
        <div class="page-header">
            <h2><i class="bi bi-gear me-2"></i>账户设置</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">首页</a></li>
                    <li class="breadcrumb-item active">设置</li>
                </ol>
            </nav>
        </div>
        
        <div class="row">
            <div class="col-md-3">
                <div class="list-group" id="settingsTabs">
                    <a href="#profile" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                        <i class="bi bi-person me-2"></i>基本信息
                    </a>
                    <a href="#password" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="bi bi-lock me-2"></i>登录密码
                    </a>
                    <a href="#paypassword" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="bi bi-shield-lock me-2"></i>支付密码
                    </a>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="profile">
                        <div class="card-box">
                            <div class="card-title">
                                <i class="bi bi-person me-2"></i>基本信息
                            </div>
                            
                            <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                            <?php endif; ?>
                            
                            <form action="settings_profile_do.php" method="POST">
                                <div class="row mb-3">
                                    <label class="col-md-3 col-form-label">用户名</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-md-3 col-form-label">真实姓名</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" name="real_name" value="<?php echo htmlspecialchars($user['real_name']); ?>" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-md-3 col-form-label">手机号码</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" value="<?php echo maskPhone($user['phone']); ?>" readonly>
                                        <small class="text-muted">如需修改请联系客服</small>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-md-3 col-form-label">电子邮箱</label>
                                    <div class="col-md-9">
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-md-3 col-form-label">联系地址</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($user['address']); ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-9 offset-md-3">
                                        <button type="submit" class="btn btn-primary">保存修改</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="password">
                        <div class="card-box">
                            <div class="card-title">
                                <i class="bi bi-lock me-2"></i>修改登录密码
                            </div>
                            
                            <form action="settings_password_do.php" method="POST" id="passwordForm">
                                <div class="mb-3">
                                    <label class="form-label">当前密码</label>
                                    <input type="password" class="form-control" name="old_password" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">新密码</label>
                                    <input type="password" class="form-control" name="new_password" id="newPassword" required minlength="6">
                                    <small class="text-muted">密码长度至少6位</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">确认新密码</label>
                                    <input type="password" class="form-control" name="confirm_password" required minlength="6">
                                </div>
                                <button type="submit" class="btn btn-primary">修改密码</button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="paypassword">
                        <div class="card-box">
                            <div class="card-title">
                                <i class="bi bi-shield-lock me-2"></i><?php echo empty($user['pay_password']) ? '设置' : '修改'; ?>支付密码
                            </div>
                            
                            <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                            <?php endif; ?>
                            
                            <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                            <?php endif; ?>
                            
                            <?php if (empty($user['pay_password'])): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                您还未设置支付密码，为了账户安全，请设置6位数字支付密码。
                            </div>
                            <?php endif; ?>
                            
                            <form action="settings_paypassword_do.php" method="POST">
                                <?php if (!empty($user['pay_password'])): ?>
                                <div class="mb-3">
                                    <label class="form-label">当前支付密码</label>
                                    <input type="password" class="form-control" name="old_pay_password" maxlength="6" pattern="[0-9]{6}">
                                </div>
                                <?php endif; ?>
                                <div class="mb-3">
                                    <label class="form-label"><?php echo empty($user['pay_password']) ? '设置' : '新'; ?>支付密码</label>
                                    <input type="password" class="form-control" name="new_pay_password" required maxlength="6" pattern="[0-9]{6}" placeholder="6位数字">
                                    <small class="text-muted">支付密码为6位数字</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">确认支付密码</label>
                                    <input type="password" class="form-control" name="confirm_pay_password" required maxlength="6" pattern="[0-9]{6}" placeholder="再次输入6位数字">
                                </div>
                                <button type="submit" class="btn btn-primary"><?php echo empty($user['pay_password']) ? '设置' : '修改'; ?>支付密码</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script>
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            var newPwd = document.getElementById('newPassword').value;
            if (newPwd.length < 6) {
                e.preventDefault();
                alert('密码长度不能少于6位');
            }
        });
    </script>
    
    <?php
    function maskEmail($email) {
        $parts = explode('@', $email);
        if (count($parts) !== 2) return $email;
        $name = substr($parts[0], 0, 2) . '***';
        return $name . '@' . $parts[1];
    }
    ?>
</body>
</html>
