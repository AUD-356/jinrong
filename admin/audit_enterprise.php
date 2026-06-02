<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    redirect('login.php');
}

define('IN_ADMIN', true);
$pageTitle = '企业用户注册审核';

$action = $_GET['action'] ?? '';
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($action === 'approve' && $userId > 0) {
    $enterprise = dbGetRow("SELECT * FROM enterprises WHERE id = ?", [$userId]);
    if (!$enterprise) {
        $_SESSION['error'] = '企业不存在';
        redirect('audit_enterprise.php');
    }
    if ($enterprise['status'] !== 'pending') {
        $_SESSION['error'] = '该企业当前无需审核';
        redirect('audit_enterprise.php');
    }

    // 生成激活码
    $activationCode = generateActivationCode();

    dbUpdate('enterprises', [
        'status' => 'active',
        'activation_code' => $activationCode,
        'is_activated' => 0
    ], 'id = ?', [$userId]);

    dbUpdate('applications', [
        'status' => 'approved'
    ], 'user_id = ? AND user_type = ? AND type = ?', [$userId, 'enterprise', 'register']);
    
    $logStmt = getDB()->prepare("INSERT INTO operation_logs (operator_id, operator_type, action, details, target_id, target_type, ip, created_at) VALUES (?, 'admin', 'approve_enterprise', '审核通过企业用户注册', ?, 'enterprise', ?, NOW())");
    $logStmt->execute([$_SESSION['admin_id'], $userId, $_SERVER['REMOTE_ADDR'] ?? '']);
    
    // 保存激活码到session，用于显示
    $_SESSION['show_activation_code'] = $activationCode;
    $_SESSION['show_activation_user'] = $enterprise['username'];
    $_SESSION['success'] = '已通过企业注册申请';
    redirect('audit_enterprise.php');
}

if ($action === 'reject' && $userId > 0) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $reason = sanitize($_POST['reason'] ?? '');
        
        if (empty($reason)) {
            $_SESSION['error'] = '请填写拒绝原因';
            redirect('audit_enterprise.php?action=reject&id=' . $userId);
        }
        
        $enterprise = dbGetRow("SELECT * FROM enterprises WHERE id = ?", [$userId]);
        if (!$enterprise) {
            $_SESSION['error'] = '企业不存在';
            redirect('audit_enterprise.php');
        }
        if ($enterprise['status'] !== 'pending') {
            $_SESSION['error'] = '该企业当前无法拒绝';
            redirect('audit_enterprise.php');
        }
        
        dbUpdate('enterprises', [
            'status' => 'closed',
            'freeze_reason' => $reason
        ], 'id = ?', [$userId]);
        
        dbUpdate('applications', [
            'status' => 'rejected'
        ], 'user_id = ? AND user_type = ? AND type = ?', [$userId, 'enterprise', 'register']);
        
        $logStmt = getDB()->prepare("INSERT INTO operation_logs (operator_id, operator_type, action, details, target_id, target_type, ip, created_at) VALUES (?, 'admin', 'reject_enterprise', ?, ?, 'enterprise', ?, NOW())");
        $logStmt->execute([$_SESSION['admin_id'], '拒绝企业用户注册: ' . $reason, $userId, $_SERVER['REMOTE_ADDR'] ?? '']);
        
        $_SESSION['success'] = '已拒绝企业注册申请';
        redirect('audit_enterprise.php');
    }
    
    $enterprise = dbGetRow("SELECT * FROM enterprises WHERE id = ?", [$userId]);
    if (!$enterprise) {
        $_SESSION['error'] = '企业不存在';
        redirect('audit_enterprise.php');
    }
    require_once 'header.php';
    ?>
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="bi bi-x-circle text-danger me-2"></i>拒绝注册申请</h2>
            </div>
            <a href="audit_enterprise.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>返回列表
            </a>
        </div>
    </div>
    
    <div class="card-box">
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>
            即将拒绝以下企业的注册申请：
            <strong><?php echo htmlspecialchars($enterprise['username']); ?></strong> (<?php echo htmlspecialchars($enterprise['company_name']); ?>)
        </div>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">拒绝原因 <span class="text-danger">*</span></label>
                <select class="form-select mb-2" id="rejectReasonSelect" onchange="toggleReason()">
                    <option value="">请选择原因类型</option>
                    <option value="资质材料不完整">资质材料不完整</option>
                    <option value="信息填写错误">信息填写错误</option>
                    <option value="营业执照不清晰">营业执照不清晰</option>
                    <option value="疑似虚假信息">疑似虚假信息</option>
                    <option value="其他">其他</option>
                </select>
                <textarea class="form-control" name="reason" id="rejectReason" rows="3" placeholder="请输入详细拒绝原因" required></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-x-lg me-1"></i>确认拒绝
                </button>
                <a href="audit_enterprise.php" class="btn btn-outline-secondary">取消</a>
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

