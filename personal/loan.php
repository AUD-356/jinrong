<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$userId = $_SESSION['user_id'];
$activeMenu = 'loan';

$hasActiveLoan = dbGetRow("SELECT * FROM loans WHERE user_id = ? AND user_type = 'personal' AND status IN ('pending', 'approved', 'contract_signed')", [$userId]);

$loans = dbGetAll("SELECT * FROM loans WHERE user_id = ? AND user_type = 'personal' ORDER BY created_at DESC LIMIT 10", [$userId]);

$products = dbGetAll("SELECT * FROM loan_products WHERE user_type IN ('personal', 'all') AND status = 'active' ORDER BY sort_order");
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>个人贷款 - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .product-card {
            transition: all 0.3s ease;
            border: 2px solid transparent;
            cursor: pointer;
        }
        
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            border-color: #667eea;
        }
        
        .product-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            margin: -16px -16px 16px -16px;
        }
        
        .product-feature {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 0;
            color: #666;
            font-size: 14px;
        }
        
        .product-feature i {
            color: #667eea;
            width: 20px;
        }
        
        .quick-action {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: #fff;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .quick-action h4 {
            margin: 0 0 12px 0;
            font-weight: 600;
        }
        
        .quick-action p {
            margin: 0 0 16px 0;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <?php $userName = $_SESSION['real_name']; include __DIR__ . '/../templates/sidebar_personal.php'; ?>
    </div>
    
    <div class="main-content">
        <div class="page-header">
            <h2><i class="bi bi-cash-stack me-2"></i>个人贷款</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">首页</a></li>
                    <li class="breadcrumb-item active">贷款</li>
                </ol>
            </nav>
        </div>
        
        <?php if ($hasActiveLoan): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            您有一笔贷款正在处理中（回执单号：<?php echo $hasActiveLoan['receipt_no']; ?>），请等待该笔贷款完成后再申请新的贷款。
        </div>
        <?php endif; ?>
        
        <!-- 快速申请入口 -->
        <?php if (!$hasActiveLoan): ?>
        <div class="quick-action">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4><i class="bi bi-lightning-charge me-2"></i>快速申请贷款</h4>
                    <p>选择适合您的贷款产品，填写申请信息，快速获取贷款审批结果</p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-light text-dark fs-6"><i class="bi bi-shield-check me-1"></i>安全便捷</span>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 贷款产品展示 -->
        <div class="card-box mb-4">
            <div class="card-title">
                <i class="bi bi-collection me-2"></i>贷款产品推荐
            </div>
            
            <?php if (empty($products)): ?>
            <div class="empty-state">
                <i class="bi bi-cash"></i>
                <h4>暂无贷款产品</h4>
                <p>请稍后再来查看</p>
            </div>
            <?php else: ?>
            <div class="row">
                <?php foreach ($products as $index => $product): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card product-card h-100">
                        <div class="card-body p-4">
                            <div class="product-header">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="mb-0"><?php echo htmlspecialchars($product['name']); ?></h5>
                                        <small class="opacity-75"><?php echo htmlspecialchars($product['description']); ?></small>
                                    </div>
                                    <span class="badge bg-light text-dark">
                                        <i class="bi bi-fire me-1"></i>热门
                                    </span>
                                </div>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-6 text-center">
                                    <div class="fs-3 fw-bold text-primary">
                                        <?php echo formatMoney($product['min_amount']); ?>
                                    </div>
                                    <small class="text-muted">最低额度</small>
                                </div>
                                <div class="col-6 text-center">
                                    <div class="fs-3 fw-bold text-success">
                                        <?php echo $product['min_rate']; ?>%
                                    </div>
                                    <small class="text-muted">最低利率</small>
                                </div>
                            </div>
                            
                            <div class="border-top pt-3 mb-3">
                                <div class="product-feature">
                                    <i class="bi bi-check-circle"></i>
                                    <span>最高额度：<?php echo formatMoney($product['max_amount']); ?></span>
                                </div>
                                <div class="product-feature">
                                    <i class="bi bi-check-circle"></i>
                                    <span>贷款期限：<?php echo $product['min_term']; ?> - <?php echo $product['max_term']; ?>个月</span>
                                </div>
                                <div class="product-feature">
                                    <i class="bi bi-check-circle"></i>
                                    <span>利率范围：<?php echo $product['min_rate']; ?>% - <?php echo $product['max_rate']; ?>%</span>
                                </div>
                            </div>
                            
                            <?php if (!$hasActiveLoan): ?>
                            <a href="loan_apply.php?product_id=<?php echo $product['id']; ?>" class="btn btn-primary w-100">
                                <i class="bi bi-file-earmark-text me-2"></i>立即申请
                            </a>
                            <?php else: ?>
                            <button class="btn btn-secondary w-100" disabled>
                                <i class="bi bi-lock me-2"></i>暂不可申请
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- 贷款记录 -->
        <div class="card-box">
            <div class="card-title">
                <i class="bi bi-list-ul me-2"></i>我的贷款记录
            </div>
            
            <?php if (empty($loans)): ?>
            <div class="empty-state">
                <i class="bi bi-clock-history"></i>
                <h4>暂无贷款记录</h4>
                <p>您还没有申请过贷款</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>回执单号</th>
                            <th>贷款产品</th>
                            <th>金额</th>
                            <th>期限</th>
                            <th>状态</th>
                            <th>时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loans as $loan): ?>
                        <tr>
                            <td><small><?php echo $loan['receipt_no']; ?></small></td>
                            <td><?php echo htmlspecialchars($loan['purpose']); ?></td>
                            <td class="text-danger fw-bold"><?php echo formatMoney($loan['amount']); ?></td>
                            <td><?php echo $loan['term']; ?>个月</td>
                            <td><span class="badge <?php echo getLoanStatusClass($loan['status']); ?>"><?php echo getLoanStatusText($loan['status']); ?></span></td>
                            <td><small><?php echo formatDateShort($loan['created_at']); ?></small></td>
                            <td>
                                <a href="loan_detail.php?id=<?php echo $loan['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> 详情
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
</body>
</html>
