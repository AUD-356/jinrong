<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    redirect('login.php');
}

// 自动检查并修复 transfers 表的 status 字段
try {
    // 检查当前 status 字段的定义
    $checkSql = "SHOW COLUMNS FROM `transfers` LIKE 'status'";
    $column = dbGetRow($checkSql);
    
    if ($column && strpos($column['Type'], 'refunded') === false) {
        // 需要添加 refunded 状态
        $alterSql = "ALTER TABLE `transfers` MODIFY COLUMN `status` ENUM('pending', 'approved', 'rejected', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending'";
        dbExecute($alterSql);
    }
} catch (Exception $e) {
    // 静默处理，避免影响页面显示
}

define('IN_ADMIN', true);
$pageTitle = '转账记录管理';

require_once 'header.php';

$action = $_GET['action'] ?? '';
$transferId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($action === 'approve' && $transferId > 0) {
    $transfer = dbGetRow("SELECT t.*, fc.balance as from_balance FROM transfers t LEFT JOIN cards fc ON t.from_card_id = fc.id WHERE t.id = ?", [$transferId]);
    
    if (!$transfer) {
        $_SESSION['error'] = '转账记录不存在';
        redirect('transfers.php');
    }
    
    if ($transfer['status'] !== 'pending') {
        $_SESSION['error'] = '只有待审核的转账才能审核通过';
        redirect('transfers.php');
    }
    
    dbBeginTransaction();
    try {
        // 更新转出账户余额
        dbExecute("UPDATE cards SET balance = balance - ? WHERE id = ?", [$transfer['amount'], $transfer['from_card_id']]);
        
        // 获取更新后的转出账户余额
        $updatedFromCard = dbGetRow("SELECT balance FROM cards WHERE id = ?", [$transfer['from_card_id']]);
        
        // 插入转出账单
        $fromBank = dbGetOne("SELECT bank_code FROM cards WHERE id = ?", [$transfer['from_card_id']]);
        dbInsert('bills', [
            'user_id' => $transfer['from_user_id'],
            'user_type' => $transfer['from_user_type'],
            'type' => 'transfer_out',
            'amount' => -$transfer['amount'],
            'balance' => $updatedFromCard['balance'],
            'title' => '转账支出',
            'content' => '向' . getBankName($transfer['to_bank']) . '转账',
            'related_id' => $transfer['from_card_id'],
            'related_type' => 'card',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // 处理转入账户
        $toCard = null;
        if (!empty($transfer['to_card_id'])) {
            $toCard = dbGetRow("SELECT * FROM cards WHERE id = ? AND status = 'active'", [$transfer['to_card_id']]);
        }
        if (!$toCard && !empty($transfer['to_card_no'])) {
            $cleanToCardNo = normalizeCardNo($transfer['to_card_no']);
            $toCard = dbGetRow("SELECT * FROM cards WHERE REPLACE(REPLACE(REPLACE(card_no, ' ', ''), '-', ''), '　', '') = ? AND status = 'active' LIMIT 1", [$cleanToCardNo]);
            if ($toCard) {
                dbUpdate('transfers', ['to_card_id' => $toCard['id']], 'id = ?', [$transferId]);
            }
        }
        
        if ($toCard) {
            // 更新转入账户余额
            dbExecute("UPDATE cards SET balance = balance + ? WHERE id = ?", [$transfer['amount'], $toCard['id']]);
            
            // 获取更新后的转入账户余额
            $updatedToCard = dbGetRow("SELECT balance FROM cards WHERE id = ?", [$toCard['id']]);
            
            // 插入转入账单
            dbInsert('bills', [
                'user_id' => $toCard['user_id'],
                'user_type' => $toCard['user_type'],
                'type' => 'transfer_in',
                'amount' => $transfer['amount'],
                'balance' => $updatedToCard['balance'],
                'title' => '转账收入',
                'content' => '从' . getBankName($fromBank) . '转入',
                'related_id' => $toCard['id'],
                'related_type' => 'card',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        // 更新转账状态
        dbUpdate('transfers', [
            'status' => 'approved',
            'processed_by' => $_SESSION['admin_id'],
            'processed_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$transferId]);
        
        // 记录操作日志
        $logStmt = getDB()->prepare("INSERT INTO operation_logs (operator_id, operator_type, action, details, target_id, target_type, ip, created_at) VALUES (?, 'admin', 'approve_transfer', '审核通过转账申请', ?, 'transfer', ?, NOW())");
        $logStmt->execute([$_SESSION['admin_id'], $transferId, $_SERVER['REMOTE_ADDR'] ?? '']);
        
        dbCommit();
        
        $_SESSION['success'] = '转账申请已通过，金额已划转';
        redirect('transfers.php');
    } catch (Exception $e) {
        dbRollback();
        $_SESSION['error'] = '审核失败: ' . $e->getMessage();
        redirect('transfers.php');
    }
}

if ($action === 'reject' && $transferId > 0) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $reason = sanitize($_POST['reason'] ?? '');
        
        if (empty($reason)) {
            $_SESSION['error'] = '请填写拒绝原因';
            redirect('transfers.php?action=reject&id=' . $transferId);
        }
        
        dbUpdate('transfers', [
            'status' => 'rejected',
            'admin_remark' => $reason,
            'processed_by' => $_SESSION['admin_id'],
            'processed_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$transferId]);
        
        $logStmt = getDB()->prepare("INSERT INTO operation_logs (operator_id, operator_type, action, details, target_id, target_type, ip, created_at) VALUES (?, 'admin', 'reject_transfer', ?, ?, 'transfer', ?, NOW())");
        $logStmt->execute([$_SESSION['admin_id'], '拒绝转账申请: ' . $reason, $transferId, $_SERVER['REMOTE_ADDR'] ?? '']);
        
        $_SESSION['success'] = '已拒绝转账申请';
        redirect('transfers.php');
    }
    ?>
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="bi bi-x-circle text-danger me-2"></i>拒绝转账申请</h2>
            </div>
            <a href="transfers.php" class="btn btn-outline-secondary">
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
                <a href="transfers.php" class="btn btn-outline-secondary">取消</a>
            </div>
        </form>
    </div>
    <?php
    require_once 'footer.php';
    exit;
}

if ($action === 'refund' && $transferId > 0) {
    $transfer = dbGetRow("SELECT t.*, fc.card_no, fc.balance as from_balance FROM transfers t LEFT JOIN cards fc ON t.from_card_id = fc.id WHERE t.id = ?", [$transferId]);
    
    if (!$transfer) {
        $_SESSION['error'] = '转账记录不存在';
        redirect('transfers.php');
    }
    
    if (!in_array($transfer['status'], ['completed', 'approved'])) {
        $_SESSION['error'] = '只有已完成或已通过的转账才能退回';
        redirect('transfers.php');
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $refundType = sanitize($_POST['refund_type'] ?? '');
        $refundReason = sanitize($_POST['refund_reason'] ?? '');
        
        if (empty($refundType)) {
            $_SESSION['error'] = '请选择退回情况';
            redirect('transfers.php?action=refund&id=' . $transferId);
        }
        
        if (empty($refundReason)) {
            $_SESSION['error'] = '请填写退回理由';
            redirect('transfers.php?action=refund&id=' . $transferId);
        }
        
        dbBeginTransaction();
        try {
            $refundAmount = $transfer['amount'] - ($transfer['fee'] ?? 0);
            
            dbExecute("UPDATE cards SET balance = balance + ? WHERE id = ?", [$refundAmount, $transfer['from_card_id']]);
            $updatedFromCard = dbGetRow("SELECT balance FROM cards WHERE id = ?", [$transfer['from_card_id']]);
            
            $toCard = null;
            if (!empty($transfer['to_card_id'])) {
                $toCard = dbGetRow("SELECT * FROM cards WHERE id = ? AND status = 'active'", [$transfer['to_card_id']]);
            }
            if (!$toCard && !empty($transfer['to_card_no'])) {
                $cleanToCardNo = normalizeCardNo($transfer['to_card_no']);
                $toCard = dbGetRow("SELECT * FROM cards WHERE REPLACE(REPLACE(REPLACE(card_no, ' ', ''), '-', ''), '　', '') = ? AND status = 'active' LIMIT 1", [$cleanToCardNo]);
                if ($toCard) {
                    dbUpdate('transfers', ['to_card_id' => $toCard['id']], 'id = ?', [$transferId]);
                }
            }
            
            if ($toCard) {
                dbExecute("UPDATE cards SET balance = balance - ? WHERE id = ?", [$transfer['amount'], $toCard['id']]);
                $updatedToCard = dbGetRow("SELECT balance FROM cards WHERE id = ?", [$toCard['id']]);
                dbInsert('bills', [
                    'user_id' => $toCard['user_id'],
                    'user_type' => $toCard['user_type'],
                    'type' => 'transfer_out',
                    'amount' => -$transfer['amount'],
                    'balance' => $updatedToCard['balance'],
                    'title' => '转账退回',
                    'content' => '退回转账，原单号: ' . $transfer['receipt_no'],
                    'related_id' => $toCard['id'],
                    'related_type' => 'card',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            dbUpdate('transfers', [
                'status' => 'refunded',
                'admin_remark' => '退回类型: ' . $refundType . '，退回理由: ' . $refundReason,
                'processed_by' => $_SESSION['admin_id'],
                'processed_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$transferId]);
            
            dbInsert('bills', [
                'user_id' => $transfer['from_user_id'],
                'user_type' => $transfer['from_user_type'],
                'type' => 'transfer_in',
                'amount' => $refundAmount,
                'balance' => $updatedFromCard['balance'],
                'title' => '转账退回',
                'content' => '转账退回，原单号: ' . $transfer['receipt_no'],
                'related_id' => $transfer['from_card_id'],
                'related_type' => 'card',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $logStmt = getDB()->prepare("INSERT INTO operation_logs (operator_id, operator_type, action, details, target_id, target_type, ip, created_at) VALUES (?, 'admin', 'refund_transfer', ?, ?, 'transfer', ?, NOW())");
            $logStmt->execute([$_SESSION['admin_id'], '转账退回: ' . $refundType . ' - ' . $refundReason, $transferId, $_SERVER['REMOTE_ADDR'] ?? '']);
            
            dbCommit();
            
            $_SESSION['success'] = '转账已成功退回，金额已返还到转出账户';
            redirect('transfers.php');
        } catch (Exception $e) {
            dbRollback();
            $_SESSION['error'] = '退回失败: ' . $e->getMessage();
            redirect('transfers.php?action=refund&id=' . $transferId);
        }
    }
    
    $refundTypes = [
        'user_request' => '用户申请退回',
        'wrong_account' => '收款方信息错误',
        'system_error' => '系统处理错误',
        'fraud_suspect' => '涉嫌欺诈',
        'other' => '其他原因'
    ];
    ?>
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="bi bi-arrow-return-left text-warning me-2"></i>转账退回</h2>
            </div>
            <a href="transfers.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>返回列表
            </a>
        </div>
    </div>
    
    <div class="card-box">
        <div class="mb-4 p-3 bg-warning bg-opacity-10 rounded">
            <strong>注意：</strong>执行退回操作后，转账金额（扣除手续费）将返还到转出账户。
        </div>
        
        <div class="mb-4">
            <h5 class="card-title">转账信息</h5>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label text-muted">转账单号</label>
                    <div class="form-control-plaintext"><?php echo htmlspecialchars($transfer['receipt_no']); ?></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted">转账金额</label>
                    <div class="form-control-plaintext text-danger fw-bold">￥<?php echo formatMoney($transfer['amount']); ?></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted">预计退回金额</label>
                    <div class="form-control-plaintext text-success fw-bold">￥<?php echo formatMoney($transfer['amount'] - ($transfer['fee'] ?? 0)); ?></div>
                </div>
            </div>
        </div>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">退回情况 <span class="text-danger">*</span></label>
                <select class="form-select" name="refund_type" required>
                    <option value="">请选择退回情况</option>
                    <?php foreach ($refundTypes as $key => $label): ?>
                    <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">退回理由 <span class="text-danger">*</span></label>
                <textarea class="form-control" name="refund_reason" rows="3" placeholder="请详细说明退回理由..." required></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-warning"><i class="bi bi-arrow-return-left me-1"></i>确认退回</button>
                <a href="transfers.php" class="btn btn-outline-secondary">取消</a>
            </div>
        </form>
    </div>
    <?php
    require_once 'footer.php';
    exit;
}

if ($action === 'view' && $transferId > 0) {
    $transfer = dbGetRow("SELECT t.*, 
        CASE WHEN t.from_user_type = 'personal' THEN u.username ELSE e.username END as from_username,
        CASE WHEN t.from_user_type = 'personal' THEN u.real_name ELSE e.company_name END as from_name,
        fc.card_no as from_card_no
        FROM transfers t
        LEFT JOIN users u ON t.from_user_id = u.id AND t.from_user_type = 'personal'
        LEFT JOIN enterprises e ON t.from_user_id = e.id AND t.from_user_type = 'enterprise'
        LEFT JOIN cards fc ON t.from_card_id = fc.id
        WHERE t.id = ?", [$transferId]);
    
    if (!$transfer) {
        $_SESSION['error'] = '转账记录不存在';
        redirect('transfers.php');
    }
    ?>
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="bi bi-arrow-left-right me-2"></i>转账详情</h2>
            <a href="transfers.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>返回列表</a>
        </div>
    </div>
    
    <div class="card-box">
        <h5 class="card-title">转账信息</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label text-muted">转账单号</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($transfer['receipt_no']); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">转账金额</label>
                <div class="form-control-plaintext text-success fw-bold fs-5">￥<?php echo formatMoney($transfer['amount']); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">手续费</label>
                <div class="form-control-plaintext">￥<?php echo formatMoney($transfer['fee']); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">目标银行</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($transfer['to_bank'] ?: '-'); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">目标卡号</label>
                <div class="form-control-plaintext"><?php echo maskBankCard($transfer['to_card_no'] ?: ''); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">转出卡号</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($transfer['from_card_no'] ?: '-'); ?></div>
            </div>
            <div class="col-12">
                <label class="form-label text-muted">备注</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($transfer['remark'] ?: '-'); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">状态</label>
                <div class="form-control-plaintext">
                    <span class="badge <?php echo getTransferStatusClass($transfer['status']); ?>"><?php echo getTransferStatusText($transfer['status']); ?></span>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">申请时间</label>
                <div class="form-control-plaintext"><?php echo formatDate($transfer['created_at']); ?></div>
            </div>
            <?php if ($transfer['admin_remark']): ?>
            <div class="col-12">
                <label class="form-label text-muted">审核备注</label>
                <div class="form-control-plaintext text-warning"><?php echo htmlspecialchars($transfer['admin_remark']); ?></div>
            </div>
            <?php endif; ?>
        </div>
        
        <h5 class="card-title mt-4">转出人信息</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label text-muted">用户名</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($transfer['from_username']); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">姓名/企业</label>
                <div class="form-control-plaintext"><?php echo htmlspecialchars($transfer['from_name']); ?></div>
            </div>
        </div>
        
        <?php if ($transfer['status'] === 'pending'): ?>
        <hr class="my-4">
        <div class="d-flex gap-2">
            <a href="transfers.php?action=approve&id=<?php echo $transferId; ?>" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>通过申请</a>
            <a href="transfers.php?action=reject&id=<?php echo $transferId; ?>" class="btn btn-danger"><i class="bi bi-x-lg me-1"></i>拒绝申请</a>
        </div>
        <?php endif; ?>
        
        <?php if (in_array($transfer['status'], ['completed', 'approved'])): ?>
        <hr class="my-4">
        <div class="d-flex gap-2">
            <a href="transfers.php?action=refund&id=<?php echo $transferId; ?>" class="btn btn-warning"><i class="bi bi-arrow-return-left me-1"></i>转账退回</a>
        </div>
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
    $where .= " AND (t.receipt_no LIKE ? OR t.to_card_no LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($status) {
    $where .= " AND t.status = ?";
    $params[] = $status;
}

$countSql = "SELECT COUNT(*) FROM transfers t WHERE $where";
$total = dbGetOne($countSql, $params);
$totalPages = ceil($total / $pageSize);
$offset = ($page - 1) * $pageSize;

$sql = "SELECT t.*, 
    CASE WHEN t.from_user_type = 'personal' THEN u.real_name ELSE e.company_name END as from_name
    FROM transfers t
    LEFT JOIN users u ON t.from_user_id = u.id AND t.from_user_type = 'personal'
    LEFT JOIN enterprises e ON t.from_user_id = e.id AND t.from_user_type = 'enterprise'
    WHERE $where ORDER BY t.created_at DESC LIMIT $pageSize OFFSET $offset";
$transfers = dbGetAll($sql, $params);

ob_start();
?>
<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-arrow-left-right me-2"></i>转账记录管理</h2>
            <p class="text-muted mb-0">查看和处理所有转账申请</p>
        </div>
    </div>
</div>

<div class="card-box">
    <form method="GET" class="mb-4">
        <input type="hidden" name="page" value="1">
        <div class="row g-3">
            <div class="col-md-4">
                <input type="text" class="form-control" name="search" placeholder="搜索单号/目标卡号" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="status">
                    <option value="">全部状态</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>待审核</option>
                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>已通过</option>
                    <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>已拒绝</option>
                    <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>处理中</option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>已完成</option>
                    <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>失败</option>
                    <option value="refunded" <?php echo $status === 'refunded' ? 'selected' : ''; ?>>已退回</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>搜索</button>
                <a href="transfers.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i>重置</a>
            </div>
        </div>
    </form>
    
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>单号</th>
                    <th>转出人</th>
                    <th>目标卡号</th>
                    <th>金额</th>
                    <th>手续费</th>
                    <th>状态</th>
                    <th>时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transfers)): ?>
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">暂无数据</td>
                </tr>
                <?php else: ?>
                <?php foreach ($transfers as $tr): ?>
                <tr>
                    <td><?php echo $tr['id']; ?></td>
                    <td><small><?php echo htmlspecialchars($tr['receipt_no']); ?></small></td>
                    <td><?php echo htmlspecialchars($tr['from_name']); ?></td>
                    <td><small><?php echo maskBankCard($tr['to_card_no'] ?: ''); ?></small></td>
                    <td class="text-danger fw-bold">-￥<?php echo formatMoney($tr['amount']); ?></td>
                    <td>￥<?php echo formatMoney($tr['fee']); ?></td>
                    <td><span class="badge <?php echo getTransferStatusClass($tr['status']); ?>"><?php echo getTransferStatusText($tr['status']); ?></span></td>
                    <td><?php echo formatDateShort($tr['created_at']); ?></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="transfers.php?action=view&id=<?php echo $tr['id']; ?>" class="btn btn-outline-primary" title="查看"><i class="bi bi-eye"></i></a>
                            <?php if ($tr['status'] === 'pending'): ?>
                            <a href="transfers.php?action=approve&id=<?php echo $tr['id']; ?>" class="btn btn-outline-success" title="通过"><i class="bi bi-check-lg"></i></a>
                            <a href="transfers.php?action=reject&id=<?php echo $tr['id']; ?>" class="btn btn-outline-danger" title="拒绝"><i class="bi bi-x-lg"></i></a>
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
