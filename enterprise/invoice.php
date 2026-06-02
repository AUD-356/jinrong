<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['enterprise_id'])) {
    redirect('login.php');
}

$enterpriseId = $_SESSION['enterprise_id'];
$activeMenu = 'invoice';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>企业发票申请 - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="sidebar">
        <?php $userName = $_SESSION['company_name']; include __DIR__ . '/../templates/sidebar_enterprise.php'; ?>
    </div>
    
    <div class="main-content">
        <div class="page-header">
            <h2><i class="bi bi-receipt me-2"></i>企业发票申请</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">首页</a></li>
                    <li class="breadcrumb-item active">发票申请</li>
                </ol>
            </nav>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card-box">
                    <div class="card-title">
                        <i class="bi bi-file-earmark-text me-2"></i>填写发票信息
                    </div>
                    
                    <form action="invoice_do.php" method="POST" id="invoiceForm">
                        <div class="form-section">
                            <h5><i class="bi bi-building2 me-2"></i>发票类型</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="type" 
                                               id="typeEnterprise" value="enterprise" checked required>
                                        <label class="form-check-label" for="typeEnterprise">
                                            <i class="bi bi-building2 me-1"></i>企业发票
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5><i class="bi bi-card-text me-2"></i>发票信息</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">发票抬头 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($_SESSION['company_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">税号 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="tax_id" placeholder="请输入企业税号" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">发票金额（元）<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="amount" required data-money>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">发票内容 <span class="text-danger">*</span></label>
                                    <select class="form-select" name="content" required>
                                        <option value="">请选择</option>
                                        <option value="咨询费">咨询费</option>
                                        <option value="服务费">服务费</option>
                                        <option value="技术开发费">技术开发费</option>
                                        <option value="软件服务费">软件服务费</option>
                                        <option value="信息服务费">信息服务费</option>
                                        <option value="其他">其他</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5><i class="bi bi-envelope me-2"></i>联系方式</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">接收邮箱 <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" name="email" placeholder="发票将以PDF形式发送至此邮箱" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">联系电话</label>
                                    <input type="text" class="form-control" name="phone" maxlength="11">
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">详细地址</label>
                                    <input type="text" class="form-control" name="address" placeholder="选填">
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            发票申请提交后，我们将在1-3个工作日内完成审核，审核通过后发票将以PDF形式发送至您提供的邮箱                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-send me-2"></i>提交申请
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card-box">
                    <div class="card-title">
                        <i class="bi bi-question-circle me-2"></i>申请须知
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            发票金额需与实际消费金额一致
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            企业发票需提供正确的税号
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            发票抬头需与企业名称一致
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            发票将在1-3个工作日内开具
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            电子发票具有同等法律效力
                        </li>
                    </ul>
                </div>
                
                <div class="card-box mt-4">
                    <div class="card-title">
                        <i class="bi bi-clock-history me-2"></i>申请记录
                    </div>
                    <?php
                    $invoices = dbGetAll("SELECT * FROM invoices WHERE user_id = ? AND user_type = 'enterprise' ORDER BY created_at DESC LIMIT 5", [$enterpriseId]);
                    if (empty($invoices)):
                    ?>
                    <p class="text-muted text-center">暂无申请记录</p>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($invoices as $inv): ?>
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong><?php echo htmlspecialchars($inv['title']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo formatDateShort($inv['created_at']); ?></small>
                                </div>
                                <span class="badge <?php echo getInvoiceStatusClass($inv['status']); ?>">
                                    <?php echo getInvoiceStatusText($inv['status']); ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
</body>
</html>
