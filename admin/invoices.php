<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    redirect('login.php');
}

define('IN_ADMIN', true);
$pageTitle = '发票申请管理';

ob_start();
require_once 'header.php';

$action = $_GET['action'] ?? '';
$invoiceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($action === 'approve' && $invoiceId > 0) {
    $invoiceNo = 'INV' . date('YmdHis') . rand(1000, 9999);
    
    dbUpdate('invoices', [
        'status' => 'approved',
        'invoice_no' => $invoiceNo,
        'processed_by' => $_SESSION['admin_id'],
        'processed_at' => date('Y-m-d H:i:s')
    ], 'id = ?', [$invoiceId]);
    
    $logStmt = getDB()->prepare("INSERT INTO operation_logs (operator_id, operator_type, action, details, target_id, target_type, ip, created_at) VALUES (?, 'admin', 'approve_invoice', ?, ?, 'invoice', ?, NOW())");
    $logStmt->execute([$_SESSION['admin_id'], "审核通过发票申请，发票号: $invoiceNo", $invoiceId, $_SERVER['REMOTE_ADDR'] ?? '']);
    
    $_SESSION['success'] = "发票申请已通过，发票号: $invoiceNo";
    redirect('invoices.php');
}

if ($action === 'reject' && $invoiceId > 0) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $reason = sanitize($_POST['reason'] ?? '');
        
        if (empty($reason)) {
            $_SESSION['error'] = '请填写拒绝原因';
            redirect('invoices.php?action=reject&id=' . $invoiceId);
        }
        
        dbUpdate('invoices', [
            'status' => 'rejected',
            'admin_remark' => $reason,
            'processed_by' => $_SESSION['admin_id'],
            'processed_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$invoiceId]);
        
        $logStmt = getDB()->prepare("INSERT INTO operation_logs (operator_id, operator_type, action, details, target_id, target_type, ip, created_at) VALUES (?, 'admin', 'reject_invoice', ?, ?, 'invoice', ?, NOW())");
        $logStmt->execute([$_SESSION['admin_id'], '拒绝发票申请: ' . $reason, $invoiceId, $_SERVER['REMOTE_ADDR'] ?? '']);
        
        $_SESSION['success'] = '已拒绝发票申请';
        redirect('invoices.php');
    }
    ?>
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="bi bi-x-circle text-danger me-2"></i>拒绝发票申请</h2>
            </div>
            <a href="invoices.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>返回列表
            </a>
        </div>
    </div>
    
    <div class="card-box">
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">拒绝原因 <span class="text-danger">*</span></label>
                <textarea class="form-control" name="reason" rows="3" placeholder="请输入拒绝原因" required></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-danger"><i class="bi bi-x-lg me-1"></i>确认拒绝</button>
                <a href="invoices.php" class="btn btn-outline-secondary">取消</a>
            </div>
        </form>
    </div>
    <?php
    require_once 'footer.php';
    exit;
}

