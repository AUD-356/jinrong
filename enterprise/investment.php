<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['enterprise_id'])) {
    redirect('login.php');
}

$enterpriseId = $_SESSION['enterprise_id'];
$activeMenu = 'investment';

$products = dbGetAll("SELECT * FROM investments WHERE status = 'active' ORDER BY id");

$investments = dbGetAll("SELECT i.*, p.name as product_name, p.expected_rate, p.type as product_type 
    FROM user_investments i 
    LEFT JOIN investments p ON i.product_id = p.id 
    WHERE i.user_id = ? AND i.user_type = 'enterprise' AND i.status = 'invested' 
    ORDER BY i.created_at DESC", [$enterpriseId]);

$totalInvested = 0;
$totalExpected = 0;
foreach ($investments as $inv) {
    $totalInvested += $inv['amount'];
    $totalExpected += $inv['expected_profit'];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>企业投资理财 - <?php echo SITE_NAME; ?></title>
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
            <h2><i class="bi bi-piggy-bank me-2"></i>企业投资理财</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">首页</a></li>
                    <li class="breadcrumb-item active">投资理财</li>
                </ol>
            </nav>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-label">我的投资</div>
                    <div class="stat-value"><?php echo formatMoney($totalInvested); ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card success">
                    <div class="stat-label">预期收益</div>
                    <div class="stat-value text-success"><?php echo formatMoney($totalExpected); ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card warning">
                    <div class="stat-label">持仓产品数</div>
                    <div class="stat-value"><?php echo count($investments); ?></div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card-box">
                    <div class="card-title">
                        <i class="bi bi-list-ul me-2"></i>我的理财产品
                    </div>
                    
                    <?php if (empty($investments)): ?>
                    <div class="empty-state">
                        <i class="bi bi-piggy-bank"></i>
                        <h4>暂无投资</h4>
                        <p>您还没有购买任何理财产品</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>产品名称</th>
                                    <th>投资金额</th>
                                    <th>预期年化</th>
                                    <th>期限</th>
                                    <th>起息日</th>
                                    <th>到期日</th>
                                    <th>状态</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($investments as $inv): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($inv['product_name']); ?></td>
                                    <td class="text-success fw-bold"><?php echo formatMoney($inv['amount']); ?></td>
                                    <td><?php echo $inv['expected_rate']; ?>%</td>
                                    <td><?php echo $inv['term_days'] > 0 ? $inv['term_days'] . '天' : '灵活'; ?></td>
                                    <td><?php echo formatDateShort($inv['start_date']); ?></td>
                                    <td><?php echo $inv['end_date'] ? formatDateShort($inv['end_date']) : '-'; ?></td>
                                    <td>
                                        <span class="badge <?php echo $inv['status'] === 'invested' ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $inv['status'] === 'invested' ? '持有中' : '已赎回'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($inv['product_type'] === 'flexible'): ?>
                                        <button class="btn btn-sm btn-outline-danger" onclick="redeemInvestment(<?php echo $inv['id']; ?>)">赎回</button>
                                        <?php elseif ($inv['status'] === 'expired'): ?>
                                        <button class="btn btn-sm btn-outline-success" onclick="redeemInvestment(<?php echo $inv['id']; ?>)">赎回</button>
                                        <?php else: ?>
                                        <button class="btn btn-sm btn-outline-info view-detail-btn" 
                                                data-id="<?php echo $inv['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($inv['product_name']); ?>"
                                                data-amount="<?php echo $inv['amount']; ?>"
                                                data-rate="<?php echo $inv['expected_rate']; ?>"
                                                data-term="<?php echo $inv['term_days']; ?>"
                                                data-start-date="<?php echo $inv['start_date']; ?>"
                                                data-end-date="<?php echo $inv['end_date'] ?: ''; ?>"
                                                data-profit="<?php echo $inv['expected_profit']; ?>"
                                                data-status="<?php echo $inv['status']; ?>">查看详情</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card-box">
                    <div class="card-title">
                        <i class="bi bi-star me-2"></i>理财产品推荐
                    </div>
                    
                    <?php foreach ($products as $product): ?>
                    <?php
                    $riskClass = [
                        'low' => 'bg-success',
                        'medium' => 'bg-warning text-dark',
                        'high' => 'bg-danger'
                    ];
                    $riskText = [
                        'low' => '低风险',
                        'medium' => '中风险',
                        'high' => '高风险'
                    ];
                    $typeText = [
                        'fixed' => '固定期限',
                        'flexible' => '灵活存取',
                        'fund' => '基金',
                        'bond' => '债券'
                    ];
                    ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($product['name']); ?></h5>
                                    <small class="text-muted"><?php echo $typeText[$product['type']]; ?></small>
                                </div>
                                <span class="badge <?php echo $riskClass[$product['risk_level']]; ?>">
                                    <?php echo $riskText[$product['risk_level']]; ?>
                                </span>
                            </div>
                            <p class="card-text text-muted small"><?php echo htmlspecialchars($product['description']); ?></p>
                            <div class="row text-center mb-3">
                                <div class="col-4">
                                    <div class="text-success fw-bold fs-5"><?php echo $product['expected_rate']; ?>%</div>
                                    <small class="text-muted">预期年化</small>
                                </div>
                                <div class="col-4">
                                    <div class="fw-bold fs-5"><?php echo formatMoney($product['min_amount']); ?></div>
                                    <small class="text-muted">起投金额</small>
                                </div>
                                <div class="col-4">
                                    <div class="fw-bold fs-5"><?php echo $product['term_days'] > 0 ? $product['term_days'] . '天' : '无限制'; ?></div>
                                    <small class="text-muted">期限</small>
                                </div>
                            </div>
                            <button class="btn btn-success w-100" onclick="buyProduct(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>', <?php echo $product['min_amount']; ?>, <?php echo $product['max_amount']; ?>)">
                                <i class="bi bi-cart-plus me-1"></i>立即投资
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="buyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">购买理财产品</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="investment_buy.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="product_id" id="buyProductId">
                        <div class="alert alert-success">
                            <strong id="buyProductName"></strong>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">投资金额（元）</label>
                            <input type="text" class="form-control" name="amount" id="buyAmount" required data-money>
                            <small class="text-muted">起投金额：<span id="minAmount"></span>元</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">预期年化收益率</label>
                            <input type="text" class="form-control" id="buyRate" readonly>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-success">确认购买</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">投资详情</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">产品名称</label>
                        <input type="text" class="form-control" id="viewProductName" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">投资金额</label>
                        <input type="text" class="form-control" id="viewAmount" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">预期年化</label>
                        <input type="text" class="form-control" id="viewRate" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">期限</label>
                        <input type="text" class="form-control" id="viewTerm" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">起息日</label>
                        <input type="text" class="form-control" id="viewStartDate" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">到期日</label>
                        <input type="text" class="form-control" id="viewEndDate" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">预期收益</label>
                        <input type="text" class="form-control" id="viewProfit" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">投资状态</label>
                        <input type="text" class="form-control" id="viewStatus" readonly>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function buyProduct(id, name, minAmount, maxAmount) {
            document.getElementById('buyProductId').value = id;
            document.getElementById('buyProductName').innerText = name;
            document.getElementById('minAmount').innerText = minAmount.toLocaleString();
            document.getElementById('buyAmount').value = '';
            new bootstrap.Modal(document.getElementById('buyModal')).show();
        }
        
        function redeemInvestment(id) {
            if (confirm('确认赎回该理财产品？')) {
                window.location.href = 'investment_redeem.php?id=' + id;
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('click', function(e) {
                var btn = e.target.closest('.view-detail-btn');
                if (btn) {
                    var data = btn.dataset;
                    document.getElementById('viewProductName').value = data.name;
                    document.getElementById('viewAmount').value = '¥' + parseFloat(data.amount).toLocaleString();
                    document.getElementById('viewRate').value = data.rate + '%';
                    document.getElementById('viewTerm').value = parseInt(data.term) > 0 ? data.term + '天' : '灵活存取';
                    document.getElementById('viewStartDate').value = data.startDate;
                    document.getElementById('viewEndDate').value = data.endDate || '-';
                    document.getElementById('viewProfit').value = '¥' + parseFloat(data.profit).toLocaleString();
                    document.getElementById('viewStatus').value = data.status === 'invested' ? '持有中' : '已赎回';
                    
                    var modal = new bootstrap.Modal(document.getElementById('viewModal'));
                    modal.show();
                }
            });
        });
    </script>
</body>
</html>
