<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['real_name'];

$user = dbGetRow("SELECT * FROM users WHERE id = ?", [$userId]);

$cards = dbGetAll("SELECT * FROM cards WHERE user_id = ? AND user_type = 'personal' AND status = 'active'", [$userId]);

$totalBalance = 0;
foreach ($cards as $card) {
    $totalBalance += $card['balance'];
}

$recentApplications = dbGetAll("SELECT * FROM applications WHERE user_id = ? AND user_type = 'personal' ORDER BY created_at DESC LIMIT 5", [$userId]);

$activeLoans = dbGetAll("SELECT * FROM loans WHERE user_id = ? AND user_type = 'personal' AND status IN ('pending', 'approved', 'contract_signed')", [$userId]);

$stockHoldings = dbGetAll("SELECT SUM(quantity) as total_shares FROM stock_holdings WHERE user_id = ? AND user_type = 'personal'", [$userId]);

$investments = dbGetAll("SELECT SUM(amount) as total_investment FROM user_investments WHERE user_id = ? AND user_type = 'personal' AND status = 'invested'", [$userId]);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>个人首页 - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="sidebar">
        <?php $activeMenu = 'home'; $userName = $_SESSION['real_name']; include __DIR__ . '/../templates/sidebar_personal.php'; ?>
    </div>
    
    <div class="main-content">
        <div class="page-header">
            <h2>欢迎回来，<?php echo htmlspecialchars($userName); ?></h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">首页</a></li>
                    <li class="breadcrumb-item active">控制台</li>
                </ol>
            </nav>
        </div>
        
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-wallet2"></i></div>
                    <div class="stat-value"><?php echo formatMoney($totalBalance); ?></div>
                    <div class="stat-label">账户总余额（元）</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card success">
                    <div class="stat-icon"><i class="bi bi-credit-card"></i></div>
                    <div class="stat-value"><?php echo count($cards); ?></div>
                    <div class="stat-label">银行卡数量</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
                    <div class="stat-value"><?php echo count($activeLoans); ?></div>
                    <div class="stat-label">进行中贷款</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card info">
                    <div class="stat-icon"><i class="bi bi-graph-up"></i></div>
                    <div class="stat-value"><?php echo formatMoney($investments[0]['total_investment'] ?? 0); ?></div>
                    <div class="stat-label">投资总额（元）</div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card-box">
                    <div class="card-title">
                        <i class="bi bi-bank me-2"></i>我的银行卡
                    </div>
                    <?php if (empty($cards)): ?>
                    <div class="empty-state">
                        <i class="bi bi-credit-card"></i>
                        <h4>暂无银行卡</h4>
                        <p>您还没有申请银行卡</p>
                        <a href="cards.php" class="btn btn-primary">立即开户</a>
                    </div>
                    <?php else: ?>
                    <div class="row">
                        <?php foreach ($cards as $card): ?>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="card-title mb-0">
                                            <i class="bi bi-credit-card-2-front text-primary me-2"></i>
                                            <?php echo getBankName($card['bank_code']); ?>
                                        </h5>
                                        <span class="badge <?php echo getCardStatusClass($card['status']); ?>">
                                            <?php echo getCardStatusText($card['status']); ?>
                                        </span>
                                    </div>
                                    <p class="card-text">
                                        <strong><?php echo maskBankCard($card['card_no']); ?></strong>
                                    </p>
                                    <p class="text-muted mb-0">
                                        余额：<span class="text-success fw-bold"><?php echo formatMoney($card['balance']); ?></span> 元
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card-box">
                    <div class="card-title">
                        <i class="bi bi-person-circle me-2"></i>账户信息
                    </div>
                    <table class="table table-sm">
                        <tr>
                            <td>用户名</td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                        </tr>
                        <tr>
                            <td>真实姓名</td>
                            <td><?php echo htmlspecialchars($user['real_name']); ?></td>
                        </tr>
                        <tr>
                            <td>手机号</td>
                            <td><?php echo maskPhone($user['phone']); ?></td>
                        </tr>
                        <tr>
                            <td>邮箱</td>
                            <td><?php echo $user['email'] ? maskEmail($user['email']) : '-'; ?></td>
                        </tr>
                        <tr>
                            <td>账户状态</td>
                            <td><span class="badge <?php echo getStatusClass($user['status']); ?>"><?php echo getStatusText($user['status']); ?></span></td>
                        </tr>
                    </table>
                    <a href="settings.php" class="btn btn-outline-primary btn-sm w-100">
                        <i class="bi bi-gear me-1"></i>账户设置
                    </a>
                </div>
                
                <div class="card-box mt-4">
                    <div class="card-title">
                        <i class="bi bi-lightning-fill me-2"></i>快捷操作
                    </div>
                    <div class="d-grid gap-2">
                        <a href="transfer.php" class="btn btn-primary">
                            <i class="bi bi-arrow-left-right me-2"></i>转账汇款
                        </a>
                        <a href="cards.php" class="btn btn-outline-primary">
                            <i class="bi bi-credit-card me-2"></i>银行卡管理
                        </a>
                        <a href="loan.php" class="btn btn-outline-success">
                            <i class="bi bi-cash-stack me-2"></i>申请贷款
                        </a>
                        <a href="stock.php" class="btn btn-outline-danger">
                            <i class="bi bi-graph-up me-2"></i>股票交易
                        </a>
                    </div>
                </div>
                
                <div class="card-box mt-4">
                    <div class="card-title">
                        <i class="bi bi-bell me-2"></i>通知公告
                    </div>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item px-0">
                            <small class="text-muted">2024-01-15</small>
                            <p class="mb-0">系统升级维护通知</p>
                        </div>
                        <div class="list-group-item px-0">
                            <small class="text-muted">2024-01-10</small>
                            <p class="mb-0">新增理财产品上线</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    
    <?php
    function maskEmail($email) {
        $parts = explode('@', $email);
        if (count($parts) !== 2) return $email;
        $name = substr($parts[0], 0, 2) . '***';
        return $name . '@' . $parts[1];
    }
    
    function getApplicationTypeText($type) {
        $types = [
            'register' => '用户注册',
            'transfer' => '转账申请',
            'card_open' => '银行卡开户',
            'loan' => '贷款申请',
            'forgot_password' => '忘记密码',
            'other' => '其他申请'
        ];
        return $types[$type] ?? $type;
    }
    ?>
</body>
</html>
