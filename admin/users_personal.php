<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    redirect('login.php');
}

define('IN_ADMIN', true);
$pageTitle = '个人用户管理';

$action = $_GET['action'] ?? '';
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($action === 'edit' && $userId > 0) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $realName = sanitize($_POST['real_name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        
        dbUpdate('users', [
            'real_name' => $realName,
            'phone' => $phone,
            'email' => $email,
            'address' => $address
        ], 'id = ?', [$userId]);
        
        $logStmt = getDB()->prepare("INSERT INTO operation_logs (operator_id, operator_type, action, details, target_id, target_type, ip, created_at) VALUES (?, 'admin', 'edit_user', ?, ?, 'personal', ?, NOW())");
        $logStmt->execute([$_SESSION['admin_id'], '编辑个人用户信息', $userId, $_SERVER['REMOTE_ADDR'] ?? '']);
        
        $_SESSION['success'] = '用户信息已更新';
        redirect('users_personal.php');
    }
    
    $user = dbGetRow("SELECT * FROM users WHERE id = ?", [$userId]);
    if (!$user) {
        $_SESSION['error'] = '用户不存在';
        redirect('users_personal.php');
    }
    
    require_once 'header.php';
    ob_start();
    ?>
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="bi bi-person me-2"></i>编辑个人用户</h2>
                <p class="text-muted mb-0">修改用户信息</p>
            </div>
            <a href="users_personal.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>返回列表
            </a>
        </div>
    </div>
    
    <div class="card-box">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">用户名</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">真实姓名 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="real_name" value="<?php echo htmlspecialchars($user['real_name']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">手机号码 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                </div>
                <div class="col-md-6">
                        <label class="form-label">电子邮箱</label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">激活时间</label>
                        <input type="text" class="form-control" value="<?php echo $user['is_activated'] && $user['activated_at'] ? formatDate($user['activated_at']) : '未激活'; ?>" readonly>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">地址</label>
                        <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                    </div>
                <div class="col-md-6">
                    <label class="form-label">状态</label>
                    <input type="text" class="form-control" value="<?php echo getStatusText($user['status']); ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">注册时间</label>
                    <input type="text" class="form-control" value="<?php echo formatDate($user['created_at']); ?>" readonly>
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>保存修改
                </button>
                <a href="users_personal.php?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-danger">
                    <i class="bi bi-trash me-1"></i>删除用户
                </a>
                <a href="users_personal.php" class="btn btn-outline-secondary">取消</a>
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
        
        dbUpdate('users', [
            'status' => 'frozen',
            'freeze_reason' => $reason
        ], 'id = ?', [$userId]);
        
        $logStmt = getDB()->prepare("INSERT INTO operation_logs (operator_id, operator_type, action, details, target_id, target_type, ip, created_at) VALUES (?, 'admin', 'disable_user', ?, ?, 'personal', ?, NOW())");
        $logStmt->execute([$_SESSION['admin_id'], '冻结个人用户: ' . $reason, $userId, $_SERVER['REMOTE_ADDR'] ?? '']);
        
        $_SESSION['success'] = '用户已冻结';
        redirect('users_personal.php');
    }
    require_once 'header.php';
    ?>
    <div class="page-header mb-4">
        <h2><i class="bi bi-snow me-2"></i>冻结用户</h2>
    </div>
    <div class="card-box">
        <form method="POST">
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                确定要冻结此用户吗？冻结后用户将无法登录和操作。
            </div>
            <div class="mb-3">
                <label class="form-label">冻结原因 <span class="text-danger">*</span></label>
                <select class="form-select mb-2" id="freezeReasonSelect" onchange="toggleReason()">
                    <option value="">请选择原因类型</option>
                    <option value="违规操作">违规操作</option>
                    <option value="账户安全问题">账户安全问题</option>
                    <option value="用户申请">用户申请</option>
                    <option value="其他">其他</option>
                </select>
                <textarea class="form-control" name="reason" id="freezeReason" rows="3" placeholder="请输入详细原因" required></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-snow me-1"></i>确认冻结
                </button>
                <a href="users_personal.php" class="btn btn-outline-secondary">取消</a>
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
    dbUpdate('users', [
        'status' => 'active',
        'freeze_reason' => ''
    ], 'id = ?', [$userId]);
    
    $logStmt = getDB()->prepare("INSERT INTO operation_logs (operator_id, operator_type, action, details, target_id, target_type, ip, created_at) VALUES (?, 'admin', 'enable_user', '解冻个人用户', ?, 'personal', ?, NOW())");
    $logStmt->execute([$_SESSION['admin_id'], $userId, $_SERVER['REMOTE_ADDR'] ?? '']);
    
    $_SESSION['success'] = '用户已解冻';
    redirect('users_personal.php');
}

