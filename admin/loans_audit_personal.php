<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    redirect('login.php');
}

define('IN_ADMIN', true);
$pageTitle = '个人贷款审核';

ob_start();
require_once 'header.php';

$action = $_GET['action'] ?? '';
$loanId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($action === 'approve' && $loanId > 0) {
    $loan = dbGetRow("SELECT * FROM loans WHERE id = ? AND status = 'pending'", [$loanId]);
    if (!$loan) {
        $_SESSION['error'] = '贷款申请不存在或已处理';
        redirect('loans_audit_personal.php');
    }
    
    dbUpdate('loans', [
        'status' => 'approved',
        'processed_by' => $_SESSION['admin_id'],
        'processed_at' => date('Y-m-d H:i:s')
    ], 'id = ?', [$loanId]);
    
    $logStmt = getDB()->prepare("INSERT INTO operation_logs (operator_id, operator_type, action, details, target_id, target_type, ip, created_at) VALUES (?, 'admin', 'approve_loan', '审核通过个人贷款申请', ?, 'loan', ?, NOW())");
    $logStmt->execute([$_SESSION['admin_id'], $loanId, $_SERVER['REMOTE_ADDR'] ?? '']);
    
    $_SESSION['success'] = '贷款申请已通过（待签约）';
    redirect('loans_audit_personal.php');
}

if ($action === 'reject' && $loanId > 0) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $reason = sanitize($_POST['reason'] ?? '');
        
        if (empty($reason)) {
            $_SESSION['error'] = '请填写拒绝原因';
            redirect('loans_audit_personal.php?action=reject&id=' . $loanId);
        }
        
        dbUpdate('loans', [
            'status' => 'rejected',
            'admin_remark' => $reason,
            'processed_by' => $_SESSION['admin_id'],
            'processed_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$loanId]);
        
        $logStmt = getDB()->prepare("INSERT INTO operation_logs (operator_id, operator_type, action, details, target_id, target_type, ip, created_at) VALUES (?, 'admin', 'reject_loan', ?, ?, 'loan', ?, NOW())");
        $logStmt->execute([$_SESSION['admin_id'], '拒绝个人贷款申请: ' . $reason, $loanId, $_SERVER['REMOTE_ADDR'] ?? '']);
        
        $_SESSION['success'] = '已拒绝贷款申请';
        redirect('loans_audit_personal.php');
    }
    ?>
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="bi bi-x-circle text-danger me-2"></i>拒绝贷款申请</h2>
            </div>
            <a href="loans_audit_personal.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>返回列表
            </a>
        </div>
    </div>
    
    <div class="card-box">
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">拒绝原因 <span class="text-danger">*</span></label>
                <select class="form-select mb-2" id="rejectReasonSelect" onchange="toggleReason()">
                    <option value="">请选择原因类型</option>
                    <option value="资质不符合要求">资质不符合要求</option>
                    <option value="信用记录不良">信用记录不良</option>
                    <option value="收入证明不足">收入证明不足</option>
                    <option value="贷款用途不符合规定">贷款用途不符合规定</option>
                    <option value="其他">其他</option>
                </select>
                <textarea class="form-control" name="reason" id="rejectReason" rows="3" placeholder="请输入详细拒绝原因" required></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-danger"><i class="bi bi-x-lg me-1"></i>确认拒绝</button>
                <a href="loans_audit_personal.php" class="btn btn-outline-secondary">取消</a>
            </div>
        </form>
    </div>
    <script>
    function toggleReason() {
        var select = document.getElementById('rejectReasonSelect');
        var textarea = document.getElementById('rejectReason');
        if (select.value && select.value !== '其他') {
            textarea.value = select.value;
        }
    }
    </script>
    <?php
    require_once 'footer.php';
    exit;
}

