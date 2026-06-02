<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$userId = $_SESSION['user_id'];
$activeMenu = 'transfer';

$cards = dbGetAll("SELECT * FROM cards WHERE user_id = ? AND user_type = 'personal' AND status = 'active' ORDER BY created_at DESC", [$userId]);

$recentTransfers = dbGetAll("SELECT t.*, c1.card_no as from_card_no, c1.bank_code as from_bank 
    FROM transfers t 
    LEFT JOIN cards c1 ON t.from_card_id = c1.id 
    WHERE t.from_user_id = ? AND t.from_user_type = 'personal' 
    ORDER BY t.created_at DESC LIMIT 10", [$userId]);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>转账汇款 - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="sidebar">
        <?php $userName = $_SESSION['real_name']; include __DIR__ . '/../templates/sidebar_personal.php'; ?>
    </div>
    
    <div class="main-content">
        <div class="page-header">
            <h2><i class="bi bi-arrow-left-right me-2"></i>转账汇款</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">首页</a></li>
                    <li class="breadcrumb-item active">转账汇款</li>
                </ol>
            </nav>
        </div>
        
        <div class="row">
            <div class="col-md-5">
                <div class="card-box">
                    <div class="card-title">
                        <i class="bi bi-wallet2 me-2"></i>发起转账
                    </div>
                    
                    <?php if (empty($cards)): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        您还没有银行卡，无法进行转账操作
                    </div>
                    <a href="cards.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>立即开户
                    </a>
                    <?php else: ?>
                    <form action="transfer_do.php" method="POST" id="transferForm">
                        <div class="mb-3">
                            <label class="form-label">选择转出账户</label>
                            <select class="form-select" name="from_card_id" required>
                                <option value="">请选择转出账户</option>
                                <?php foreach ($cards as $card): ?>
                                <option value="<?php echo $card['id']; ?>" data-balance="<?php echo $card['balance']; ?>">
                                    <?php echo getBankName($card['bank_code']); ?> (<?php echo maskBankCard($card['card_no']); ?>) - 可用 <?php echo formatMoney($card['balance']); ?>元
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">收款方账户</label>
                            <input type="text" class="form-control" name="to_card_no" placeholder="请输入收款方银行卡号" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">收款方银行</label>
                            <select class="form-select" name="to_bank" required>
                                <option value="">请选择收款方银行</option>
                                <option value="ICBC">中国工商银行</option>
                                <option value="ABC">中国农业银行</option>
                                <option value="BOC">中国银行</option>
                                <option value="CCB">中国建设银行</option>
                                <option value="COMM">交通银行</option>
                                <option value="CMB">招商银行</option>
                                <option value="CITIC">中信银行</option>
                                <option value="CEB">中国光大银行</option>
                                <option value="HXB">华夏银行</option>
                                <option value="CMBC">民生银行</option>
                                <option value="GDB">广发银行</option>
                                <option value="PAB">平安银行</option>
                                <option value="PSBC">中国邮政储蓄银行</option>
                                <option value="SPDB">上海浦东发展银行</option>
                                <option value="CIB">兴业银行</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">转账金额</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="amount" id="amount" 
                                       placeholder="0.00" required data-money>
                                <span class="input-group-text">元</span>
                            </div>
                            <small class="text-muted">单笔最高500万元，超过<?php echo formatMoney(PERSONAL_TRANSFER_LIMIT); ?>元需人工审核</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">转账说明</label>
                            <input type="text" class="form-control" name="remark" placeholder="选填，最多50字" maxlength="50">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">支付密码</label>
                            <input type="password" class="form-control" name="pay_password" 
                                   placeholder="请输入6位支付密码" required maxlength="6">
                        </div>
                        
                        <div class="alert alert-info">
                            <small>
                                <i class="bi bi-info-circle me-1"></i>
                                转账金额超过<?php echo formatMoney(PERSONAL_TRANSFER_LIMIT); ?>元时，系统将自动提交后台审核，审核通过后资金将自动转出。
                            </small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-circle me-2"></i>确认转账
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-7">
                <div class="card-box">
                    <div class="card-title">
                        <i class="bi bi-clock-history me-2"></i>转账记录
                    </div>
                    
                    <?php if (empty($recentTransfers)): ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <h4>暂无转账记录</h4>
                        <p>您还没有发起过转账</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>回执单号</th>
                                    <th>收款方</th>
                                    <th>金额</th>
                                    <th>状态</th>
                                    <th>时间</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTransfers as $transfer): ?>
                                <tr>
                                    <td><small><?php echo $transfer['receipt_no']; ?></small></td>
                                    <td>
                                        <?php echo getBankName($transfer['to_bank']); ?><br>
                                        <small class="text-muted"><?php echo maskBankCard($transfer['to_card_no']); ?></small>
                                    </td>
                                    <td class="text-danger fw-bold">-<?php echo formatMoney($transfer['amount']); ?></td>
                                    <td><span class="badge <?php echo getTransferStatusClass($transfer['status']); ?>"><?php echo getTransferStatusText($transfer['status']); ?></span></td>
                                    <td><small><?php echo formatDateShort($transfer['created_at']); ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="bills.php?type=transfer" class="btn btn-outline-primary btn-sm">查看更多</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">确认转账</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>您即将向以下账户转账：</p>
                    <table class="table table-bordered">
                        <tr>
                            <th>收款方账户</th>
                            <td id="modalToCard"></td>
                        </tr>
                        <tr>
                            <th>收款方银行</th>
                            <td id="modalToBank"></td>
                        </tr>
                        <tr>
                            <th>转账金额</th>
                            <td class="text-danger fw-bold" id="modalAmount"></td>
                        </tr>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary" id="confirmBtn">确认转账</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script>
        document.getElementById('transferForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            var amount = parseFloat(document.getElementById('amount').value);
            if (isNaN(amount) || amount <= 0) {
                alert('请输入正确的转账金额');
                return;
            }
            
            var fromCard = this.querySelector('select[name="from_card_id"]');
            var option = fromCard.options[fromCard.selectedIndex];
            var balance = parseFloat(option.dataset.balance || 0);
            
            if (amount > balance) {
                alert('余额不足，请重新输入');
                return;
            }
            
            var formData = new FormData(this);
            
            $.ajax({
                url: 'transfer_do.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $('#confirmBtn').attr('disabled', true).text('处理中...');
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        window.location.reload();
                    } else {
                        alert(response.message);
                    }
                    $('#confirmBtn').attr('disabled', false).text('确认转账');
                },
                error: function() {
                    alert('网络错误，请稍后重试');
                    $('#confirmBtn').attr('disabled', false).text('确认转账');
                }
            });
        });
    </script>
</body>
</html>
