<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    redirect('login.php');
}

define('IN_ADMIN', true);
$pageTitle = '操作日志';

require_once 'header.php';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$pageSize = 50;
$search = sanitize($_GET['search'] ?? '');
$action = sanitize($_GET['action'] ?? '');
$dateFrom = sanitize($_GET['date_from'] ?? '');
$dateTo = sanitize($_GET['date_to'] ?? '');

$where = "1=1";
$params = [];
if ($search) {
    $where .= " AND (l.details LIKE ? OR a.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($action) {
    $where .= " AND l.action = ?";
    $params[] = $action;
}
if ($dateFrom) {
    $where .= " AND DATE(l.created_at) >= ?";
    $params[] = $dateFrom;
}
if ($dateTo) {
    $where .= " AND DATE(l.created_at) <= ?";
    $params[] = $dateTo;
}

$countSql = "SELECT COUNT(*) FROM operation_logs l LEFT JOIN admin_users a ON l.operator_id = a.id WHERE $where";
$total = dbGetOne($countSql, $params);
$totalPages = ceil($total / $pageSize);
$offset = ($page - 1) * $pageSize;

$sql = "SELECT l.*, a.username as admin_username FROM operation_logs l LEFT JOIN admin_users a ON l.operator_id = a.id WHERE $where ORDER BY l.created_at DESC LIMIT $pageSize OFFSET $offset";
$logs = dbGetAll($sql, $params);

$actionTypes = dbGetAll("SELECT DISTINCT action FROM operation_logs WHERE operator_type = 'admin' ORDER BY action");

$actionMap = [
    'login' => '登录',
    'logout' => '退出',
    'edit_user' => '编辑用户',
    'disable_user' => '冻结用户',
    'enable_user' => '解冻用户',
    'reset_password' => '重置密码',
    'edit_enterprise' => '编辑企业',
    'disable_enterprise' => '冻结企业',
    'enable_enterprise' => '启用企业',
    'approve_user' => '审核通过用户',
    'reject_user' => '拒绝用户',
    'approve_enterprise' => '审核通过企业',
    'reject_enterprise' => '拒绝企业',
    'approve_card' => '审核通过卡片',
    'reject_card' => '拒绝卡片',
    'freeze_card' => '冻结卡片',
    'unfreeze_card' => '解冻卡片',
    'approve_loan' => '审核通过贷款',
    'reject_loan' => '拒绝贷款',
    'approve_invoice' => '审核通过发票',
    'reject_invoice' => '拒绝发票',
    'approve_transfer' => '审核通过转账',
    'reject_transfer' => '拒绝转账',
    'update_config' => '更新配置'
];

ob_start();
?>
<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-clock-history me-2"></i>操作日志</h2>
            <p class="text-muted mb-0">查看管理员操作记录</p>
        </div>
    </div>
</div>

<div class="card-box">
    <form method="GET" class="mb-4">
        <input type="hidden" name="page" value="1">
        <div class="row g-3">
            <div class="col-md-3">
                <input type="text" class="form-control" name="search" placeholder="搜索操作详情/管理员" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <select class="form-select" name="action">
                    <option value="">全部操作</option>
                    <?php foreach ($actionTypes as $type): ?>
                    <option value="<?php echo htmlspecialchars($type['action']); ?>" <?php echo $action === $type['action'] ? 'selected' : ''; ?>><?php echo $actionMap[$type['action']] ?? $type['action']; ?></option>
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
                <a href="system_logs.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i>重置</a>
            </div>
        </div>
    </form>
    
    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>时间</th>
                    <th>管理员</th>
                    <th>操作类型</th>
                    <th>操作详情</th>
                    <th>IP地址</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">暂无数据</td>
                </tr>
                <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo $log['id']; ?></td>
                    <td><small><?php echo formatDate($log['created_at']); ?></small></td>
                    <td><?php echo htmlspecialchars($log['admin_username'] ?? '系统'); ?></td>
                    <td><span class="badge bg-secondary"><?php echo $actionMap[$log['action']] ?? $log['action']; ?></span></td>
                    <td><small><?php echo htmlspecialchars($log['details'] ?? ''); ?></small></td>
                    <td><small class="text-muted"><?php echo htmlspecialchars($log['ip'] ?? ''); ?></small></td>
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
            <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&action=<?php echo urlencode($action); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>">上一页</a></li>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&action=<?php echo urlencode($action); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
            <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&action=<?php echo urlencode($action); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>">下一页</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
echo $content;
require_once 'footer.php';