if ($action === 'view' && $loanId > 0) {
    $loan = dbGetRow("SELECT l.*, u.username, u.real_name, u.phone, lp.name as product_name FROM loans l LEFT JOIN users u ON l.user_id = u.id LEFT JOIN loan_products lp ON l.product_id = lp.id WHERE l.id = ?", [$loanId]);
    if (!$loan) {
        $_SESSION['error'] = '贷款申请不存在';
        redirect('loans_audit_personal.php');
    }
    ?>
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="bi bi-cash-stack me-2"></i>贷款申请详情</h2>
            <a href="loans_audit_personal.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>返回列表</a>
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
                <div class="form-control-plaintext text-success fw-bold fs-5">￥<?php echo formatMoney($loan['amount']); ?></div>
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
                <label class="form-label text-muted">收入情况</label>
                <div class="form-control-plaintext">￥<?php echo formatMoney($loan['income'] ?? 0); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">就业状态</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($loan['employment_status'] ?? ''); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">申请时间</label>
                <div class="form-control-plaintext"><?php echo formatDate($loan['created_at']); ?></div>
            </div>
        </div>
        
        <?php if (!empty($loan['application_content'])): ?>
        <h5 class="card-title mt-4">申请内容</h5>
        <div class="form-control-plaintext bg-light p-3 rounded"><?php echo nl2br(htmlspecialchars($loan['application_content'])); ?></div>
        <?php endif; ?>
        
        <h5 class="card-title mt-4">借款人信息</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label text-muted">用户名</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($loan['username']); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">真实姓名</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($loan['real_name']); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">手机号码</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($loan['phone']); ?></div>
            </div>
        </div>
        
        <?php if ($loan['status'] === 'pending'): ?>
        <hr class="my-4">
        <div class="d-flex gap-2">
            <a href="loans_audit_personal.php?action=approve&id=<?php echo $loanId; ?>" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>通过申请</a>
            <a href="loans_audit_personal.php?action=reject&id=<?php echo $loanId; ?>" class="btn btn-danger"><i class="bi bi-x-lg me-1"></i>拒绝申请</a>
        </div>
        <?php endif; ?>
    </div>
    <?php
    require_once 'footer.php';
    exit;
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$pageSize = 20;

$countSql = "SELECT COUNT(*) FROM loans WHERE status = 'pending' AND user_type = 'personal'";
$total = dbGetOne($countSql);
$totalPages = ceil($total / $pageSize);
$offset = ($page - 1) * $pageSize;

$sql = "SELECT l.*, u.username, u.real_name, lp.name as product_name FROM loans l LEFT JOIN users u ON l.user_id = u.id LEFT JOIN loan_products lp ON l.product_id = lp.id WHERE l.status = 'pending' AND l.user_type = 'personal' ORDER BY l.created_at DESC LIMIT $pageSize OFFSET $offset";
$loans = dbGetAll($sql);

ob_start();
?>
<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-clipboard-check me-2"></i>个人贷款审核</h2>
            <p class="text-muted mb-0">审核个人用户的贷款申请</p>
        </div>
        <span class="badge bg-warning text-dark fs-6">
            <i class="bi bi-clock me-1"></i>待审核: <?php echo $total; ?> 个
        </span>
    </div>
</div>

<div class="card-box">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>贷款编号</th>
                    <th>用户名</th>
                    <th>姓名</th>
                    <th>贷款金额</th>
                    <th>期限</th>
                    <th>申请时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($loans)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="bi bi-check-circle fs-1 d-block mb-2"></i>
                        暂无待审核申请
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($loans as $loan): ?>
                <tr>
                    <td><?php echo $loan['id']; ?></td>
                    <td><small><?php echo htmlspecialchars($loan['receipt_no']); ?></small></td>
                    <td><?php echo htmlspecialchars($loan['username']); ?></td>
                    <td><?php echo htmlspecialchars($loan['real_name']); ?></td>
                    <td class="text-success fw-bold">￥<?php echo formatMoney($loan['amount']); ?></td>
                    <td><?php echo $loan['term']; ?>月</td>
                    <td><?php echo formatDateShort($loan['created_at']); ?></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="loans_audit_personal.php?action=view&id=<?php echo $loan['id']; ?>" class="btn btn-outline-primary" title="查看"><i class="bi bi-eye"></i></a>
                            <a href="loans_audit_personal.php?action=approve&id=<?php echo $loan['id']; ?>" class="btn btn-outline-success" title="通过"><i class="bi bi-check-lg"></i></a>
                            <a href="loans_audit_personal.php?action=reject&id=<?php echo $loan['id']; ?>" class="btn btn-outline-danger" title="拒绝"><i class="bi bi-x-lg"></i></a>
                        </div>
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
            <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>">上一页</a></li>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
            <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>">下一页</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
echo $content;
require_once 'footer.php';
