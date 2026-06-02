<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    redirect('login.php');
}

define('IN_ADMIN', true);
$pageTitle = '企业开卡申请审核';

$action = $_GET['action'] ?? '';
$cardId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($action === 'approve' && $cardId > 0) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $bankCode = sanitize($_POST['bank_code'] ?? '');
        
        if (empty($bankCode)) {
            $_SESSION['error'] = '请选择开户银行';
            redirect('cards_audit_enterprise.php?action=approve&id=' . $cardId);
        }
        
        $card = dbGetRow("SELECT * FROM cards WHERE id = ?", [$cardId]);
        if (!$card) {
            $_SESSION['error'] = '申请不存在';
            redirect('cards_audit_enterprise.php');
        }
        
        $cardNo = generateCardNumber();
        $cardPwd = generateCardPwd();
        
        dbUpdate('cards', [
            'status' => 'active',
            'bank_code' => $bankCode,
            'card_no' => $cardNo,
            'card_pwd' => $cardPwd,
            'activated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$cardId]);
        
        $logStmt = getDB()->prepare("INSERT INTO operation_logs (operator_id, operator_type, action, details, target_id, target_type, ip, created_at) VALUES (?, 'admin', 'approve_card', ?, ?, 'card', ?, NOW())");
        $logStmt->execute([$_SESSION['admin_id'], "审核通过企业开卡申请，卡号: $cardNo", $cardId, $_SERVER['REMOTE_ADDR'] ?? '']);
        
        $_SESSION['success'] = "开卡申请已通过，卡号: $cardNo，密码: $cardPwd";
        redirect('cards_audit_enterprise.php');
    }
    
    $card = dbGetRow("SELECT c.*, e.username, e.company_name, e.contact_phone FROM cards c LEFT JOIN enterprises e ON c.user_id = e.id WHERE c.id = ?", [$cardId]);
    if (!$card) {
        $_SESSION['error'] = '申请不存在';
        redirect('cards_audit_enterprise.php');
    }
    
    $banks = [
        'ICBC' => '中国工商银行',
        'ABC' => '中国农业银行',
        'BOC' => '中国银行',
        'CCB' => '中国建设银行',
        'COMM' => '交通银行',
        'CMB' => '招商银行',
        'CITIC' => '中信银行',
        'CEB' => '中国光大银行',
        'HXB' => '华夏银行',
        'CMBC' => '民生银行',
        'GDB' => '广发银行',
        'PAB' => '平安银行',
        'PSBC' => '中国邮政储蓄银行',
        'SPDB' => '上海浦东发展银行',
        'CIB' => '兴业银行'
    ];
    require_once 'header.php';
    ?>
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="bi bi-check-circle text-success me-2"></i>通过开卡申请</h2>
            </div>
            <a href="cards_audit_enterprise.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>返回列表
            </a>
        </div>
    </div>
    
    <div class="card-box">
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            将为企业 <strong><?php echo htmlspecialchars($card['company_name']); ?></strong> 开户，请选择开户银行。
        </div>
        
        <form method="POST">
            <h5 class="card-title">企业信息</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label text-muted">用户名</label>
                    <div class="form-control-plaintext"><?php echo htmlspecialchars($card['username']); ?></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted">企业名称</label>
                    <div class="form-control-plaintext"><?php echo htmlspecialchars($card['company_name']); ?></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted">联系电话</label>
                    <div class="form-control-plaintext"><?php echo htmlspecialchars($card['contact_phone']); ?></div>
                </div>
            </div>
            
            <h5 class="card-title">开户信息</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">选择开户银行 <span class="text-danger">*</span></label>
                    <select class="form-select" name="bank_code" required>
                        <option value="">请选择银行</option>
                        <?php foreach ($banks as $code => $name): ?>
                        <option value="<?php echo $code; ?>"><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <div class="alert alert-warning">
                        <i class="bi bi-lightbulb me-2"></i>
                        <strong>系统将自动生成银行卡号和密码</strong>
                    </div>
                </div>
            </div>
            
            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-lg me-1"></i>确认通过
                </button>
                <a href="cards_audit_enterprise.php" class="btn btn-outline-secondary">取消</a>
            </div>
        </form>
    </div>
    <?php
    require_once 'footer.php';
    exit;
}

