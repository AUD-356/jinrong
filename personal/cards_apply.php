<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$userId = $_SESSION['user_id'];
$user = dbGetRow("SELECT * FROM users WHERE id = ?", [$userId]);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>银行卡开户申请 - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .upload-box {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .upload-box:hover {
            border-color: #667eea;
            background: #f8f9fa;
        }
        
        .upload-box.has-file {
            border-color: #28a745;
            background: #f0fff4;
        }
        
        .upload-box input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        
        .upload-preview {
            max-height: 200px;
            object-fit: contain;
            border-radius: 4px;
        }
        
        .step-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
        }
        
        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .step-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            transition: all 0.3s;
        }
        
        .step-circle.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }
        
        .step-circle.pending {
            background: #e9ecef;
            color: #6c757d;
        }
        
        .step-line {
            width: 60px;
            height: 3px;
            background: #e9ecef;
        }
        
        .step-line.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .step-text {
            margin-top: 10px;
            font-size: 14px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <?php $userName = $_SESSION['real_name']; $activeMenu = 'cards'; include __DIR__ . '/../templates/sidebar_personal.php'; ?>
    </div>
    
    <div class="main-content">
        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <div class="page-header">
            <h2><i class="bi bi-plus-circle me-2"></i>银行卡开户申请</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">首页</a></li>
                    <li class="breadcrumb-item"><a href="cards.php">银行卡</a></li>
                    <li class="breadcrumb-item active">开户申请</li>
                </ol>
            </nav>
        </div>
        
        <!-- 步骤指示器 -->
        <div class="card-box mb-4">
            <div class="step-indicator">
                <div class="step-item">
                    <div class="step-circle active"><i class="bi bi-check-circle"></i></div>
                    <span class="step-text text-primary">1. 点击开户</span>
                </div>
                <div class="step-line active"></div>
                <div class="step-item">
                    <div class="step-circle active"><i class="bi bi-pencil-square"></i></div>
                    <span class="step-text text-primary">2. 填写信息</span>
                </div>
                <div class="step-line"></div>
                <div class="step-item">
                    <div class="step-circle pending"><i class="bi bi-file-check"></i></div>
                    <span class="step-text text-muted">3. 提交确认</span>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card-box">
                    <form action="cards_apply_do.php" method="POST" id="applyForm">
                        <div class="form-section">
                            <h5><i class="bi bi-card-text me-2"></i>卡片类型</h5>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="card_type" 
                                               id="card_debit" value="debit" checked required>
                                        <label class="form-check-label" for="card_debit">
                                            <i class="bi bi-credit-card-2-front me-1"></i>借记卡
                                        </label>
                                        <small class="d-block text-muted ms-4">适合日常消费、储蓄</small>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="card_type" 
                                               id="card_credit" value="credit">
                                        <label class="form-check-label" for="card_credit">
                                            <i class="bi bi-credit-card me-1"></i>信用卡
                                        </label>
                                        <small class="d-block text-muted ms-4">先消费后还款，享受免息期</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5><i class="bi bi-person me-2"></i>持卡人信息</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">持卡人姓名 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="card_holder" 
                                           value="<?php echo htmlspecialchars($user['real_name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">手机号码 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="phone" 
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                           placeholder="请输入在银行预留手机号" required maxlength="11">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">身份证号码 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="id_card" 
                                           value="<?php echo htmlspecialchars($user['id_card'] ?? ''); ?>" 
                                           placeholder="请输入身份证号码" required maxlength="18">
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            开户申请提交后，银行将在1-3个工作日内完成审核，请耐心等待。您可以使用回执单号查询审核进度。
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle me-2"></i>提交开户申请
                            </button>
                            <a href="cards.php" class="btn btn-outline-secondary">取消</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card-box">
                    <div class="card-title">
                        <i class="bi bi-lightbulb me-2"></i>开户须知
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            年满18周岁的中国公民
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            需提供有效身份证件
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            一个用户最多可开通5张银行卡
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            借记卡免年费，信用卡年费根据卡种收取
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            开户审核通常在1-3个工作日内完成
                        </li>
                    </ul>
                </div>
                
                <div class="card-box mt-4">
                    <div class="card-title">
                        <i class="bi bi-shield-check me-2"></i>安全保障
                    </div>
                    <p class="text-muted">
                        您的开户申请将经过严格审核，确保账户安全。我们采用银行级加密技术保护您的信息安全。
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script>
        document.getElementById('applyForm').addEventListener('submit', function(e) {
            var cardHolder = document.querySelector('input[name="card_holder"]').value;
            var phone = document.querySelector('input[name="phone"]').value;
            var idCard = document.querySelector('input[name="id_card"]').value;
            
            if (cardHolder.trim() === '') {
                e.preventDefault();
                alert('请输入持卡人姓名');
                return;
            }
            
            if (!/^1[3-9]\d{9}$/.test(phone)) {
                e.preventDefault();
                alert('请输入正确的手机号码');
                return;
            }
            
            if (!/^\d{17}[\dXx]$/.test(idCard)) {
                e.preventDefault();
                alert('请输入正确的身份证号码');
                return;
            }
        });
    </script>
</body>
</html>