if ($action === 'reset_password' && $userId > 0) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if ($newPassword !== $confirmPassword) {
            $_SESSION['error'] = '两次密码输入不一致';
            redirect('users_personal.php?action=reset_password&id=' . $userId);
        }
        
        if (strlen($newPassword) < 6) {
            $_SESSION['error'] = '密码长度不能少于6位';
            redirect('users_personal.php?action=reset_password&id=' . $userId);
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        dbUpdate('users', ['password' => $hashedPassword], 'id = ?', [$userId]);
        
        $logStmt = getDB()->prepare("INSERT INTO operation_logs (operator_id, operator_type, action, details, target_id, target_type, ip, created_at) VALUES (?, 'admin', 'reset_password', '重置个人用户密码', ?, 'personal', ?, NOW())");
        $logStmt->execute([$_SESSION['admin_id'], $userId, $_SERVER['REMOTE_ADDR'] ?? '']);
        
        $_SESSION['success'] = '密码已重置';
        redirect('users_personal.php');
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
                将为用户设置新的登录密码，请告知用户。
            </div>
            <div class="mb-3">
                <label class="form-label">新密码 <span class="text-danger">*</span></label>
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
                <a href="users_personal.php" class="btn btn-outline-secondary">取消</a>
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
            
            $user = dbGetRow("SELECT username FROM users WHERE id = ?", [$userId]);
            
            dbDelete('applications', 'user_id = ? AND user_type = ?', [$userId, 'personal']);
            dbDelete('users', 'id = ?', [$userId]);
            
            $logStmt = $pdo->prepare("INSERT INTO operation_logs (operator_id, operator_type, action, details, target_id, target_type, ip, created_at) VALUES (?, 'admin', 'delete_user', ?, ?, 'personal', ?, NOW())");
            $logStmt->execute([$_SESSION['admin_id'], '删除个人用户: ' . ($user['username'] ?? ''), $userId, $_SERVER['REMOTE_ADDR'] ?? '']);
            
            $pdo->commit();
            $_SESSION['success'] = '用户已删除';
            redirect('users_personal.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = '删除失败: ' . $e->getMessage();
            redirect('users_personal.php?action=delete&id=' . $userId);
        }
    }
    $user = dbGetRow("SELECT * FROM users WHERE id = ?", [$userId]);
    if (!$user) {
        $_SESSION['error'] = '用户不存在';
        redirect('users_personal.php');
    }
    require_once 'header.php';
    ?>
    <div class="page-header mb-4">
        <h2><i class="bi bi-trash me-2"></i>删除用户</h2>
    </div>
    <div class="card-box">
        <form method="POST">
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>警告！</strong>此操作将永久删除该用户及其所有相关数据，此操作无法撤销！
            </div>
            <div class="mb-3">
                <label class="form-label">用户名</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">真实姓名</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['real_name']); ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">手机号码</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" readonly>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-trash me-1"></i>确认删除
                </button>
                <a href="users_personal.php" class="btn btn-outline-secondary">取消</a>
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
    $where .= " AND (username LIKE ? OR real_name LIKE ? OR phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($status) {
    $where .= " AND status = ?";
    $params[] = $status;
}

$countSql = "SELECT COUNT(*) FROM users WHERE $where";
$total = dbGetOne($countSql, $params);
$totalPages = ceil($total / $pageSize);
$offset = ($page - 1) * $pageSize;

$sql = "SELECT * FROM users WHERE $where ORDER BY created_at DESC LIMIT $pageSize OFFSET $offset";
$users = dbGetAll($sql, $params);

require_once 'header.php';
ob_start();
?>
<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-people me-2"></i>个人用户管理</h2>
            <p class="text-muted mb-0">管理所有个人用户账户</p>
        </div>
    </div>
</div>

<div class="card-box">
    <form method="GET" class="mb-4">
        <input type="hidden" name="page" value="1">
        <div class="row g-3">
            <div class="col-md-4">
                <input type="text" class="form-control" name="search" placeholder="搜索用户名/姓名/手机" value="<?php echo htmlspecialchars($search); ?>">
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
                <a href="users_personal.php" class="btn btn-outline-secondary">
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
                    <th>姓名</th>
                    <th>手机号码</th>
                    <th>身份证</th>
                    <th>状态</th>
                    <th>注册时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">暂无数据</td>
                </tr>
                <?php else: ?>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['real_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                    <td><?php echo maskIdCard($user['id_card'] ?? ''); ?></td>
                    <td><span class="badge <?php echo getStatusClass($user['status']); ?>"><?php echo getStatusText($user['status']); ?></span></td>
                    <td><?php echo formatDateShort($user['created_at']); ?></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="users_personal.php?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-outline-primary" title="编辑">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if ($user['status'] === 'active'): ?>
                            <a href="users_personal.php?action=disable&id=<?php echo $user['id']; ?>" class="btn btn-outline-warning" title="冻结">
                                <i class="bi bi-snow"></i>
                            </a>
                            <?php elseif ($user['status'] === 'frozen'): ?>
                            <a href="users_personal.php?action=enable&id=<?php echo $user['id']; ?>" class="btn btn-outline-success" title="解冻">
                                <i class="bi bi-sun"></i>
                            </a>
                            <?php endif; ?>
                            <a href="users_personal.php?action=reset_password&id=<?php echo $user['id']; ?>" class="btn btn-outline-info" title="重置密码">
                                <i class="bi bi-key"></i>
                            </a>
                            <a href="users_personal.php?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-outline-danger" title="删除">
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
