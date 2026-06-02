<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    redirect('login.php');
}

define('IN_ADMIN', true);
$pageTitle = '个人征信查询';

ob_start();
require_once 'header.php';

$searchKeyword = sanitize($_GET['keyword'] ?? '');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$pageSize = 20;

$where = [];
$params = [];

if (!empty($searchKeyword)) {
    $where[] = "(u.username LIKE ? OR u.real_name LIKE ? OR u.id_card LIKE ? OR u.phone LIKE ?)";
    $keyword = "%{$searchKeyword}%";
    $params = [$keyword, $keyword, $keyword, $keyword];
}

$whereSql = empty($where) ? '1=1' : implode(' AND ', $where);

$countSql = "SELECT COUNT(*) FROM users u WHERE {$whereSql}";
$total = dbGetOne($countSql, $params);
$totalPages = ceil($total / $pageSize);
$offset = ($page - 1) * $pageSize;

$sql = "SELECT u.* FROM users u WHERE {$whereSql} ORDER BY u.created_at DESC LIMIT $pageSize OFFSET $offset";
$users = dbGetAll($sql, $params);
?>
<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-file-search me-2"></i>个人征信查询</h2>
            <p class="text-muted mb-0">查询和查看个人用户的征信信息</p>
        </div>
    </div>
</div>

<div class="card-box">
    <form method="GET" class="search-box mb-4">
        <input type="text" class="form-control" name="keyword" placeholder="搜索用户名、姓名、身份证号或手机号" value="<?php echo htmlspecialchars($searchKeyword); ?>">
        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> 搜索</button>
    </form>
    
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>用户名</th>
                    <th>姓名</th>
                    <th>身份证号</th>
                    <th>手机号</th>
                    <th>注册时间</th>
                    <th>状态</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="bi bi-search fs-1 d-block mb-2"></i>
                        暂无匹配的用户
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['real_name']); ?></td>
                    <td><small><?php echo maskIdCard($user['id_card']); ?></small></td>
                    <td><small><?php echo maskPhone($user['phone']); ?></small></td>
                    <td><?php echo formatDateShort($user['created_at']); ?></td>
                    <td><span class="badge <?php echo $user['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>"><?php echo getStatusText($user['status']); ?></span></td>
                    <td>
                        <a href="credit_personal.php?action=view&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary" title="查看征信">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
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
            <li class="page-item"><a class="page-link" href="?keyword=<?php echo urlencode($searchKeyword); ?>&page=<?php echo $page - 1; ?>">上一页</a></li>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="?keyword=<?php echo urlencode($searchKeyword); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
            <li class="page-item"><a class="page-link" href="?keyword=<?php echo urlencode($searchKeyword); ?>&page=<?php echo $page + 1; ?>">下一页</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])): ?>
<?php
$userId = (int)$_GET['id'];
$user = dbGetRow("SELECT * FROM users WHERE id = ?", [$userId]);
if (!$user) {
    $_SESSION['error'] = '用户不存在';
    redirect('credit_personal.php');
}

$loans = dbGetAll("SELECT * FROM loans WHERE user_id = ? AND user_type = 'personal' ORDER BY created_at DESC", [$userId]);
$cards = dbGetAll("SELECT * FROM cards WHERE user_id = ? AND user_type = 'personal' ORDER BY created_at DESC", [$userId]);
$bills = dbGetAll("SELECT * FROM bills WHERE user_id = ? AND user_type = 'personal' ORDER BY created_at DESC LIMIT 10", [$userId]);

$totalLoanAmount = array_sum(array_column($loans, 'amount'));
$totalCardBalance = array_sum(array_column($cards, 'balance'));
?>
<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-file-text me-2"></i>个人征信详情</h2>
        <a href="credit_personal.php?keyword=<?php echo urlencode($searchKeyword); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>返回列表</a>
    </div>
</div>

<div class="card-box mb-4">
    <h5 class="card-title">基本信息</h5>
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label text-muted">用户名</label>
            <div class="form-control-plaintext"><?php echo htmlspecialchars($user['username']); ?></div>
        </div>
        <div class="col-md-4">
            <label class="form-label text-muted">真实姓名</label>
            <div class="form-control-plaintext"><?php echo htmlspecialchars($user['real_name']); ?></div>
        </div>
        <div class="col-md-4">
            <label class="form-label text-muted">身份证号</label>
            <div class="form-control-plaintext"><?php echo $user['id_card']; ?></div>
        </div>
        <div class="col-md-4">
            <label class="form-label text-muted">手机号</label>
            <div class="form-control-plaintext"><?php echo $user['phone']; ?></div>
        </div>
        <div class="col-md-4">
            <label class="form-label text-muted">邮箱</label>
            <div class="form-control-plaintext"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
        </div>
        <div class="col-md-4">
            <label class="form-label text-muted">注册时间</label>
            <div class="form-control-plaintext"><?php echo formatDate($user['created_at']); ?></div>
        </div>
        <div class="col-md-4">
            <label class="form-label text-muted">用户状态</label>
            <div class="form-control-plaintext">
                <span class="badge <?php echo $user['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>"><?php echo getStatusText($user['status']); ?></span>
            </div>
        </div>
    </div>
</div>

<div class="card-box mb-4">
    <h5 class="card-title">资产概览</h5>
    <div class="row">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-label">银行卡总资产</div>
                <div class="stat-value">￥<?php echo formatMoney($totalCardBalance); ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card success">
                <div class="stat-label">累计贷款金额</div>
                <div class="stat-value">￥<?php echo formatMoney($totalLoanAmount); ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card info">
                <div class="stat-label">银行卡数量</div>
                <div class="stat-value"><?php echo count($cards); ?> 张</div>
            </div>
        </div>
    </div>
</div>

<div class="card-box mb-4">
    <h5 class="card-title">贷款记录</h5>
    <?php if (empty($loans)): ?>
    <p class="text-muted text-center py-4">暂无贷款记录</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>贷款编号</th>
                    <th>金额</th>
                    <th>期限</th>
                    <th>状态</th>
                    <th>申请时间</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($loans as $loan): ?>
                <tr>
                    <td><small><?php echo htmlspecialchars($loan['receipt_no']); ?></small></td>
                    <td>￥<?php echo formatMoney($loan['amount']); ?></td>
                    <td><?php echo $loan['term']; ?> 月</td>
                    <td><span class="badge <?php echo getLoanStatusClass($loan['status']); ?>"><?php echo getLoanStatusText($loan['status']); ?></span></td>
                    <td><?php echo formatDateShort($loan['created_at']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<div class="card-box">
    <h5 class="card-title">最近账单</h5>
    <?php if (empty($bills)): ?>
    <p class="text-muted text-center py-4">暂无账单记录</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>类型</th>
                    <th>标题</th>
                    <th>金额</th>
                    <th>时间</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bills as $bill): ?>
                <tr>
                    <td><span class="badge bg-info"><?php echo getBillTypeText($bill['type']); ?></span></td>
                    <td><?php echo htmlspecialchars($bill['title']); ?></td>
                    <td class="<?php echo $bill['amount'] < 0 ? 'text-danger' : 'text-success'; ?>">
                        <?php echo $bill['amount'] < 0 ? '-' : '+'; ?>￥<?php echo formatMoney(abs($bill['amount'])); ?>
                    </td>
                    <td><?php echo formatDateShort($bill['created_at']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
echo $content;
require_once 'footer.php';
?>