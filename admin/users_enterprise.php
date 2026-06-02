<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    redirect('login.php');
}

define('IN_ADMIN', true);
$pageTitle = '企业用户管理';

$action = $_GET['action'] ?? '';
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($action === 'edit' && $userId > 0) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $companyName = sanitize($_POST['company_name'] ?? '');
        $contactPerson = sanitize($_POST['contact_person'] ?? '');
        $contactPhone = sanitize($_POST['contact_phone'] ?? '');
        $contactEmail = sanitize($_POST['contact_email'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        
        dbUpdate('enterprises', [
            'company_name' => $companyName,
            'contact_person' => $contactPerson,
            'contact_phone' => $contactPhone,
            'contact_email' => $contactEmail,
            'address' => $address
        ], 'id = ?', [$userId]);
        
        $logStmt = getDB()->prepare("INSERT INTO operation_logs (operator_id, operator_type, action, details, target_id, target_type, ip, created_at) VALUES (?, 'admin', 'edit_enterprise', ?, ?, 'enterprise', ?, NOW())");
        $logStmt->execute([$_SESSION['admin_id'], '编辑企业用户信息', $userId, $_SERVER['REMOTE_ADDR'] ?? '']);
        
        $_SESSION['success'] = '企业信息已更新';
        redirect('users_enterprise.php');
    }
    
    $enterprise = dbGetRow("SELECT * FROM enterprises WHERE id = ?", [$userId]);
    if (!$enterprise) {
        $_SESSION['error'] = '企业不存在';
        redirect('users_enterprise.php');
    }
    
    require_once 'header.php';
    ob_start();
    ?>
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="bi bi-building2 me-2"></i>编辑企业用户</h2>
                <p class="text-muted mb-0">修改企业信息</p>
            </div>
            <a href="users_enterprise.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>返回列表
            </a>
        </div>
    </div>
    
    <div class="card-box">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">用户名</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($enterprise['username']); ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">企业名称 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="company_name" value="<?php echo htmlspecialchars($enterprise['company_name']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">统一社会信用代码</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($enterprise['tax_id'] ?? ''); ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">法定代表人</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($enterprise['legal_person'] ?? ''); ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">联系人<span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="contact_person" value="<?php echo htmlspecialchars($enterprise['contact_person'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">联系电话 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="contact_phone" value="<?php echo htmlspecialchars($enterprise['contact_phone']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">电子邮箱</label>
                    <input type="email" class="form-control" name="contact_email" value="<?php echo htmlspecialchars($enterprise['contact_email'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">激活时间</label>
                    <input type="text" class="form-control" value="<?php echo $enterprise['is_activated'] && $enterprise['activated_at'] ? formatDate($enterprise['activated_at']) : '未激活'; ?>" readonly>
                </div>
                <div class="col-md-12">
                    <label class="form-label">企业地址</label>
                    <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($enterprise['address'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">状态</label>
                    <input type="text" class="form-control" value="<?php echo getStatusText($enterprise['status']); ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">注册时间</label>
                    <input type="text" class="form-control" value="<?php echo formatDate($enterprise['created_at']); ?>" readonly>
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>保存修改
                </button>
                <a href="users_enterprise.php?action=delete&id=<?php echo $enterprise['id']; ?>" class="btn btn-danger">
                    <i class="bi bi-trash me-1"></i>删除企业
                </a>
                <a href="users_enterprise.php" class="btn btn-outline-secondary">取消</a>
            </div>
        </form>
    </div>
    <?php
    $content = ob_get_clean();
    echo $content;
    require_once 'footer.php';
    exit;
}

if ($action === 'disable' && $userId > 0) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $reason = sanitize($_POST['reason'] ?? '');
        
        dbUpdate('enterprises', [
            'status' => 'frozen',
            'freeze_reason' => $reason
        ], 'id = ?', [$userId]);
        
        $logStmt = getDB()->prepare("INSERT INTO operation_logs (operator_id, operator_type, action, details, target_id, target_type, ip, created_at) VALUES (?, 'admin', 'disable_enterprise', ?, ?, 'enterprise', ?, NOW())");
        $logStmt->execute([$_SESSION['admin_id'], '冻结企业用户: ' . $reason, $userId, $_SERVER['REMOTE_ADDR'] ?? '']);
        
        $_SESSION['success'] = '企业已冻结';
        redirect('users_enterprise.php');
    }
    require_once 'header.php';
    ?>
    <div class="page-header mb-4">
        <h2><i class="bi bi-snow me-2"></i>冻结企业</h2>
    </div>
    <div class="card-box">
        <form method="POST">
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                确定要冻结此企业吗？冻结后企业将无法登录和操作</div>
            <div class="mb-3">
                <label class="form-label">冻结原因 <span class="text-danger">*</span></label>
                <select class="form-select mb-2" id="freezeReasonSelect" onchange="toggleReason()">
                    <option value="">请选择原因类型</option>
                    <option value="违规操作">违规操作</option>
                    <option value="账户安全问题">账户安全问题</option>
                    <option value="企业申请">企业申请</option>
                    <option value="其他">其他</option>
                </select>
                <textarea class="form-control" name="reason" id="freezeReason" rows="3" placeholder="请输入详细原因" required></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-snow me-1"></i>确认冻结
                </button>
                <a href="users_enterprise.php" class="btn btn-outline-secondary">取消</a>
            </div>
        </form>
    </div>
    <script>
    function toggleReason() {
        var select = document.getElementById('freezeReasonSelect');
        var textarea = document.getElementById('freezeReason');
        if (select.value && select.value !== '其他') {
            textarea.value = select.value;
        }
    }
    </script>
    <?php
    require_once 'footer.php';
    exit;
}

