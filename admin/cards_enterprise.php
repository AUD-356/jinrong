<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    redirect('login.php');
}

define('IN_ADMIN', true);
$pageTitle = '企业银行卡管理';

require_once 'header.php';

$action = $_GET['action'] ?? '';
$cardId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($action === 'freeze' && $cardId > 0) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $reason = sanitize($_POST['reason'] ?? '');
        
        if (empty($reason)) {
            $_SESSION['error'] = '请填写冻结原因';
            redirect('cards_enterprise.php?action=freeze&id=' . $cardId);
        }
        
        dbUpdate('cards', [
            'status' => 'frozen',
            'frozen_reason' => $reason,
            'frozen_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$cardId]);
        
        $logStmt = getDB()->prepare("INSERT INTO operation_logs (operator_id, operator_type, action, details, target_id, target_type, ip, created_at) VALUES (?, 'admin', 'freeze_card', ?, ?, 'card', ?, NOW())");
        $logStmt->execute([$_SESSION['admin_id'], '冻结企业银行卡: ' . $reason, $cardId, $_SERVER['REMOTE_ADDR'] ?? '']);
        
        $_SESSION['success'] = '卡片已冻结';
        redirect('cards_enterprise.php');
    }
    ?>
    <div class="page-header mb-4">
        <h2><i class="bi bi-snow me-2"></i>冻结银行卡</h2>
    </div>
    <div class="card-box">
        <form method="POST">
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                冻结后该卡片将无法进行任何交易操作。
            </div>
            <div class="mb-3">
                <label class="form-label">冻结原因类型</label>
                <select class="form-select mb-2" id="reasonType" onchange="toggleReason()">
                    <option value="">请选择原因类型</option>
                    <option value="企业申请">企业申请</option>
                    <option value="风险控制">风险控制</option>
                    <option value="违规操作">违规操作</option>
                    <option value="其他">其他</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">详细原因 <span class="text-danger">*</span></label>
                <textarea class="form-control" name="reason" id="reasonDetail" rows="3" placeholder="请输入详细冻结原因" required></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-danger"><i class="bi bi-snow me-1"></i>确认冻结</button>
                <a href="cards_enterprise.php" class="btn btn-outline-secondary">取消</a>
            </div>
        </form>
    </div>
    <script>
    function toggleReason() {
        var select = document.getElementById('reasonType');
        var textarea = document.getElementById('reasonDetail');
        if (select.value && select.value !== '其他') {
            textarea.value = select.value;
        }
    }
    </script>
    <?php
    require_once 'footer.php';
    exit;
}

if ($action === 'unfreeze' && $cardId > 0) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $reason = sanitize($_POST['reason'] ?? '');
        
        if (empty($reason)) {
            $_SESSION['error'] = '请填写解冻原因';
            redirect('cards_enterprise.php?action=unfreeze&id=' . $cardId);
        }
        
        dbUpdate('cards', [
            'status' => 'active',
            'frozen_reason' => ''
        ], 'id = ?', [$cardId]);
        
        $logStmt = getDB()->prepare("INSERT INTO operation_logs (operator_id, operator_type, action, details, target_id, target_type, ip, created_at) VALUES (?, 'admin', 'unfreeze_card', ?, ?, 'card', ?, NOW())");
        $logStmt->execute([$_SESSION['admin_id'], '解冻企业银行卡: ' . $reason, $cardId, $_SERVER['REMOTE_ADDR'] ?? '']);
        
        $_SESSION['success'] = '卡片已解冻';
        redirect('cards_enterprise.php');
    }
    ?>
    <div class="page-header mb-4">
        <h2><i class="bi bi-sun me-2"></i>解冻银行卡</h2>
    </div>
    <div class="card-box">
        <form method="POST">
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                解冻后该卡片将恢复正常使用。
            </div>
            <div class="mb-3">
                <label class="form-label">解冻原因类型</label>
                <select class="form-select mb-2" id="reasonType" onchange="toggleReason()">
                    <option value="">请选择原因类型</option>
                    <option value="企业申请解除">企业申请解除</option>
                    <option value="风险排查完成">风险排查完成</option>
                    <option value="其他">其他</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">详细原因 <span class="text-danger">*</span></label>
                <textarea class="form-control" name="reason" id="reasonDetail" rows="3" placeholder="请输入详细解冻原因" required></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success"><i class="bi bi-unlock me-1"></i>确认解冻</button>
                <a href="cards_enterprise.php" class="btn btn-outline-secondary">取消</a>
            </div>
        </form>
    </div>
    <script>
    function toggleReason() {
        var select = document.getElementById('reasonType');
        var textarea = document.getElementById('reasonDetail');
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
        $_SESSION['error'] = '卡片不存在';
        redirect('cards_enterprise.php');
    }
    ?>
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="bi bi-credit-card me-2"></i>卡片详情</h2>
            <a href="cards_enterprise.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>返回列表</a>
        </div>
    </div>
    
    <div class="card-box">
        <h5 class="card-title">卡片信息</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label text-muted">卡号</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($card['card_no']); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">所属银行</label>
                <div class="form-control-plaintext"><?php echo getBankName($card['bank_code']); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">余额</label>
                <div class="form-control-plaintext text-success fw-bold">￥<?php echo formatMoney($card['balance']); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">状态</label>
                <div class="form-control-plaintext">
                    <span class="badge <?php echo getCardStatusClass($card['status']); ?>"><?php echo getCardStatusText($card['status']); ?></span>
                </div>
            </div>
            <?php if ($card['frozen_reason']): ?>
            <div class="col-12">
                <label class="form-label text-muted">冻结原因</label>
                <div class="form-control-plaintext text-warning"><?php echo htmlspecialchars($card['frozen_reason']); ?></div>
            </div>
            <?php endif; ?>
        </div>
        
        <h5 class="card-title mt-4">所属企业</h5>
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
        </div>
        
        <?php if ($card['status'] === 'active'): ?>
        <hr class="my-4">
        <a href="cards_enterprise.php?action=freeze&id=<?php echo $cardId; ?>" class="btn btn-warning"><i class="bi bi-snow me-1"></i>冻结卡片</a>
        <?php elseif ($card['status'] === 'frozen'): ?>
        <hr class="my-4">
        <a href="cards_enterprise.php?action=unfreeze&id=<?php echo $cardId; ?>" class="btn btn-success"><i class="bi bi-unlock me-1"></i>解冻卡片</a>
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

