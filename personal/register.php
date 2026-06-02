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
    <title>个人用户注册 - <?php echo SITE_NAME; ?></title>
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
                <h3><i class="bi bi-person-plus me-2"></i>个人用户注册</h3>
                <p class="mb-0 mt-2 opacity-75">加入<?php echo SITE_NAME; ?></p>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <form action="register_do.php" method="POST" id="registerForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">用户名 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   required minlength="4" maxlength="20" pattern="[a-zA-Z0-9_]+"
                                   placeholder="4-20位字母、数字或下划线">
                            <div class="invalid-feedback">请输入4-20位字母、数字或下划线</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="real_name" class="form-label">真实姓名 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="real_name" name="real_name" 
                                   required placeholder="请输入真实姓名">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">密码 <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   required minlength="6" placeholder="至少6位字符">
                            <div class="invalid-feedback">密码长度不能少于6位</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">确认密码 <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   required minlength="6" placeholder="再次输入密码">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">手机号码 <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   placeholder="请输入11位手机号" required maxlength="11" 
                                   pattern="^1[3-9]\d{9}$">
                            <div class="invalid-feedback">请输入正确的11位手机号</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">电子邮箱</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   placeholder="请输入邮箱地址">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="id_card" class="form-label">身份证号 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="id_card" name="id_card" 
                                   placeholder="请输入18位身份证号（最后一位可以是X或x）" 
                                   required maxlength="18" 
                                   pattern="^\d{17}[\dXx]$">
                            <div class="invalid-feedback">请输入正确的18位身份证号</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">联系地址</label>
                        <input type="text" class="form-control" id="address" name="address">
                    </div>
                    
                    <div class="mb-3">
                        <label for="captcha" class="form-label">验证码 <span class="text-danger">*</span></label>
                        <div class="captcha-box">
                            <input type="text" class="form-control" id="captcha" name="captcha" 
                                   placeholder="验证码" required maxlength="4">
                            <img src="../includes/captcha.php" alt="验证码" id="captchaImg" 
                                 onclick="this.src='../includes/captcha.php?t='+Date.now()" style="cursor:pointer; height:38px;">
                        </div>
                    </div>
                    
                    <div class="mb-4 form-check">
                        <input type="checkbox" class="form-check-input" id="agree" name="agree" required>
                        <label class="form-check-label" for="agree">
                            我已阅读并同意<a href="#" data-bs-toggle="modal" data-bs-target="#agreementModal">《用户服务协议》</a>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="bi bi-check-circle me-2"></i>提交注册
                    </button>
                    
                    <div class="text-center">
                        <span class="text-muted">已有账号？</span>
                        <a href="login.php" class="text-decoration-none">立即登录</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="agreementModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">用户服务协议</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="max-height: 400px; overflow-y: auto;">
                    <p>欢迎使用<?php echo SITE_NAME; ?>服务！...</p>
                    <p>（此处省略协议内容）</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script>
        // 手机号验证：11位纯数字，以1开头
        function validatePhone(phone) {
            return /^1[3-9]\d{9}$/.test(phone);
        }
        
        // 身份证号验证：18位数字或最后一位X/x
        function validateIdCard(idCard) {
            return /^\d{17}[\dXx]$/.test(idCard);
        }
        
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            var username = document.getElementById('username').value.trim();
            var realName = document.getElementById('real_name').value.trim();
            var password = document.getElementById('password').value;
            var confirmPassword = document.getElementById('confirm_password').value;
            var phone = document.getElementById('phone').value.trim();
            var idCard = document.getElementById('id_card').value.trim();
            var captcha = document.getElementById('captcha').value.trim();
            var agree = document.getElementById('agree').checked;
            
            // 验证用户名
            if (!username) {
                e.preventDefault();
                alert('请输入用户名');
                return false;
            }
            if (username.length < 4 || username.length > 20) {
                e.preventDefault();
                alert('用户名长度需要在4-20位之间');
                return false;
            }
            
            // 验证真实姓名
            if (!realName) {
                e.preventDefault();
                alert('请输入真实姓名');
                return false;
            }
            
            // 验证密码
            if (!password) {
                e.preventDefault();
                alert('请输入密码');
                return false;
            }
            if (password.length < 6) {
                e.preventDefault();
                alert('密码长度不能少于6位');
                return false;
            }
            
            // 验证确认密码
            if (!confirmPassword) {
                e.preventDefault();
                alert('请输入确认密码');
                return false;
            }
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('两次输入的密码不一致');
                return false;
            }
            
            // 验证手机号
            if (!phone) {
                e.preventDefault();
                alert('请输入手机号');
                return false;
            }
            if (!validatePhone(phone)) {
                e.preventDefault();
                alert('请输入正确的11位手机号');
                return false;
            }
            
            // 验证身份证号
            if (!idCard) {
                e.preventDefault();
                alert('请输入身份证号');
                return false;
            }
            if (!validateIdCard(idCard)) {
                e.preventDefault();
                alert('请输入正确的18位身份证号（最后一位可以是X或x）');
                return false;
            }
            
            // 验证验证码
            if (!captcha) {
                e.preventDefault();
                alert('请输入验证码');
                return false;
            }
            if (captcha.length !== 4) {
                e.preventDefault();
                alert('验证码必须是4位');
                return false;
            }
            
            // 验证协议
            if (!agree) {
                e.preventDefault();
                alert('请先阅读并同意用户服务协议');
                return false;
            }
        });
        
        // 实时验证手机号格式
        document.getElementById('phone').addEventListener('input', function() {
            var phone = this.value.trim();
            var feedback = this.nextElementSibling;
            if (phone && !validatePhone(phone)) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else if (phone) {
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
            } else {
                this.classList.remove('is-valid', 'is-invalid');
            }
        });
        
        // 实时验证身份证号格式
        document.getElementById('id_card').addEventListener('input', function() {
            var idCard = this.value.trim();
            var feedback = this.nextElementSibling;
            if (idCard && !validateIdCard(idCard)) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else if (idCard) {
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
            } else {
                this.classList.remove('is-valid', 'is-invalid');
            }
        });
    </script>
</body>
</html>
