<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    redirect('login.php');
}

define('IN_ADMIN', true);

$pageTitle = '控制台';

$totalPersonalUsers = dbCount('users');
$totalEnterpriseUsers = dbCount('enterprises');
$totalCards = dbCount('cards');
$totalLoans = dbCount('loans');
$totalTransfers = dbCount('transfers');
$totalInvoices = dbCount('invoices');

$pendingPersonal = dbCount('users', "status = 'pending'");
$pendingEnterprise = dbCount('enterprises', "status = 'pending'");
$pendingCards = dbCount('cards', "status = 'pending'");
$pendingLoans = dbCount('loans', "status = 'pending'");
$pendingTransfers = dbCount('transfers', "status = 'pending'");
$pendingInvoices = dbCount('invoices', "status = 'pending'");

$todayRegistrations = dbCount('users', "DATE(created_at) = CURDATE()") + dbCount('enterprises', "DATE(created_at) = CURDATE()");
$todayTransfers = dbCount('transfers', "DATE(created_at) = CURDATE()");
$todayLoans = dbCount('loans', "DATE(created_at) = CURDATE()");

$recentLogs = dbGetAll("SELECT * FROM operation_logs ORDER BY created_at DESC LIMIT 10");
$recentUsers = dbGetAll("SELECT 'personal' as type, id, username, real_name, created_at, status FROM users ORDER BY created_at DESC LIMIT 5");
$recentEnterprises = dbGetAll("SELECT 'enterprise' as type, id, username, company_name as real_name, created_at, status FROM enterprises ORDER BY created_at DESC LIMIT 5");
$recentUsers = array_merge($recentUsers, $recentEnterprises);
usort($recentUsers, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
$recentUsers = array_slice($recentUsers, 0, 5);

ob_start();
?>
<style>
.admin-footer {
    margin-left: 240px;
    padding: 20px 0;
    border-top: 1px solid #eee;
    background: #fff;
}
.stat-card {
    border-radius: 12px;
    padding: 24px;
    color: #fff;
    position: relative;
    overflow: hidden;
}
.stat-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
}
.stat-card .stat-icon {
    font-size: 32px;
    opacity: 0.8;
}
.stat-card .stat-value {
    font-size: 28px;
    font-weight: 700;
}
.stat-card .stat-label {
    font-size: 14px;
    opacity: 0.9;
}
.stat-card.bg-gradient-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.stat-card.bg-gradient-success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
.stat-card.bg-gradient-danger { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.stat-card.bg-gradient-warning { background: linear-gradient(135deg, #f5af19 0%, #f12711 100%); }
.stat-card.bg-gradient-info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.stat-card.bg-gradient-dark { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); }
</style>
<?php
$extraHead = ob_get_clean();
require_once 'header.php';
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-speedometer2 me-2"></i>控制台</h2>
            <p class="text-muted mb-0">欢迎回来，<?php echo htmlspecialchars($_SESSION['admin_real_name'] ?? $_SESSION['admin_username']); ?></p>
        </div>
        <div class="text-muted">
            <i class="bi bi-clock me-1"></i><?php echo date('Y年m月d日 H:i'); ?>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card bg-gradient-primary">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">个人用户</div>
                    <div class="stat-value"><?php echo number_format($totalPersonalUsers); ?></div>
                    <div class="stat-label mt-2">待审核 <?php echo $pendingPersonal; ?></div>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card bg-gradient-success">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">企业用户</div>
                    <div class="stat-value"><?php echo number_format($totalEnterpriseUsers); ?></div>
                    <div class="stat-label mt-2">待审核 <?php echo $pendingEnterprise; ?></div>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-building2-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card bg-gradient-info">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">银行卡</div>
                    <div class="stat-value"><?php echo number_format($totalCards); ?></div>
                    <div class="stat-label mt-2">待审核 <?php echo $pendingCards; ?></div>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-credit-card-2-front-fill"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card bg-gradient-warning">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">贷款申请</div>
                    <div class="stat-value"><?php echo number_format($totalLoans); ?></div>
                    <div class="stat-label mt-2">待审核 <?php echo $pendingLoans; ?></div>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card bg-gradient-danger">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">转账记录</div>
                    <div class="stat-value"><?php echo number_format($totalTransfers); ?></div>
                    <div class="stat-label mt-2">待审核 <?php echo $pendingTransfers; ?></div>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-arrow-left-right"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card bg-gradient-dark">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">发票申请</div>
                    <div class="stat-value"><?php echo number_format($totalInvoices); ?></div>
                    <div class="stat-label mt-2">待审核 <?php echo $pendingInvoices; ?></div>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-receipt"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card-box">
            <h5 class="card-title"><i class="bi bi-people me-2"></i>最新注册用户</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>类型</th>
                            <th>用户名</th>
                            <th>姓名/公司</th>
                            <th>注册时间</th>
                            <th>状态</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentUsers)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">暂无数据</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($recentUsers as $user): ?>
                        <tr>
                            <td>
                                <?php if ($user['type'] === 'personal'): ?>
                                <span class="badge bg-primary"><i class="bi bi-person me-1"></i>个人</span>
                                <?php else: ?>
                                <span class="badge bg-success"><i class="bi bi-building2 me-1"></i>企业</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['real_name'] ?? ''); ?></td>
                            <td><?php echo formatDateShort($user['created_at']); ?></td>
                            <td><span class="badge <?php echo getStatusClass($user['status']); ?>"><?php echo getStatusText($user['status']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