if ($action === 'reject' && $cardId > 0) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $reason = sanitize($_POST['reason'] ?? '');
        
        if (empty($reason)) {
            $_SESSION['error'] = '请填写拒绝原因';
            redirect('cards_audit_enterprise.php?action=reject&id=' . $cardId);
        }
        
        dbUpdate('cards', [
            'status' => 'closed',
            'frozen_reason' => $reason
        ], 'id = ?', [$cardId]);
        
        $logStmt = getDB()->prepare("INSERT INTO operation_logs (operator_id, operator_type, action, details, target_id, target_type, ip, created_at) VALUES (?, 'admin', 'reject_card', ?, ?, 'card', ?, NOW())");
        $logStmt->execute([$_SESSION['admin_id'], '拒绝企业开卡申请: ' . $reason, $cardId, $_SERVER['REMOTE_ADDR'] ?? '']);
        
        $_SESSION['success'] = '已拒绝开卡申请';
        redirect('cards_audit_enterprise.php');
    }
    require_once 'header.php';
    ?>
    <div class="page-header mb-4">
        <h2><i class="bi bi-x-circle text-danger me-2"></i>拒绝开卡申请</h2>
    </div>
    <div class="card-box">
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">拒绝原因 <span class="text-danger">*</span></label>
                <select class="form-select mb-2" id="rejectReasonSelect" onchange="toggleReason()">
                    <option value="">请选择原因类型</option>
                    <option value="资质材料不完整">资质材料不完整</option>
                    <option value="信息填写错误">信息填写错误</option>
                    <option value="资质不符合">资质不符合</option>
                    <option value="其他">其他</option>
                </select>
                <textarea class="form-control" name="reason" id="rejectReason" rows="3" placeholder="请输入详细拒绝原因" required></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-danger"><i class="bi bi-x-lg me-1"></i>确认拒绝</button>
                <a href="cards_audit_enterprise.php" class="btn btn-outline-secondary">取消</a>
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

if ($action === 'view' && $cardId > 0) {
    $card = dbGetRow("SELECT c.*, e.username, e.company_name, e.contact_phone FROM cards c LEFT JOIN enterprises e ON c.user_id = e.id WHERE c.id = ?", [$cardId]);
    if (!$card) {
        $_SESSION['error'] = '申请不存在';
        redirect('cards_audit_enterprise.php');
    }
    require_once 'header.php';
    ?>
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="bi bi-credit-card me-2"></i>申请详情</h2>
            <a href="cards_audit_enterprise.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>返回列表</a>
        </div>
    </div>
    
    <div class="card-box">
        <h5 class="card-title">企业信息</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label text-muted">用户名</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($card['username']); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">企业名称</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($card['company_name']); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">联系电话</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($card['contact_phone']); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">申请时间</label>
                <div class="form-control-plaintext"><?php echo formatDate($card['created_at']); ?></div>
            </div>
        </div>
        
        <?php if ($card['status'] === 'pending'): ?>
        <hr class="my-4">
        <div class="d-flex gap-2">
            <a href="cards_audit_enterprise.php?action=approve&id=<?php echo $cardId; ?>" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>通过申请</a>
            <a href="cards_audit_enterprise.php?action=reject&id=<?php echo $cardId; ?>" class="btn btn-danger"><i class="bi bi-x-lg me-1"></i>拒绝申请</a>
        </div>
        <?php endif; ?>
    </div>
    <?php
    require_once 'footer.php';
    exit;
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$pageSize = 20;

$countSql = "SELECT COUNT(*) FROM cards WHERE status = 'pending' AND user_type = 'enterprise'";
$total = dbGetOne($countSql);
$totalPages = ceil($total / $pageSize);
$offset = ($page - 1) * $pageSize;

$sql = "SELECT c.*, e.username, e.company_name FROM cards c LEFT JOIN enterprises e ON c.user_id = e.id WHERE c.status = 'pending' AND c.user_type = 'enterprise' ORDER BY c.created_at DESC LIMIT $pageSize OFFSET $offset";
$cards = dbGetAll($sql);

require_once 'header.php';
ob_start();
?>
<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-file-earmark-plus me-2"></i>企业开卡申请审核</h2>
            <p class="text-muted mb-0">审核企业用户的开卡申请</p>
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
                    <th>用户名</th>
                    <th>企业名称</th>
                    <th>申请时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cards)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        <i class="bi bi-check-circle fs-1 d-block mb-2"></i>
                        暂无待审核申请
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($cards as $card): ?>
                <tr>
                    <td><?php echo $card['id']; ?></td>
                    <td><?php echo htmlspecialchars($card['username']); ?></td>
                    <td><?php echo htmlspecialchars($card['company_name']); ?></td>
                    <td><?php echo formatDateShort($card['created_at']); ?></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="cards_audit_enterprise.php?action=view&id=<?php echo $card['id']; ?>" class="btn btn-outline-primary" title="查看"><i class="bi bi-eye"></i></a>
                            <a href="cards_audit_enterprise.php?action=approve&id=<?php echo $card['id']; ?>" class="btn btn-outline-success" title="通过"><i class="bi bi-check-lg"></i></a>
                            <a href="cards_audit_enterprise.php?action=reject&id=<?php echo $card['id']; ?>" class="btn btn-outline-danger" title="拒绝"><i class="bi bi-x-lg"></i></a>
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