if ($action === 'view' && $invoiceId > 0) {
    $invoice = dbGetRow("SELECT i.*, 
        CASE WHEN i.user_type = 'personal' THEN u.username ELSE e.username END as username,
        CASE WHEN i.user_type = 'personal' THEN u.real_name ELSE e.company_name END as user_name,
        CASE WHEN i.user_type = 'personal' THEN u.phone ELSE e.contact_phone END as user_phone
        FROM invoices i 
        LEFT JOIN users u ON i.user_id = u.id AND i.user_type = 'personal'
        LEFT JOIN enterprises e ON i.user_id = e.id AND i.user_type = 'enterprise'
        WHERE i.id = ?", [$invoiceId]);
    
    if (!$invoice) {
        $_SESSION['error'] = '发票申请不存在';
        redirect('invoices.php');
    }
    ?>
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="bi bi-receipt me-2"></i>发票详情</h2>
            <a href="invoices.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>返回列表</a>
        </div>
    </div>
    
    <div class="card-box">
        <h5 class="card-title">发票信息</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label text-muted">发票编号</label>
                <div class="form-control-plaintext"><?php echo $invoice['invoice_no'] ? htmlspecialchars($invoice['invoice_no']) : '-'; ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">申请类型</label>
                <div class="form-control-plaintext"><?php echo $invoice['type'] === 'personal' ? '个人' : '企业'; ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">发票抬头</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($invoice['title']); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">发票金额</label>
                <div class="form-control-plaintext text-success fw-bold">￥<?php echo formatMoney($invoice['amount']); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">发票内容</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($invoice['content'] ?? ''); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">接收邮箱</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($invoice['email']); ?></div>
            </div>
            <?php if ($invoice['tax_id']): ?>
            <div class="col-md-6">
                <label class="form-label text-muted">税号</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($invoice['tax_id']); ?></div>
            </div>
            <?php endif; ?>
            <div class="col-md-6">
                <label class="form-label text-muted">状态</label>
                <div class="form-control-plaintext">
                    <span class="badge <?php echo getInvoiceStatusClass($invoice['status']); ?>"><?php echo getInvoiceStatusText($invoice['status']); ?></span>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">申请时间</label>
                <div class="form-control-plaintext"><?php echo formatDate($invoice['created_at']); ?></div>
            </div>
        </div>
        
        <h5 class="card-title mt-4">申请人信息</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label text-muted">用户名</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($invoice['username']); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">姓名/企业</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($invoice['user_name']); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">联系电话</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($invoice['user_phone']); ?></div>
            </div>
        </div>
        
        <?php if ($invoice['status'] === 'pending'): ?>
        <hr class="my-4">
        <div class="d-flex gap-2">
            <a href="invoices.php?action=approve&id=<?php echo $invoiceId; ?>" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>通过申请</a>
            <a href="invoices.php?action=reject&id=<?php echo $invoiceId; ?>" class="btn btn-danger"><i class="bi bi-x-lg me-1"></i>拒绝申请</a>
        </div>
        <?php elseif ($invoice['status'] === 'approved'): ?>
        <hr class="my-4">
        <div class="d-flex gap-2">
            <a href="invoice_template.php?id=<?php echo $invoiceId; ?>" class="btn btn-success"><i class="bi bi-receipt me-1"></i>开发票</a>
        </div>
        <?php elseif ($invoice['status'] === 'sent'): ?>
        <hr class="my-4">
        <div class="alert alert-success">此发票已标记为已发送，可重新查看模板。</div>
        <a href="invoice_template.php?id=<?php echo $invoiceId; ?>" class="btn btn-outline-success"><i class="bi bi-receipt me-1"></i>重新查看发票模板</a>
        <?php endif; ?>
    </div>
    <?php
    require_once 'footer.php';
    exit;
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$pageSize = 20;
$search = sanitize($_GET['search'] ?? '');
$status = sanitize($_GET['status'] ?? '');

$where = "1=1";
$params = [];
if ($search) {
    $where .= " AND (i.invoice_no LIKE ? OR i.title LIKE ? OR i.receipt_no LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($status) {
    $where .= " AND i.status = ?";
    $params[] = $status;
}

$countSql = "SELECT COUNT(*) FROM invoices i WHERE $where";
$total = dbGetOne($countSql, $params);
$totalPages = ceil($total / $pageSize);
$offset = ($page - 1) * $pageSize;

$sql = "SELECT i.*, 
    CASE WHEN i.user_type = 'personal' THEN u.username ELSE e.username END as username,
    CASE WHEN i.user_type = 'personal' THEN u.real_name ELSE e.company_name END as user_name
    FROM invoices i 
    LEFT JOIN users u ON i.user_id = u.id AND i.user_type = 'personal'
    LEFT JOIN enterprises e ON i.user_id = e.id AND i.user_type = 'enterprise'
    WHERE $where ORDER BY i.created_at DESC LIMIT $pageSize OFFSET $offset";
$invoices = dbGetAll($sql, $params);

ob_start();
?>
<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-receipt me-2"></i>发票申请管理</h2>
            <p class="text-muted mb-0">处理用户的发票开票申请</p>
        </div>
    </div>
</div>

<div class="card-box">
    <form method="GET" class="mb-4">
        <input type="hidden" name="page" value="1">
        <div class="row g-3">
            <div class="col-md-4">
                <input type="text" class="form-control" name="search" placeholder="搜索发票号/抬头/单号" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="status">
                    <option value="">全部状态</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>待处理</option>
                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>已通过</option>
                    <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>已拒绝</option>
                    <option value="sent" <?php echo $status === 'sent' ? 'selected' : ''; ?>>已发送</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>搜索</button>
                <a href="invoices.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i>重置</a>
            </div>
        </div>
    </form>
    
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>发票编号</th>
                    <th>发票抬头</th>
                    <th>申请人</th>
                    <th>金额</th>
                    <th>状态</th>
                    <th>申请时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoices)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">暂无数据</td>
                </tr>
                <?php else: ?>
                <?php foreach ($invoices as $inv): ?>
                <tr>
                    <td><?php echo $inv['id']; ?></td>
                    <td><small><?php echo $inv['invoice_no'] ? htmlspecialchars($inv['invoice_no']) : '-'; ?></small></td>
                    <td><?php echo htmlspecialchars($inv['title']); ?></td>
                    <td><?php echo htmlspecialchars($inv['user_name']); ?></td>
                    <td class="text-success fw-bold">￥<?php echo formatMoney($inv['amount']); ?></td>
                    <td><span class="badge <?php echo getInvoiceStatusClass($inv['status']); ?>"><?php echo getInvoiceStatusText($inv['status']); ?></span></td>
                    <td><?php echo formatDateShort($inv['created_at']); ?></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="invoices.php?action=view&id=<?php echo $inv['id']; ?>" class="btn btn-outline-primary" title="查看"><i class="bi bi-eye"></i></a>
                            <?php if ($inv['status'] === 'pending'): ?>
                            <a href="invoices.php?action=approve&id=<?php echo $inv['id']; ?>" class="btn btn-outline-success" title="通过"><i class="bi bi-check-lg"></i></a>
                            <a href="invoices.php?action=reject&id=<?php echo $inv['id']; ?>" class="btn btn-outline-danger" title="拒绝"><i class="bi bi-x-lg"></i></a>
                            <?php endif; ?>
                            <?php if ($inv['status'] === 'approved' || $inv['status'] === 'sent'): ?>
                            <a href="invoice_template.php?id=<?php echo $inv['id']; ?>" class="btn btn-outline-success" title="开发票"><i class="bi bi-receipt"></i></a>
                            <?php endif; ?>
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
