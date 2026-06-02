<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    redirect('login.php');
}

define('IN_ADMIN', true);
$pageTitle = '企业贷款管理';

$action = $_GET['action'] ?? '';
$loanId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($action === 'disburse' && $loanId > 0) {
    $loan = dbGetRow("SELECT * FROM loans WHERE id = ? AND status = 'contract_signed'", [$loanId]);
    if (!$loan) {
        $_SESSION['error'] = '贷款记录不存在或状态不正确';
        redirect('loans_enterprise.php');
    }
    
    dbBeginTransaction();
    try {
        // 更新贷款状态
        dbUpdate('loans', [
            'status' => 'disbursed',
            'disbursed_at' => date('Y-m-d H:i:s'),
            'processed_by' => $_SESSION['admin_id'],
            'processed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$loanId]);
        
        // 获取用户首张激活银行卡并更新余额
        $userCard = dbGetRow("SELECT * FROM cards WHERE user_id = ? AND user_type = 'enterprise' AND status = 'active' ORDER BY id ASC LIMIT 1", [$loan['user_id']]);
        if ($userCard) {
            dbExecute("UPDATE cards SET balance = balance + ? WHERE id = ?", [$loan['amount'], $userCard['id']]);
            $currentBalance = $userCard['balance'] + $loan['amount'];
        } else {
            $currentBalance = $loan['amount'];
        }
        
        // 插入账单记录
        dbInsert('bills', [
            'user_id' => $loan['user_id'],
            'user_type' => $loan['user_type'],
            'type' => 'loan_disbursed',
            'amount' => $loan['amount'],
            'balance' => $currentBalance + $loan['amount'],
            'title' => '贷款放款',
            'content' => '贷款放款 - ' . $loan['receipt_no'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // 记录操作日志
        $logStmt = getDB()->prepare("INSERT INTO operation_logs (operator_id, operator_type, action, details, target_id, target_type, ip, created_at) VALUES (?, 'admin', 'disburse_loan', '放款企业贷款', ?, 'loan', ?, NOW())");
        $logStmt->execute([$_SESSION['admin_id'], $loanId, $_SERVER['REMOTE_ADDR'] ?? '']);
        
        dbCommit();
        $_SESSION['success'] = '贷款放款成功';
        redirect('loans_enterprise.php');
    } catch (Exception $e) {
        dbRollback();
        $_SESSION['error'] = '放款失败：' . $e->getMessage();
        redirect('loans_enterprise.php');
    }
}

require_once 'header.php';

if ($action === 'view' && $loanId > 0) {
    $loan = dbGetRow("SELECT l.*, e.username, e.company_name, e.contact_phone, lp.name as product_name FROM loans l LEFT JOIN enterprises e ON l.user_id = e.id LEFT JOIN loan_products lp ON l.product_id = lp.id WHERE l.id = ?", [$loanId]);
    if (!$loan) {
        $_SESSION['error'] = '贷款记录不存在';
        redirect('loans_enterprise.php');
    }
    
    $repayments = dbGetAll("SELECT * FROM repayments WHERE loan_id = ? ORDER BY period ASC", [$loanId]);
    ?>
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="bi bi-bank me-2"></i>贷款详情</h2>
            </div>
            <a href="loans_enterprise.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>返回列表
            </a>
        </div>
    </div>
    
    <div class="card-box">
        <h5 class="card-title">贷款信息</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label text-muted">贷款编号</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($loan['receipt_no']); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">贷款产品</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($loan['product_name'] ?? '自定义贷款'); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">贷款金额</label>
                <div class="form-control-plaintext text-success fw-bold">￥<?php echo formatMoney($loan['amount']); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">贷款期限</label>
                <div class="form-control-plaintext"><?php echo $loan['term']; ?> 个月</div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">年利率</label>
                <div class="form-control-plaintext"><?php echo $loan['rate']; ?>%</div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">月还款额</label>
                <div class="form-control-plaintext">￥<?php echo formatMoney($loan['monthly_payment']); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">总还款额</label>
                <div class="form-control-plaintext">￥<?php echo formatMoney($loan['total_payment']); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">贷款用途</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($loan['purpose'] ?? ''); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">状态</label>
                <div class="form-control-plaintext">
                    <span class="badge <?php echo getLoanStatusClass($loan['status']); ?>"><?php echo getLoanStatusText($loan['status']); ?></span>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">申请时间</label>
                <div class="form-control-plaintext"><?php echo formatDate($loan['created_at']); ?></div>
            </div>
            <?php if ($loan['admin_remark']): ?>
            <div class="col-12">
                <label class="form-label text-muted">审核备注</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($loan['admin_remark']); ?></div>
            </div>
            <?php endif; ?>
        </div>
        
        <h5 class="card-title mt-4">企业信息</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label text-muted">用户名</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($loan['username']); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">企业名称</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($loan['company_name']); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">联系电话</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($loan['contact_phone']); ?></div>
            </div>
        </div>
        
        <?php if (!empty($repayments)): ?>
        <h5 class="card-title mt-4">还款计划</h5>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>期数</th>
                        <th>应还日期</th>
                        <th>本金</th>
                        <th>利息</th>
                        <th>应还金额</th>
                        <th>实还金额</th>
                        <th>状态</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($repayments as $rp): ?>
                    <tr>
                        <td><?php echo $rp['period']; ?></td>
                        <td><?php echo formatDateShort($rp['due_date']); ?></td>
                        <td>￥<?php echo formatMoney($rp['principal']); ?></td>
                        <td>￥<?php echo formatMoney($rp['interest']); ?></td>
                        <td>￥<?php echo formatMoney($rp['amount']); ?></td>
                        <td><?php echo $rp['paid_amount'] > 0 ? '￥' . formatMoney($rp['paid_amount']) : '-'; ?></td>
                        <td><span class="badge <?php echo $rp['status'] === 'paid' ? 'bg-success' : ($rp['status'] === 'overdue' ? 'bg-danger' : 'bg-warning text-dark'); ?>"><?php echo $rp['status'] === 'paid' ? '已还' : ($rp['status'] === 'overdue' ? '逾期' : '待还'); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="view_contract.php?id=<?php echo $loanId; ?>" class="btn btn-primary">
                <i class="bi bi-file-earmark-text me-1"></i>查看合同
            </a>
        </div>
    </div>
    <?php
    require_once 'footer.php';
    exit;
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$pageSize = 20;
$search = sanitize($_GET['search'] ?? '');
$status = sanitize($_GET['status'] ?? '');

$where = "l.user_type = 'enterprise'";
$params = [];
if ($search) {
    $where .= " AND (l.receipt_no LIKE ? OR e.username LIKE ? OR e.company_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($status) {
    $where .= " AND l.status = ?";
    $params[] = $status;
}

$countSql = "SELECT COUNT(*) FROM loans l LEFT JOIN enterprises e ON l.user_id = e.id WHERE $where";
$total = dbGetOne($countSql, $params);
$totalPages = ceil($total / $pageSize);
$offset = ($page - 1) * $pageSize;

$sql = "SELECT l.*, e.username, e.company_name, lp.name as product_name FROM loans l LEFT JOIN enterprises e ON l.user_id = e.id LEFT JOIN loan_products lp ON l.product_id = lp.id WHERE $where ORDER BY l.created_at DESC LIMIT $pageSize OFFSET $offset";
$loans = dbGetAll($sql, $params);

ob_start();
?>
<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-bank me-2"></i>企业贷款管理</h2>
            <p class="text-muted mb-0">管理所有企业用户贷款记录</p>
        </div>
    </div>
</div>

<div class="card-box">
    <form method="GET" class="mb-4">
        <input type="hidden" name="page" value="1">
        <div class="row g-3">
            <div class="col-md-4">
                <input type="text" class="form-control" name="search" placeholder="搜索编号/用户名/企业名称" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="status">
                    <option value="">全部状态</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>待审核</option>
                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>已批准(待签约)</option>
                    <option value="contract_signed" <?php echo $status === 'contract_signed' ? 'selected' : ''; ?>>已签约(待放款)</option>
                    <option value="disbursed" <?php echo $status === 'disbursed' ? 'selected' : ''; ?>>已放款</option>
                    <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>已拒绝</option>
                    <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>已还清</option>
                    <option value="overdue" <?php echo $status === 'overdue' ? 'selected' : ''; ?>>已逾期</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>搜索</button>
                <a href="loans_enterprise.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i>重置</a>
            </div>
        </div>
    </form>
    
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>贷款编号</th>
                    <th>用户名</th>
                    <th>企业名称</th>
                    <th>贷款金额</th>
                    <th>期限</th>
                    <th>状态</th>
                    <th>申请时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($loans)): ?>
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">暂无数据</td>
                </tr>
                <?php else: ?>
                <?php foreach ($loans as $loan): ?>
                <tr>
                    <td><?php echo $loan['id']; ?></td>
                    <td><small><?php echo htmlspecialchars($loan['receipt_no']); ?></small></td>
                    <td><?php echo htmlspecialchars($loan['username']); ?></td>
                    <td><?php echo htmlspecialchars($loan['company_name']); ?></td>
                    <td class="text-success fw-bold">￥<?php echo formatMoney($loan['amount']); ?></td>
                    <td><?php echo $loan['term']; ?>月</td>
                    <td><span class="badge <?php echo getLoanStatusClass($loan['status']); ?>"><?php echo getLoanStatusText($loan['status']); ?></span></td>
                    <td><?php echo formatDateShort($loan['created_at']); ?></td>
                    <td>
                        <a href="loans_enterprise.php?action=view&id=<?php echo $loan['id']; ?>" class="btn btn-outline-primary btn-sm" title="查看">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="view_contract.php?id=<?php echo $loan['id']; ?>" class="btn btn-outline-secondary btn-sm ms-1" title="查看合同">
                            <i class="bi bi-file-earmark-text"></i>
                        </a>
                        <?php if ($loan['status'] === 'contract_signed'): ?>
                        <a href="loans_enterprise.php?action=disburse&id=<?php echo $loan['id']; ?>" class="btn btn-outline-success btn-sm ms-1" title="放款" onclick="return confirm('确认放款此笔贷款？')">
                            <i class="bi bi-cash"></i>
                        </a>
                        <?php endif; ?>
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
            <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">上一页</a></li>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
            <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">下一页</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
echo $content;
require_once 'footer.php';