$where = "c.user_type = 'enterprise'";
$params = [];
if ($search) {
    $where .= " AND (c.card_no LIKE ? OR e.username LIKE ? OR e.company_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($status) {
    $where .= " AND c.status = ?";
    $params[] = $status;
}

$countSql = "SELECT COUNT(*) FROM cards c LEFT JOIN enterprises e ON c.user_id = e.id WHERE $where";
$total = dbGetOne($countSql, $params);
$totalPages = ceil($total / $pageSize);
$offset = ($page - 1) * $pageSize;

$sql = "SELECT c.*, e.username, e.company_name FROM cards c LEFT JOIN enterprises e ON c.user_id = e.id WHERE $where ORDER BY c.created_at DESC LIMIT $pageSize OFFSET $offset";
$cards = dbGetAll($sql, $params);

ob_start();
?>
<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-credit-card me-2"></i>企业银行卡管理</h2>
            <p class="text-muted mb-0">管理所有企业用户银行卡</p>
        </div>
    </div>
</div>

<div class="card-box">
    <form method="GET" class="mb-4">
        <input type="hidden" name="page" value="1">
        <div class="row g-3">
            <div class="col-md-4">
                <input type="text" class="form-control" name="search" placeholder="搜索卡号/用户名/企业名称" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="status">
                    <option value="">全部状态</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>正常</option>
                    <option value="frozen" <?php echo $status === 'frozen' ? 'selected' : ''; ?>>已冻结</option>
                    <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>已销户</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>搜索</button>
                <a href="cards_enterprise.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i>重置</a>
            </div>
        </div>
    </form>
    
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>卡号</th>
                    <th>企业名称</th>
                    <th>所属银行</th>
                    <th>余额</th>
                    <th>状态</th>
                    <th>创建时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cards)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">暂无数据</td>
                </tr>
                <?php else: ?>
                <?php foreach ($cards as $card): ?>
                <tr>
                    <td><?php echo $card['id']; ?></td>
                    <td><?php echo htmlspecialchars($card['card_no']); ?></td>
                    <td><?php echo htmlspecialchars($card['company_name']); ?></td>
                    <td><?php echo getBankName($card['bank_code']); ?></td>
                    <td class="text-success fw-bold">￥<?php echo formatMoney($card['balance']); ?></td>
                    <td><span class="badge <?php echo getCardStatusClass($card['status']); ?>"><?php echo getCardStatusText($card['status']); ?></span></td>
                    <td><?php echo formatDateShort($card['created_at']); ?></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="cards_enterprise.php?action=view&id=<?php echo $card['id']; ?>" class="btn btn-outline-primary" title="查看"><i class="bi bi-eye"></i></a>
                            <?php if ($card['status'] === 'active'): ?>
                            <a href="cards_enterprise.php?action=freeze&id=<?php echo $card['id']; ?>" class="btn btn-outline-warning" title="冻结"><i class="bi bi-snow"></i></a>
                            <?php elseif ($card['status'] === 'frozen'): ?>
                            <a href="cards_enterprise.php?action=unfreeze&id=<?php echo $card['id']; ?>" class="btn btn-outline-success" title="解冻"><i class="bi bi-unlock"></i></a>
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
