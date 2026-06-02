<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    redirect('login.php');
}

define('IN_ADMIN', true);
$pageTitle = '企业账单管理';

require_once 'header.php';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$pageSize = 20;
$search = sanitize($_GET['search'] ?? '');
$type = sanitize($_GET['type'] ?? '');
$dateFrom = sanitize($_GET['date_from'] ?? '');
$dateTo = sanitize($_GET['date_to'] ?? '');

$where = "b.user_type = 'enterprise'";
$params = [];
if ($search) {
    $where .= " AND (e.username LIKE ? OR e.company_name LIKE ? OR b.title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($type) {
    $where .= " AND b.type = ?";
    $params[] = $type;
}
if ($dateFrom) {
    $where .= " AND DATE(b.created_at) >= ?";
    $params[] = $dateFrom;
}
if ($dateTo) {
    $where .= " AND DATE(b.created_at) <= ?";
    $params[] = $dateTo;
}

$countSql = "SELECT COUNT(*) FROM bills b LEFT JOIN enterprises e ON b.user_id = e.id WHERE $where";
$total = dbGetOne($countSql, $params);
$totalPages = ceil($total / $pageSize);
$offset = ($page - 1) * $pageSize;

$sql = "SELECT b.*, e.username, e.company_name FROM bills b LEFT JOIN enterprises e ON b.user_id = e.id WHERE $where ORDER BY b.created_at DESC LIMIT $pageSize OFFSET $offset";
$bills = dbGetAll($sql, $params);

$typeMap = [
    'transfer_out' => '转出',
    'transfer_in' => '转入',
    'loan_disbursed' => '贷款放款',
    'loan_repayment' => '贷款还款',
    'stock_buy' => '股票买入',
    'stock_sell' => '股票卖出',
    'investment' => '理财购买',
    'investment_redeem' => '理财赎回',
    'invoice' => '发票',
    'fee' => '手续费',
    'other' => '其他'
];

ob_start();
?>
<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-file-earmark-text-fill me-2"></i>企业账单管理</h2>
            <p class="text-muted mb-0">查看所有企业用户账单记录</p>
        </div>
        <button onclick="exportBills()" class="btn btn-success">
            <i class="bi bi-download me-1"></i>导出CSV
        </button>
    </div>
</div>

<div class="card-box">
    <form method="GET" class="mb-4">
        <input type="hidden" name="page" value="1">
        <div class="row g-3">
            <div class="col-md-3">
                <input type="text" class="form-control" name="search" placeholder="搜索用户名/企业名称/标题" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <select class="form-select" name="type">
                    <option value="">全部类型</option>
                    <?php foreach ($typeMap as $key => $val): ?>
                    <option value="<?php echo $key; ?>" <?php echo $type === $key ? 'selected' : ''; ?>><?php echo $val; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control" name="date_from" value="<?php echo $dateFrom; ?>" placeholder="开始日期">
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control" name="date_to" value="<?php echo $dateTo; ?>" placeholder="结束日期">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>搜索</button>
                <a href="bills_enterprise.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i>重置</a>
            </div>
        </div>
    </form>
    
    <div class="table-responsive">
        <table class="table table-hover" id="billsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>用户名</th>
                    <th>企业名称</th>
                    <th>类型</th>
                    <th>金额</th>
                    <th>标题</th>
                    <th>时间</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bills)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">暂无数据</td>
                </tr>
                <?php else: ?>
                <?php foreach ($bills as $bill): ?>
                <tr>
                    <td><?php echo $bill['id']; ?></td>
                    <td><?php echo htmlspecialchars($bill['username']); ?></td>
                    <td><?php echo htmlspecialchars($bill['company_name']); ?></td>
                    <td><span class="badge bg-secondary"><?php echo $typeMap[$bill['type']] ?? $bill['type']; ?></span></td>
                    <td class="<?php echo $bill['type'] === 'transfer_in' || $bill['type'] === 'loan_disbursed' || $bill['type'] === 'stock_sell' || $bill['type'] === 'investment_redeem' ? 'text-success' : 'text-danger'; ?> fw-bold">
                        <?php echo $bill['type'] === 'transfer_in' || $bill['type'] === 'loan_disbursed' || $bill['type'] === 'stock_sell' || $bill['type'] === 'investment_redeem' ? '+' : '-'; ?>￥<?php echo formatMoney($bill['amount']); ?>
                    </td>
                    <td><?php echo htmlspecialchars($bill['title'] ?: '-'); ?></td>
                    <td><?php echo formatDateShort($bill['created_at']); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($totalPages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
            <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>">上一页</a></li>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
            <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>">下一页</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<script>
function exportBills() {
    window.location.href = 'export_bills.php?type=enterprise&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>';
}
</script>

<?php
$content = ob_get_clean();
echo $content;
require_once 'footer.php';
