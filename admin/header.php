<?php
ob_start();
if (!defined('IN_ADMIN')) {
    die('Access Denied');
}

$adminName = $_SESSION['admin_real_name'] ?? $_SESSION['admin_username'] ?? '管理员';
$adminRole = $_SESSION['admin_role'] ?? 'admin';
$roleText = [
    'super_admin' => '超级管理员',
    'admin' => '管理员',
    'operator' => '操作员'
];
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? '管理后台'; ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <?php if (isset($extraHead)): ?>
    <?php echo $extraHead; ?>
    <?php endif; ?>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="d-flex align-items-center">
                <i class="bi bi-shield-lock text-danger fs-4 me-2"></i>
                <div>
                    <div class="fw-bold text-white"><?php echo SITE_NAME; ?></div>
                    <small class="text-muted">管理后台</small>
                </div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-user">
                <div class="user-avatar">
                    <i class="bi bi-person-circle"></i>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($adminName); ?></div>
                    <div class="user-role"><?php echo $roleText[$adminRole] ?? '管理员'; ?></div>
                </div>
            </div>
            
            <div class="nav-section">
                <a href="index.php" class="nav-item <?php echo $currentPage === 'index' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>控制台</span>
                </a>
            </div>
            
            <div class="nav-section">
                <a href="users_personal.php" class="nav-item <?php echo $currentPage === 'users_personal' ? 'active' : ''; ?>">
                    <i class="bi bi-person"></i>
                    <span>个人用户</span>
                </a>
                <a href="users_enterprise.php" class="nav-item <?php echo $currentPage === 'users_enterprise' ? 'active' : ''; ?>">
                    <i class="bi bi-building-check"></i>
                    <span>企业用户</span>
                </a>
            </div>
            
            <div class="nav-section">
                <a href="audit_personal.php" class="nav-item <?php echo $currentPage === 'audit_personal' ? 'active' : ''; ?>">
                    <i class="bi bi-person-check"></i>
                    <span>个人注册审核</span>
                    <?php
                    $pendingPersonalCount = dbCount('users', "status = 'pending' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
                    if ($pendingPersonalCount > 0): ?>
                    <span class="badge bg-danger rounded-pill"><?php echo $pendingPersonalCount; ?></span>
                    <?php endif; ?>
                </a>
                <a href="audit_enterprise.php" class="nav-item <?php echo $currentPage === 'audit_enterprise' ? 'active' : ''; ?>">
                    <i class="bi bi-building-check"></i>
                    <span>企业注册审核</span>
                    <?php
                    $pendingEnterpriseCount = dbCount('enterprises', "status = 'pending' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
                    if ($pendingEnterpriseCount > 0): ?>
                    <span class="badge bg-danger rounded-pill"><?php echo $pendingEnterpriseCount; ?></span>
                    <?php endif; ?>
                </a>
            </div>
            
            <div class="nav-section">
                <a href="cards_personal.php" class="nav-item <?php echo $currentPage === 'cards_personal' ? 'active' : ''; ?>">
                    <i class="bi bi-credit-card-2-front"></i>
                    <span>个人银行卡</span>
                </a>
                <a href="cards_enterprise.php" class="nav-item <?php echo $currentPage === 'cards_enterprise' ? 'active' : ''; ?>">
                    <i class="bi bi-credit-card"></i>
                    <span>企业银行卡</span>
                </a>
                <a href="cards_audit_personal.php" class="nav-item <?php echo $currentPage === 'cards_audit_personal' ? 'active' : ''; ?>">
                    <i class="bi bi-file-earmark-plus"></i>
                    <span>个人开卡审核</span>
                    <?php
                    $pendingCardPersonal = dbCount('cards', "status = 'pending' AND user_type = 'personal' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
                    if ($pendingCardPersonal > 0): ?>
                    <span class="badge bg-danger rounded-pill"><?php echo $pendingCardPersonal; ?></span>
                    <?php endif; ?>
                </a>
                <a href="cards_audit_enterprise.php" class="nav-item <?php echo $currentPage === 'cards_audit_enterprise' ? 'active' : ''; ?>">
                    <i class="bi bi-file-earmark-plus"></i>
                    <span>企业开卡审核</span>
                    <?php
                    $pendingCardEnterprise = dbCount('cards', "status = 'pending' AND user_type = 'enterprise' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
                    if ($pendingCardEnterprise > 0): ?>
                    <span class="badge bg-danger rounded-pill"><?php echo $pendingCardEnterprise; ?></span>
                    <?php endif; ?>
                </a>
            </div>
            
            <div class="nav-section">
                <a href="loans_personal.php" class="nav-item <?php echo $currentPage === 'loans_personal' ? 'active' : ''; ?>">
                    <i class="bi bi-cash-stack"></i>
                    <span>个人贷款</span>
                </a>
                <a href="loans_enterprise.php" class="nav-item <?php echo $currentPage === 'loans_enterprise' ? 'active' : ''; ?>">
                    <i class="bi bi-bank"></i>
                    <span>企业贷款</span>
                </a>
                <a href="loans_audit_personal.php" class="nav-item <?php echo $currentPage === 'loans_audit_personal' ? 'active' : ''; ?>">
                    <i class="bi bi-clipboard-check"></i>
                    <span>个人贷款审核</span>
                    <?php
                    $pendingLoanPersonal = dbCount('loans', "status = 'pending' AND user_type = 'personal' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
                    if ($pendingLoanPersonal > 0): ?>
                    <span class="badge bg-danger rounded-pill"><?php echo $pendingLoanPersonal; ?></span>
                    <?php endif; ?>
                </a>
                <a href="loans_audit_enterprise.php" class="nav-item <?php echo $currentPage === 'loans_audit_enterprise' ? 'active' : ''; ?>">
                    <i class="bi bi-clipboard-check"></i>
                    <span>企业贷款审核</span>
                    <?php
                    $pendingLoanEnterprise = dbCount('loans', "status = 'pending' AND user_type = 'enterprise' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
                    if ($pendingLoanEnterprise > 0): ?>
                    <span class="badge bg-danger rounded-pill"><?php echo $pendingLoanEnterprise; ?></span>
                    <?php endif; ?>
                </a>
            </div>
            
            <div class="nav-section">
                <a href="credit_personal.php" class="nav-item <?php echo $currentPage === 'credit_personal' ? 'active' : ''; ?>">
                    <i class="bi bi-clipboard-check"></i>
                    <span>个人征信查询</span>
                </a>
                <a href="credit_enterprise.php" class="nav-item <?php echo $currentPage === 'credit_enterprise' ? 'active' : ''; ?>">
                    <i class="bi bi-building-check"></i>
                    <span>企业征信查询</span>
                </a>
            </div>
            
            <div class="nav-section">
                <a href="stock_management.php" class="nav-item <?php echo $currentPage === 'stock_management' ? 'active' : ''; ?>">
                    <i class="bi bi-bar-chart-line"></i>
                    <span>股票管理</span>
                </a>
            </div>
            
            <div class="nav-section">
                <a href="invoices.php" class="nav-item <?php echo $currentPage === 'invoices' ? 'active' : ''; ?>">
                    <i class="bi bi-receipt"></i>
                    <span>发票申请</span>
                    <?php
                    $pendingInvoice = dbCount('invoices', "status = 'pending' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
                    if ($pendingInvoice > 0): ?>
                    <span class="badge bg-danger rounded-pill"><?php echo $pendingInvoice; ?></span>
                    <?php endif; ?>
                </a>
                <a href="bills_personal.php" class="nav-item <?php echo $currentPage === 'bills_personal' ? 'active' : ''; ?>">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>个人账单</span>
                </a>
                <a href="bills_enterprise.php" class="nav-item <?php echo $currentPage === 'bills_enterprise' ? 'active' : ''; ?>">
                    <i class="bi bi-file-earmark-text-fill"></i>
                    <span>企业账单</span>
                </a>
                <a href="transfers.php" class="nav-item <?php echo $currentPage === 'transfers' ? 'active' : ''; ?>">
                    <i class="bi bi-arrow-left-right"></i>
                    <span>转账记录</span>
                    <?php
                    $pendingTransfer = dbCount('transfers', "status = 'pending' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
                    if ($pendingTransfer > 0): ?>
                    <span class="badge bg-danger rounded-pill"><?php echo $pendingTransfer; ?></span>
                    <?php endif; ?>
                </a>
            </div>
            
            <div class="nav-section">
                <a href="system_config.php" class="nav-item <?php echo $currentPage === 'system_config' ? 'active' : ''; ?>">
                    <i class="bi bi-gear"></i>
                    <span>系统配置</span>
                </a>
                <a href="system_logs.php" class="nav-item <?php echo $currentPage === 'system_logs' ? 'active' : ''; ?>">
                    <i class="bi bi-clock-history"></i>
                    <span>操作日志</span>
                </a>
            </div>
            
            <div class="nav-section mt-3">
                <a href="logout.php" class="nav-item text-danger">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>退出登录</span>
                </a>
            </div>
        </nav>
    </div>
    
    <div class="main-content">
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <script>
            (function() {
                var sidebar = document.querySelector('.sidebar');
                if (!sidebar) return;
                
                // 立即恢复滚动位置，避免延迟导致的跳动
                var scrollPos = parseInt(sessionStorage.getItem('sidebarScroll')) || 0;
                if (scrollPos > 0) {
                    sidebar.scrollTop = scrollPos;
                }
                
                // 使用防抖保存滚动位置
                var scrollTimer;
                sidebar.addEventListener('scroll', function() {
                    clearTimeout(scrollTimer);
                    scrollTimer = setTimeout(function() {
                        sessionStorage.setItem('sidebarScroll', sidebar.scrollTop.toString());
                    }, 100);
                });
            })();
        </script>