if ($action === 'enable' && $userId > 0) {
    dbUpdate('enterprises', [
        'status' => 'active',
        'freeze_reason' => ''
    ], 'id = ?', [$userId]);
    
    $logStmt = getDB()->prepare("INSERT INTO operation_logs (operator_id, operator_type, action, details, target_id, target_type, ip, created_at) VALUES (?, 'admin', 'enable_enterprise', '解冻企业用户', ?, 'enterprise', ?, NOW())");
    $logStmt->execute([$_SESSION['admin_id'], $userId, $_SERVER['REMOTE_ADDR'] ?? '']);
    
    $_SESSION['success'] = '企业已解冻';
    redirect('users_enterprise.php');
}

if ($action === 'reset_password' && $userId > 0) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if ($newPassword !== $confirmPassword) {
            $_SESSION['error'] = '两次密码输入不一致';
            redirect('users_enterprise.php?action=reset_password&id=' . $userId);
        }
        
        if (strlen($newPassword) < 6) {
            $_SESSION['error'] = '密码长度不能少于6位';
            redirect('users_enterprise.php?action=reset_password&id=' . $userId);
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        dbUpdate('enterprises', ['password' => $hashedPassword], 'id = ?', [$userId]);
        
        $logStmt = getDB()->prepare("INSERT INTO operation_logs (operator_id, operator_type, action, details, target_id, target_type, ip, created_at) VALUES (?, 'admin', 'reset_password', '重置企业用户密码', ?, 'enterprise', ?, NOW())");
        $logStmt->execute([$_SESSION['admin_id'], $userId, $_SERVER['REMOTE_ADDR'] ?? '']);
        
        $_SESSION['success'] = '密码已重置';
        redirect('users_enterprise.php');
    }
    require_once 'header.php';
    ?>
    <div class="page-header mb-4">
        <h2><i class="bi bi-key me-2"></i>重置密码</h2>
    </div>
    <div class="card-box">
        <form method="POST">
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                将为企业设置新的登录密码，请告知企业联系人</div>
            <div class="mb-3">
                <label class="form-label">新密码<span class="text-danger">*</span></label>
                <input type="password" class="form-control" name="new_password" minlength="6" required>
            </div>
            <div class="mb-3">
                <label class="form-label">确认密码 <span class="text-danger">*</span></label>
                <input type="password" class="form-control" name="confirm_password" minlength="6" required>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>确认重置
                </button>
                <a href="users_enterprise.php" class="btn btn-outline-secondary">取消</a>
            </div>
        </form>
    </div>
    <?php
    require_once 'footer.php';
    exit;
}

