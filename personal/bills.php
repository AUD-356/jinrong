<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$userId = $_SESSION['user_id'];
$activeMenu = 'bills';

$page = intval($_GET['page'] ?? 1);
$type = sanitize($_GET['type'] ?? '');
$startDate = sanitize($_GET['start_date'] ?? '');
$endDate = sanitize($_GET['end_date'] ?? '');
$keyword = sanitize($_GET['keyword'] ?? '');

$where = "user_id = ? AND user_type = 'personal'";
$params = [$userId];

if (!empty($type)) {
    $where .= " AND type = ?";
    $params[] = $type;
}

if (!empty($startDate)) {
    $where .= " AND DATE(created_at) >= ?";
    $params[] = $startDate;
}

if (!empty($endDate)) {
    $where .= " AND DATE(created_at) <= ?";
    $params[] = $endDate;
}

if (!empty($keyword)) {
    $where .= " AND (title LIKE ? OR content LIKE ?)";
    $params[] = '%' . $keyword . '%';
    $params[] = '%' . $keyword . '%';
}

$total = dbGetOne("SELECT COUNT(*) FROM bills WHERE {$where}", $params);
$pageSize = 20;
$offset = ($page - 1) * $pageSize;

$bills = dbGetAll("SELECT * FROM bills WHERE {$where} ORDER BY created_at DESC LIMIT {$pageSize} OFFSET {$offset}", $params);

$totalPages = ceil($total / $pageSize);

$income = dbGetOne("SELECT COALESCE(SUM(amount), 0) FROM bills WHERE {$where} AND amount > 0", $params);
$expense = dbGetOne("SELECT COALESCE(SUM(ABS(amount)), 0) FROM bills WHERE {$where} AND amount < 0", $params);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>账单明细 - <?php echo SITE_NAME; ?></title>
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
            <h2><i class="bi bi-file-earmark-text me-2"></i>账单明细</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">首页</a></li>
                    <li class="breadcrumb-item active">账单明细</li>
                </ol>
            </nav>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card success">
                    <div class="stat-label">总收入</div>
                    <div class="stat-value text-success"><?php echo formatMoney($income); ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card warning">
                    <div class="stat-label">总支出</div>
                    <div class="stat-value text-danger"><?php echo formatMoney($expense); ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-label">净收益</div>
                    <div class="stat-value <?php echo $income - $expense >= 0 ? 'text-success' : 'text-danger'; ?>">
                        <?php echo formatMoney($income - $expense); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-box">
            <div class="card-title d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-ul me-2"></i>交易记录</span>
                <button class="btn btn-sm btn-outline-primary" onclick="exportBills()">
                    <i class="bi bi-download me-1"></i>导出
                </button>
            </div>
            
            <form method="GET" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-2">
                        <select class="form-select" name="type">
                            <option value="">全部类型</option>
                            <option value="transfer_in" <?php echo $type === 'transfer_in' ? 'selected' : ''; ?>>转入</option>
                            <option value="transfer_out" <?php echo $type === 'transfer_out' ? 'selected' : ''; ?>>转出</option>
                            <option value="loan_disbursed" <?php echo $type === 'loan_disbursed' ? 'selected' : ''; ?>>贷款发放</option>
                            <option value="loan_repayment" <?php echo $type === 'loan_repayment' ? 'selected' : ''; ?>>还款</option>
                            <option value="stock_buy" <?php echo $type === 'stock_buy' ? 'selected' : ''; ?>>股票买入</option>
                            <option value="stock_sell" <?php echo $type === 'stock_sell' ? 'selected' : ''; ?>>股票卖出</option>
                            <option value="investment" <?php echo $type === 'investment' ? 'selected' : ''; ?>>理财投资</option>
                            <option value="invoice" <?php echo $type === 'invoice' ? 'selected' : ''; ?>>发票</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control" name="start_date" value="<?php echo $startDate; ?>" placeholder="开始日期">
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control" name="end_date" value="<?php echo $endDate; ?>" placeholder="结束日期">
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="搜索关键词">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-1"></i>搜索
                        </button>
                        <a href="bills.php" class="btn btn-outline-secondary ms-2">重置</a>
                    </div>
                </div>
            </form>
            
            <?php if (empty($bills)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h4>暂无账单记录</h4>
                <p>您还没有任何交易记录</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="billsTable">
                    <thead>
                        <tr>
                            <th>时间</th>
                            <th>类型</th>
                            <th>摘要</th>
                            <th>金额</th>
                            <th>余额</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bills as $bill): ?>
                        <?php
                        $typeText = getBillTypeText($bill['type']);
                        $typeClass = getBillTypeClass($bill['type']);
                        $amountClass = $bill['amount'] >= 0 ? 'text-success' : 'text-danger';
                        $amountSign = $bill['amount'] >= 0 ? '+' : '';
                        ?>
                        <tr>
                            <td><small><?php echo formatDate($bill['created_at']); ?></small></td>
                            <td><span class="badge <?php echo $typeClass; ?>"><?php echo $typeText; ?></span></td>
                            <td>
                                <div><?php echo htmlspecialchars($bill['title']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($bill['content']); ?></small>
                            </td>
                            <td class="<?php echo $amountClass; ?> fw-bold">
                                <?php echo $amountSign; ?><?php echo formatMoney(abs($bill['amount'])); ?>
                            </td>
                            <td><?php echo formatMoney($bill['balance']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>&type=<?php echo $type; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&keyword=<?php echo urlencode($keyword); ?>">上一页</a></li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&type=<?php echo $type; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&keyword=<?php echo urlencode($keyword); ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>&type=<?php echo $type; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&keyword=<?php echo urlencode($keyword); ?>">下一页</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script>
        function exportBills() {
            exportTable('billsTable', '账单明细_<?php echo date('Ymd'); ?>');
        }
    </script>
    
    <?php
    ?>
</body>
</html>
