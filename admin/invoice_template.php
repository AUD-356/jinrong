<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    redirect('login.php');
}

define('IN_ADMIN', true);
$pageTitle = '发票模板';

$action = $_POST['action'] ?? '';
$invoiceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($invoiceId <= 0) {
    $_SESSION['error'] = '发票ID不存在';
    redirect('invoices.php');
}

$invoice = dbGetRow("SELECT i.*, 
        CASE WHEN i.user_type = 'personal' THEN u.username ELSE e.username END as username,
        CASE WHEN i.user_type = 'personal' THEN u.real_name ELSE e.company_name END as user_name,
        CASE WHEN i.user_type = 'personal' THEN u.phone ELSE e.contact_phone END as user_phone,
        CASE WHEN i.user_type = 'personal' THEN u.address ELSE e.address END as user_address
        FROM invoices i 
        LEFT JOIN users u ON i.user_id = u.id AND i.user_type = 'personal'
        LEFT JOIN enterprises e ON i.user_id = e.id AND i.user_type = 'enterprise'
        WHERE i.id = ?", [$invoiceId]);

if (!$invoice) {
    $_SESSION['error'] = '发票记录不存在';
    redirect('invoices.php');
}

if ($action === 'send') {
    if ($invoice['status'] !== 'approved') {
        $_SESSION['error'] = '只有已通过的发票才能标记为已发送';
        redirect('invoice_template.php?id=' . $invoiceId);
    }

    dbUpdate('invoices', [
        'status' => 'sent',
        'processed_by' => $_SESSION['admin_id'],
        'processed_at' => date('Y-m-d H:i:s')
    ], 'id = ?', [$invoiceId]);

    $logStmt = getDB()->prepare("INSERT INTO operation_logs (operator_id, operator_type, action, details, target_id, target_type, ip, created_at) VALUES (?, 'admin', 'send_invoice', ?, ?, 'invoice', ?, NOW())");
    $logStmt->execute([$_SESSION['admin_id'], '标记发票为已发送', $invoiceId, $_SERVER['REMOTE_ADDR'] ?? '']);

    $_SESSION['success'] = '发票已标记为已发送';
    redirect('invoice_template.php?id=' . $invoiceId);
}

require_once 'header.php';
?>
<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-receipt me-2"></i>发票模板</h2>
            <p class="text-muted mb-0">查看并开具已通过的发票</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" onclick="window.print();"><i class="bi bi-printer me-1"></i>打印发票</button>
            <?php if ($invoice['status'] === 'approved'): ?>
            <form method="POST" class="d-inline">
                <input type="hidden" name="action" value="send">
                <button type="submit" class="btn btn-success"><i class="bi bi-send me-1"></i>标记已发送</button>
            </form>
            <?php endif; ?>
            <a href="invoices.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>返回列表</a>
        </div>
    </div>
</div>

<div class="card-box invoice-template bg-white p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">发票</h3>
            <div class="text-muted">发票编号：<?php echo $invoice['invoice_no'] ? htmlspecialchars($invoice['invoice_no']) : '待生成'; ?></div>
            <div class="text-muted">开票日期：<?php echo formatDateShort($invoice['processed_at'] ?? $invoice['created_at']); ?></div>
        </div>
        <div class="text-end">
            <span class="badge <?php echo getInvoiceStatusClass($invoice['status']); ?> fs-6"><?php echo getInvoiceStatusText($invoice['status']); ?></span>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="mb-2 text-muted">开票抬头</div>
            <div class="h5 fw-bold"><?php echo htmlspecialchars($invoice['title']); ?></div>
        </div>
        <div class="col-md-6">
            <div class="mb-2 text-muted">发票内容</div>
            <div class="h5 fw-bold"><?php echo htmlspecialchars($invoice['content']); ?></div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="text-muted">申请人</div>
            <div><?php echo htmlspecialchars($invoice['user_name']); ?></div>
        </div>
        <div class="col-md-4">
            <div class="text-muted">联系邮箱</div>
            <div><?php echo htmlspecialchars($invoice['email']); ?></div>
        </div>
        <div class="col-md-4">
            <div class="text-muted">联系电话</div>
            <div><?php echo htmlspecialchars($invoice['phone']); ?></div>
        </div>
    </div>

    <?php if ($invoice['tax_id']): ?>
    <div class="mb-4">
        <div class="text-muted">税号</div>
        <div><?php echo htmlspecialchars($invoice['tax_id']); ?></div>
    </div>
    <?php endif; ?>

    <div class="invoice-amount-box p-4 mb-4 border rounded">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="text-muted">开票金额</div>
                <div class="display-6 fw-bold text-success">￥<?php echo formatMoney($invoice['amount']); ?></div>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="text-muted">大写</div>
                <div class="fw-bold text-uppercase"><?php echo htmlspecialchars(formatMoneyCN($invoice['amount'])); ?></div>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <div class="text-muted">收票地址</div>
        <div><?php echo htmlspecialchars($invoice['address'] ?? $invoice['user_address'] ?? ''); ?></div>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="text-muted">开票日期</div>
            <div><?php echo formatDateShort($invoice['created_at']); ?></div>
        </div>
        <div class="col-md-4">
            <div class="text-muted">业务类型</div>
            <div><?php echo htmlspecialchars($invoice['content']); ?></div>
        </div>
        <div class="col-md-4">
            <div class="text-muted">服务单位</div>
            <div><?php echo htmlspecialchars(SITE_NAME); ?></div>
        </div>
    </div>
</div>

<style>
.invoice-template {
    page-break-inside: avoid;
}
@media print {
    body * { visibility: hidden; }
    .invoice-template, .invoice-template * { visibility: visible; }
    .invoice-template { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>

<?php
require_once 'footer.php';