if ($action === 'delete' && $userId > 0) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $pdo = getDB();
            $pdo->beginTransaction();
            
            $enterprise = dbGetRow("SELECT username, company_name FROM enterprises WHERE id = ?", [$userId]);
            
            dbDelete('applications', 'user_id = ? AND user_type = ?', [$userId, 'enterprise']);
            dbDelete('enterprises', 'id = ?', [$userId]);
            
            $logStmt = $pdo->prepare("INSERT INTO operation_logs (operator_id, operator_type, action, details, target_id, target_type, ip, created_at) VALUES (?, 'admin', 'delete_enterprise', ?, ?, 'enterprise', ?, NOW())");
            $logStmt->execute([$_SESSION['admin_id'], '删除企业用户: ' . ($enterprise['company_name'] ?? ''), $userId, $_SERVER['REMOTE_ADDR'] ?? '']);
            
            $pdo->commit();
            $_SESSION['success'] = '企业已删除';
            redirect('users_enterprise.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = '删除失败: ' . $e->getMessage();
            redirect('users_enterprise.php?action=delete&id=' . $userId);
        }
    }
    $enterprise = dbGetRow("SELECT * FROM enterprises WHERE id = ?", [$userId]);
    if (!$enterprise) {
        $_SESSION['error'] = '企业不存在';
        redirect('users_enterprise.php');
    }
    require_once 'header.php';
    ?>
    <div class="page-header mb-4">
        <h2><i class="bi bi-trash me-2"></i>删除企业</h2>
    </div>
    <div class="card-box">
        <form method="POST">
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>警告！</strong>此操作将永久删除该企业及其所有相关数据，此操作无法撤销！
            </div>
            <div class="mb-3">
                <label class="form-label">用户名</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($enterprise['username']); ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">企业名称</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($enterprise['company_name']); ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">联系人</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($enterprise['contact_person'] ?? ''); ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">联系电话</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($enterprise['contact_phone']); ?>" readonly>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-trash me-1"></i>确认删除
                </button>
                <a href="users_enterprise.php" class="btn btn-outline-secondary">取消</a>
            </div>
        </form>
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
    $where .= " AND (username LIKE ? OR company_name LIKE ? OR contact_phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($status) {
    $where .= " AND status = ?";
    $params[] = $status;
}

$countSql = "SELECT COUNT(*) FROM enterprises WHERE $where";
$total = dbGetOne($countSql, $params);
$totalPages = ceil($total / $pageSize);
$offset = ($page - 1) * $pageSize;

$sql = "SELECT * FROM enterprises WHERE $where ORDER BY created_at DESC LIMIT $pageSize OFFSET $offset";
$enterprises = dbGetAll($sql, $params);

require_once 'header.php';
ob_start();
?>
<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-building2 me-2"></i>企业用户管理</h2>
            <p class="text-muted mb-0">管理所有企业用户账户</p>
        </div>
    </div>
</div>

<div class="card-box">
    <form method="GET" class="mb-4">
        <input type="hidden" name="page" value="1">
        <div class="row g-3">
            <div class="col-md-4">
                <input type="text" class="form-control" name="search" placeholder="搜索用户名/企业名称/联系电话" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="status">
                    <option value="">全部状态</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>待审核</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>正常</option>
                    <option value="frozen" <?php echo $status === 'frozen' ? 'selected' : ''; ?>>已冻结</option>
                    <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>已注销</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-1"></i>搜索
                </button>
                <a href="users_enterprise.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>重置
                </a>
            </div>
        </div>
    </form>
    
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>用户名</th>
                    <th>企业名称</th>
                    <th>联系人</th>
                    <th>联系电话</th>
                    <th>状态</th>
                    <th>注册时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($enterprises)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">暂无数据</td>
                </tr>
                <?php else: ?>
                <?php foreach ($enterprises as $enterprise): ?>
                <tr>
                    <td><?php echo $enterprise['id']; ?></td>
                    <td><?php echo htmlspecialchars($enterprise['username']); ?></td>
                    <td><?php echo htmlspecialchars($enterprise['company_name']); ?></td>
                    <td><?php echo htmlspecialchars($enterprise['contact_person'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($enterprise['contact_phone']); ?></td>
                    <td><span class="badge <?php echo getStatusClass($enterprise['status']); ?>"><?php echo getStatusText($enterprise['status']); ?></span></td>
                    <td><?php echo formatDateShort($enterprise['created_at']); ?></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="users_enterprise.php?action=edit&id=<?php echo $enterprise['id']; ?>" class="btn btn-outline-primary" title="编辑">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if ($enterprise['status'] === 'active'): ?>
                            <a href="users_enterprise.php?action=disable&id=<?php echo $enterprise['id']; ?>" class="btn btn-outline-warning" title="冻结">
                                <i class="bi bi-snow"></i>
                            </a>
                            <?php elseif ($enterprise['status'] === 'frozen'): ?>
                            <a href="users_enterprise.php?action=enable&id=<?php echo $enterprise['id']; ?>" class="btn btn-outline-success" title="解冻">
                                <i class="bi bi-sun"></i>
                            </a>
                            <?php endif; ?>
                            <a href="users_enterprise.php?action=reset_password&id=<?php echo $enterprise['id']; ?>" class="btn btn-outline-info" title="重置密码">
                                <i class="bi bi-key"></i>
                            </a>
                            <a href="users_enterprise.php?action=delete&id=<?php echo $enterprise['id']; ?>" class="btn btn-outline-danger" title="删除">
                                <i class="bi bi-trash"></i>
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
