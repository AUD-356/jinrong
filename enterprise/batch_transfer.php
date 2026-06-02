<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['enterprise_id'])) {
    redirect('login.php');
}

$enterpriseId = $_SESSION['enterprise_id'];
$activeMenu = 'batch_transfer';

$cards = dbGetAll("SELECT * FROM cards WHERE user_id = ? AND user_type = 'enterprise' AND status = 'active' ORDER BY created_at DESC", [$enterpriseId]);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>多人转账 - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="sidebar">
        <?php $userName = $_SESSION['company_name']; include __DIR__ . '/../templates/sidebar_enterprise.php'; ?>
    </div>
    
    <div class="main-content">
        <div class="page-header">
            <h2><i class="bi bi-people me-2"></i>批量转账</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">首页</a></li>
                    <li class="breadcrumb-item active">批量转账</li>
                </ol>
            </nav>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card-box">
                    <div class="card-title">
                        <i class="bi bi-list-check me-2"></i>批量转账申请
                        <small class="text-muted ms-2">（最多10个收款方）</small>
                    </div>
                    
                    <?php if (empty($cards)): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        您还没有银行卡，无法进行转账操作
                    </div>
                    <a href="cards.php" class="btn btn-success">立即开户</a>
                    <?php else: ?>
                    <form id="batchTransferForm">
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
                            <label class="form-label">转账明细（最多添加10条）</label>
                            <table class="table table-sm" id="transferTable">
                                <thead>
                                    <tr>
                                        <th>收款方账号</th>
                                        <th>收款方银行</th>
                                        <th>收款方姓名</th>
                                        <th>金额</th>
                                        <th>备注</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody id="transferItems">
                                    <tr class="transfer-item">
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="to_card_no[]" placeholder="银行卡号" required>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm" name="to_bank[]" required>
                                                <option value="">选择银行</option>
                                                <option value="ICBC">工商银行</option>
                                                <option value="ABC">农业银行</option>
                                                <option value="BOC">中国银行</option>
                                                <option value="CCB">建设银行</option>
                                                <option value="COMM">交通银行</option>
                                                <option value="CMB">招商银行</option>
                                                <option value="CITIC">中信银行</option>
                                                <option value="CEB">光大银行</option>
                                                <option value="HXB">华夏银行</option>
                                                <option value="CMBC">民生银行</option>
                                                <option value="PSBC">邮储银行</option>
                                                <option value="CIB">兴业银行</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="to_name[]" placeholder="姓名" required>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="item_amount[]" placeholder="金额" data-money required>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="item_remark[]" placeholder="备注">
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="addRowBtn">
                                <i class="bi bi-plus me-1"></i>添加收款方
                            </button>
                        </div>
                        
                        <div class="alert alert-info">
                            <div class="d-flex justify-content-between">
                                <span>转账笔数：<strong id="totalCount">0</strong> 笔</span>
                                <span>总金额：<strong id="totalAmount" class="text-danger">¥0.00</strong></span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">支付密码</label>
                            <input type="password" class="form-control" name="pay_password" 
                                   placeholder="请输入6位支付密码" required maxlength="6">
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            批量转账申请将全部提交后台审核，审核通过后统一执行。
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-check-circle me-2"></i>提交批量转账申请
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card-box">
                    <div class="card-title">
                        <i class="bi bi-info-circle me-2"></i>操作说明
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            最多支持一次性向10个不同账户转账
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            所有转账均需后台审核
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            请确保转出账户余额充足
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            转账手续费按笔计算
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script>
        var rowCount = 1;
        var maxRows = 10;
        
        document.getElementById('addRowBtn').addEventListener('click', function() {
            if (rowCount >= maxRows) {
                alert('最多只能添加10个收款方');
                return;
            }
            
            var tbody = document.getElementById('transferItems');
            var newRow = tbody.insertRow();
            newRow.className = 'transfer-item';
            newRow.innerHTML = `
                <td><input type="text" class="form-control form-control-sm" name="to_card_no[]" placeholder="银行卡号" required></td>
                <td>
                    <select class="form-select form-select-sm" name="to_bank[]" required>
                        <option value="">选择银行</option>
                        <option value="ICBC">工商银行</option>
                        <option value="ABC">农业银行</option>
                        <option value="BOC">中国银行</option>
                        <option value="CCB">建设银行</option>
                        <option value="COMM">交通银行</option>
                        <option value="CMB">招商银行</option>
                        <option value="CITIC">中信银行</option>
                        <option value="CEB">光大银行</option>
                        <option value="HXB">华夏银行</option>
                        <option value="CMBC">民生银行</option>
                        <option value="PSBC">邮储银行</option>
                        <option value="CIB">兴业银行</option>
                    </select>
                </td>
                <td><input type="text" class="form-control form-control-sm" name="to_name[]" placeholder="姓名" required></td>
                <td><input type="text" class="form-control form-control-sm" name="item_amount[]" placeholder="金额" data-money required></td>
                <td><input type="text" class="form-control form-control-sm" name="item_remark[]" placeholder="备注"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="bi bi-trash"></i></button></td>
            `;
            rowCount++;
            updateTotals();
        });
        
        function removeRow(btn) {
            var rows = document.querySelectorAll('.transfer-item');
            if (rows.length > 1) {
                btn.closest('tr').remove();
                rowCount--;
                updateTotals();
            }
        }
        
        function updateTotals() {
            var amounts = document.querySelectorAll('input[name="item_amount[]"]');
            var count = 0;
            var total = 0;
            amounts.forEach(function(input) {
                if (input.value) {
                    count++;
                    total += parseFloat(input.value) || 0;
                }
            });
            document.getElementById('totalCount').textContent = count;
            document.getElementById('totalAmount').textContent = '¥' + total.toFixed(2);
        }
        
        document.getElementById('transferItems').addEventListener('input', function(e) {
            if (e.target.matches('input[name="item_amount[]"]')) {
                updateTotals();
            }
        });
        
        document.getElementById('batchTransferForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            updateTotals();
            var total = parseFloat(document.getElementById('totalAmount').textContent.replace('¥', '')) || 0;
            var select = document.querySelector('select[name="from_card_id"]');
            var option = select.options[select.selectedIndex];
            var balance = parseFloat(option.dataset.balance || 0);
            
            if (total > balance) {
                alert('余额不足，请确保账户余额充足');
                return;
            }
            
            var formData = new FormData(this);
            fetch('batch_transfer_do.php', {
                method: 'POST',
                body: formData
            }).then(function(response) {
                return response.json();
            }).then(function(data) {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            }).catch(function(error) {
                alert('操作失败，请重试');
            });
        });
    </script>
</body>
</html>