if ($action === 'view' && $userId > 0) {
    $enterprise = dbGetRow("SELECT * FROM enterprises WHERE id = ?", [$userId]);
    if (!$enterprise) {
        $_SESSION['error'] = '企业不存在';
        redirect('audit_enterprise.php');
    }
    require_once 'header.php';
    ?>
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="bi bi-building2 me-2"></i>企业详情</h2>
            </div>
            <a href="audit_enterprise.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>返回列表
            </a>
        </div>
    </div>
    
    <div class="card-box">
        <h5 class="card-title">基本信息</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label text-muted">用户名</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($enterprise['username']); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">企业名称</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($enterprise['company_name']); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">统一社会信用代码</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($enterprise['tax_id'] ?? ''); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">法定代表人</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($enterprise['legal_person'] ?? ''); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">联系人</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($enterprise['contact_person'] ?? ''); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">联系电话</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($enterprise['contact_phone']); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">电子邮箱</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($enterprise['contact_email'] ?? ''); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">企业地址</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($enterprise['address'] ?? ''); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">注册时间</label>
                <div class="form-control-plaintext"><?php echo formatDate($enterprise['created_at']); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">状态</label>
                <div class="form-control-plaintext">
                    <span class="badge <?php echo getStatusClass($enterprise['status']); ?>"><?php echo getStatusText($enterprise['status']); ?></span>
                </div>
            </div>
        </div>
        
        <?php if ($enterprise['business_license']): ?>
        <h5 class="card-title mt-4">资质文件</h5>
        <div class="row">
            <div class="col-md-6">
                <label class="form-label text-muted">营业执照</label>
                <img src="<?php echo htmlspecialchars($enterprise['business_license']); ?>" class="img-thumbnail" style="max-height: 200px;">
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($enterprise['status'] === 'pending'): ?>
        <hr class="my-4">
        <div class="d-flex gap-2">
            <a href="audit_enterprise.php?action=approve&id=<?php echo $userId; ?>" class="btn btn-success">
                <i class="bi bi-check-lg me-1"></i>通过审核
            </a>
            <a href="audit_enterprise.php?action=reject&id=<?php echo $userId; ?>" class="btn btn-danger">
                <i class="bi bi-x-lg me-1"></i>拒绝申请
            </a>
        </div>
        <?php endif; ?>
    </div>
    <?php
    require_once 'footer.php';
    exit;
}

require_once 'header.php';

// 检查是否有激活码需要显示
$showActivationModal = isset($_SESSION['show_activation_code']);
$activationCode = $_SESSION['show_activation_code'] ?? '';
$activationUser = $_SESSION['show_activation_user'] ?? '';
// 清除session中的激活码信息，避免重复显示
unset($_SESSION['show_activation_code']);
unset($_SESSION['show_activation_user']);

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$pageSize = 20;

$countSql = "SELECT COUNT(*) FROM enterprises WHERE status = 'pending'";
$total = dbGetOne($countSql);
$totalPages = ceil($total / $pageSize);
$offset = ($page - 1) * $pageSize;

$sql = "SELECT * FROM enterprises WHERE status = 'pending' ORDER BY created_at DESC LIMIT $pageSize OFFSET $offset";
$enterprises = dbGetAll($sql);

ob_start();
?>
<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-building2-check me-2"></i>企业用户注册审核</h2>
            <p class="text-muted mb-0">审核企业用户的注册申请</p>
        </div>
        <span class="badge bg-warning text-dark fs-6">
            <i class="bi bi-clock me-1"></i>待审核: <?php echo $total; ?> 人</span>
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
                    <th>联系人</th>
                    <th>联系电话</th>
                    <th>注册时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($enterprises)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-check-circle fs-1 d-block mb-2"></i>
                        暂无待审核企业</td>
                </tr>
                <?php else: ?>
                <?php foreach ($enterprises as $enterprise): ?>
                <tr>
                    <td><?php echo $enterprise['id']; ?></td>
                    <td><?php echo htmlspecialchars($enterprise['username']); ?></td>
                    <td><?php echo htmlspecialchars($enterprise['company_name']); ?></td>
                    <td><?php echo htmlspecialchars($enterprise['contact_person'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($enterprise['contact_phone']); ?></td>
                    <td><?php echo formatDateShort($enterprise['created_at']); ?></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="audit_enterprise.php?action=view&id=<?php echo $enterprise['id']; ?>" class="btn btn-outline-primary" title="查看详情">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="audit_enterprise.php?action=approve&id=<?php echo $enterprise['id']; ?>" class="btn btn-outline-success" title="通过">
                                <i class="bi bi-check-lg"></i>
                            </a>
                            <a href="audit_enterprise.php?action=reject&id=<?php echo $enterprise['id']; ?>" class="btn btn-outline-danger" title="拒绝">
                                <i class="bi bi-x-lg"></i>
                            </a>
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

<!-- 激活码弹窗 -->
<?php if ($showActivationModal): ?>
<div class="modal fade show" id="activationCodeModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-key text-success me-2"></i>企业激活码已生成
                </h5>
                <button type="button" class="btn-close" onclick="closeModal()"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    企业 <strong><?php echo htmlspecialchars($activationUser); ?></strong> 的注册申请已通过审核。
                </div>
                <div class="mb-3">
                    <label class="form-label">激活码</label>
                    <div class="input-group">
                        <input type="text" class="form-control font-monospace" id="activationCodeInput" 
                               value="<?php echo htmlspecialchars($activationCode); ?>" readonly>
                        <button class="btn btn-outline-primary" type="button" onclick="copyActivationCode()">
                            <i class="bi bi-clipboard"></i> 复制
                        </button>
                    </div>
                    <div class="form-text text-muted">请将此激活码提供给企业用户，用户首次登录时需要输入</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="closeModal()">
                    <i class="bi bi-check-lg"></i> 确定
                </button>
            </div>
        </div>
    </div>
</div>
<script>
function closeModal() {
    document.getElementById('activationCodeModal').style.display = 'none';
}
function copyActivationCode() {
    const input = document.getElementById('activationCodeInput');
    input.select();
    document.execCommand('copy');
    alert('激活码已复制到剪贴板！');
}
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
echo $content;
require_once 'footer.php';
