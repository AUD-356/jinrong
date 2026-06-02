<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$userId = $_SESSION['user_id'];
$activeMenu = 'stock';

$stocks = dbGetAll("SELECT * FROM stocks ORDER BY id LIMIT 20");

$holdings = dbGetAll("SELECT h.*, s.current_price, s.change_rate, 
    (h.quantity * s.current_price) as market_value,
    (h.quantity * s.current_price - h.total_cost) as profit,
    ((h.quantity * s.current_price - h.total_cost) / h.total_cost * 100) as profit_rate
    FROM stock_holdings h 
    LEFT JOIN stocks s ON h.stock_code = s.code 
    WHERE h.user_id = ? AND h.user_type = 'personal' AND h.quantity > 0", [$userId]);

$trades = dbGetAll("SELECT * FROM stock_trades WHERE user_id = ? AND user_type = 'personal' ORDER BY created_at DESC LIMIT 10", [$userId]);

$totalMarketValue = 0;
$totalCost = 0;
foreach ($holdings as $h) {
    $totalMarketValue += $h['market_value'];
    $totalCost += $h['total_cost'];
}
$totalProfit = $totalMarketValue - $totalCost;
$totalProfitRate = $totalCost > 0 ? ($totalProfit / $totalCost) * 100 : 0;

$dailyProfitData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $profit = dbGetRow("SELECT 
        SUM(CASE WHEN type = 'buy' THEN -amount ELSE amount END) as daily_profit
        FROM stock_trades 
        WHERE user_id = ? AND user_type = 'personal' 
        AND DATE(created_at) = ?", [$userId, $date]);
    $dailyProfitData[] = [
        'date' => date('m/d', strtotime($date)),
        'profit' => floatval($profit['daily_profit'] ?? 0)
    ];
}

$selectedStock = $_GET['stock'] ?? $stocks[0]['code'] ?? '';
$selectedStockData = dbGetRow("SELECT * FROM stocks WHERE code = ?", [$selectedStock]);

$klineData = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $klineData[] = [
        'date' => date('m/d', strtotime($date)),
        'open' => round(10 + mt_rand(-200, 200) / 100, 2),
        'close' => round(10 + mt_rand(-200, 200) / 100, 2),
        'high' => round(10 + mt_rand(0, 100) / 100, 2),
        'low' => round(10 - mt_rand(0, 100) / 100, 2),
        'volume' => mt_rand(1000000, 10000000)
    ];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>股票交易 - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stock-card {
            background: #fff;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid #eee;
        }
        .stock-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .stock-card .stock-code { color: #666; font-size: 12px; }
        .stock-card .stock-name { font-weight: 600; color: #333; }
        .stock-card .current-price { font-size: 20px; font-weight: 700; }
        .stock-card .change-rate { font-size: 14px; }
        .profit { color: #e74c3c; }
        .loss { color: #27ae60; }
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        .kline-container {
            position: relative;
            height: 400px;
            width: 100%;
        }
        .stock-selector {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            max-height: 200px;
            overflow-y: auto;
        }
        .stock-tag {
            padding: 4px 12px;
            background: #f0f0f0;
            border-radius: 20px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
        }
        .stock-tag:hover, .stock-tag.active {
            background: #0d6efd;
            color: white;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <?php $userName = $_SESSION['real_name']; include __DIR__ . '/../templates/sidebar_personal.php'; ?>
    </div>
    
    <div class="main-content">
        <div class="page-header">
            <h2><i class="bi bi-graph-up me-2"></i>股票交易</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">首页</a></li>
                    <li class="breadcrumb-item active">股票交易</li>
                </ol>
            </nav>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">总市值</div>
                    <div class="stat-value"><?php echo formatMoney($totalMarketValue); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card success">
                    <div class="stat-label">总盈亏</div>
                    <div class="stat-value <?php echo $totalProfit >= 0 ? 'profit' : 'loss'; ?>">
                        <?php echo $totalProfit >= 0 ? '+' : ''; ?><?php echo formatMoney($totalProfit); ?>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card warning">
                    <div class="stat-label">持仓盈亏比例</div>
                    <div class="stat-value <?php echo $totalProfitRate >= 0 ? 'profit' : 'loss'; ?>">
                        <?php echo $totalProfitRate >= 0 ? '+' : ''; ?><?php echo number_format($totalProfitRate, 2); ?>%
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card info">
                    <div class="stat-label">持仓股票数</div>
                    <div class="stat-value"><?php echo count($holdings); ?></div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="card-box">
                    <div class="card-title">
                        <i class="bi bi-trending-up me-2"></i>近7日盈亏
                    </div>
                    <div class="chart-container">
                        <canvas id="dailyProfitChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card-box">
                    <div class="card-title d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-bar-chart me-2"></i>K线图
                        </div>
                        <div class="stock-selector">
                            <?php foreach ($stocks as $stock): ?>
                            <div class="stock-tag <?php echo $stock['code'] == $selectedStock ? 'active' : ''; ?>" onclick="selectStock('<?php echo $stock['code']; ?>')">
                                <?php echo $stock['code'] . ' ' . getStockName($stock['code']); ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="kline-container">
                        <canvas id="klineChart"></canvas>
                    </div>
                    <?php if ($selectedStockData): ?>
                    <div class="mt-3 row text-center">
                        <div class="col-3">
                            <div class="text-muted small">开盘价</div>
                            <div class="fw-bold"><?php echo formatMoney($selectedStockData['open_price']); ?></div>
                        </div>
                        <div class="col-3">
                            <div class="text-muted small">收盘价</div>
                            <div class="fw-bold"><?php echo formatMoney($selectedStockData['close_price']); ?></div>
                        </div>
                        <div class="col-3">
                            <div class="text-muted small">最高价</div>
                            <div class="fw-bold text-danger"><?php echo formatMoney($selectedStockData['high_price']); ?></div>
                        </div>
                        <div class="col-3">
                            <div class="text-muted small">最低价</div>
                            <div class="fw-bold text-success"><?php echo formatMoney($selectedStockData['low_price']); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card-box">
                    <div class="card-title">
                        <i class="bi bi-list-ul me-2"></i>我的持仓
                    </div>
                    
                    <?php if (empty($holdings)): ?>
                    <div class="empty-state">
                        <i class="bi bi-briefcase"></i>
                        <h4>暂无持仓</h4>
                        <p>您还没有持有任何股票</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>股票代码</th>
                                    <th>股票名称</th>
                                    <th>持仓数量</th>
                                    <th>成本价</th>
                                    <th>现价</th>
                                    <th>盈亏</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($holdings as $h): ?>
                                <?php 
                                $profitClass = $h['profit'] >= 0 ? 'text-danger' : 'text-success';
                                $profitSign = $h['profit'] >= 0 ? '+' : '';
                                ?>
                                <tr>
                                    <td><?php echo $h['stock_code']; ?></td>
                                    <td><?php echo getStockName($h['stock_code']); ?></td>
                                    <td><?php echo $h['quantity']; ?></td>
                                    <td><?php echo formatMoney($h['avg_cost']); ?></td>
                                    <td><?php echo formatMoney($h['current_price']); ?></td>
                                    <td class="<?php echo $profitClass; ?> fw-bold">
                                        <?php echo $profitSign; ?><?php echo formatMoney($h['profit']); ?>
                                        <br><small>(<?php echo $profitSign; ?><?php echo number_format($h['profit_rate'], 2); ?>%)</small>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger" onclick="sellStock('<?php echo $h['stock_code']; ?>', '<?php echo getStockName($h['stock_code']); ?>', <?php echo $h['current_price']; ?>, <?php echo $h['available_quantity']; ?>)">
                                            卖出
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="card-box mt-4">
                    <div class="card-title">
                        <i class="bi bi-clock-history me-2"></i>交易记录
                    </div>
                    
                    <?php if (empty($trades)): ?>
                    <p class="text-muted text-center">暂无交易记录</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>时间</th>
                                    <th>股票</th>
                                    <th>买卖</th>
                                    <th>价格</th>
                                    <th>数量</th>
                                    <th>金额</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trades as $trade): ?>
                                <tr>
                                    <td><small><?php echo formatDateShort($trade['created_at']); ?></small></td>
                                    <td><?php echo $trade['stock_name']; ?></td>
                                    <td>
                                        <span class="badge <?php echo $trade['type'] === 'buy' ? 'bg-danger' : 'bg-success'; ?>">
                                            <?php echo $trade['type'] === 'buy' ? '买入' : '卖出'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatMoney($trade['price']); ?></td>
                                    <td><?php echo $trade['quantity']; ?></td>
                                    <td><?php echo formatMoney($trade['amount']); ?></td>
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
                        <i class="bi bi-graph-up me-2"></i>行情列表
                    </div>
                    
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" placeholder="搜索股票代码或名称" id="stockSearch">
                    </div>
                    
                    <div class="stock-list" style="max-height: 500px; overflow-y: auto;">
                        <?php foreach ($stocks as $stock): ?>
                        <?php 
                        $changeClass = $stock['change_rate'] >= 0 ? 'text-danger' : 'text-success';
                        $changeSign = $stock['change_rate'] >= 0 ? '+' : '';
                        ?>
                        <div class="stock-card mb-2" onclick="buyStock('<?php echo $stock['code']; ?>', '<?php echo addslashes(getStockName($stock['code'])); ?>', <?php echo $stock['current_price']; ?>)">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="stock-name"><?php echo getStockName($stock['code']); ?></div>
                                    <div class="stock-code"><?php echo $stock['code']; ?></div>
                                </div>
                                <div class="text-end">
                                    <div class="current-price"><?php echo formatMoney($stock['current_price']); ?></div>
                                    <div class="change-rate <?php echo $changeClass; ?>">
                                        <?php echo $changeSign; ?><?php echo $stock['change_rate']; ?>%
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="buyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">买入股票</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="buyStockForm">
                    <div class="modal-body">
                        <input type="hidden" name="stock_code" id="buyStockCode">
                        <div class="mb-3">
                            <label class="form-label">股票代码</label>
                            <input type="text" class="form-control" id="buyStockCodeDisplay" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">股票名称</label>
                            <input type="text" class="form-control" id="buyStockName" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">当前价格</label>
                            <input type="text" class="form-control" id="buyPrice" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">买入数量（手）</label>
                            <input type="number" class="form-control" name="quantity" id="buyQuantity" min="1" required>
                            <small class="text-muted">1手 = 100股</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">预计金额</label>
                            <input type="text" class="form-control" id="buyAmount" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">预计手续费</label>
                            <input type="text" class="form-control" id="buyFee" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">预计总扣款</label>
                            <input type="text" class="form-control" id="buyTotalAmount" readonly>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-danger">确认买入</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="sellModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">卖出股票</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="sellStockForm">
                    <div class="modal-body">
                        <input type="hidden" name="stock_code" id="sellStockCode">
                        <div class="mb-3">
                            <label class="form-label">股票代码</label>
                            <input type="text" class="form-control" id="sellStockCodeDisplay" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">股票名称</label>
                            <input type="text" class="form-control" id="sellStockName" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">当前价格</label>
                            <input type="text" class="form-control" id="sellPrice" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">可卖数量</label>
                            <input type="text" class="form-control" id="sellAvailable" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">卖出数量（手）</label>
                            <input type="number" class="form-control" name="quantity" id="sellQuantity" min="1" required>
                            <small class="text-muted">1手 = 100股</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">预计金额</label>
                            <input type="text" class="form-control" id="sellAmount" readonly>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-success">确认卖出</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script>
        function selectStock(code) {
            window.location.href = 'stock.php?stock=' + code;
        }
        
        function buyStock(code, name, price) {
            currentBuyPrice = price;
            document.getElementById('buyStockCode').value = code;
            document.getElementById('buyStockCodeDisplay').value = code;
            document.getElementById('buyStockName').value = name;
            document.getElementById('buyPrice').value = '¥' + price.toFixed(2);
            document.getElementById('buyQuantity').value = '';
            document.getElementById('buyAmount').value = '¥0.00';
            document.getElementById('buyFee').value = '¥0.00';
            document.getElementById('buyTotalAmount').value = '¥0.00';
            new bootstrap.Modal(document.getElementById('buyModal')).show();
        }
        
        function sellStock(code, name, price, available) {
            currentSellPrice = price;
            document.getElementById('sellStockCode').value = code;
            document.getElementById('sellStockCodeDisplay').value = code;
            document.getElementById('sellStockName').value = name;
            document.getElementById('sellPrice').value = '¥' + price.toFixed(2);
            document.getElementById('sellAvailable').value = available + '股';
            document.getElementById('sellQuantity').value = '';
            document.getElementById('sellAmount').value = '¥0.00';
            new bootstrap.Modal(document.getElementById('sellModal')).show();
        }
        
        var currentBuyPrice = 0;
        document.getElementById('buyQuantity').addEventListener('input', function() {
            var qty = parseInt(this.value) || 0;
            var amount = qty * 100 * currentBuyPrice;
            var fee = amount * 0.0015;
            document.getElementById('buyAmount').value = '¥' + amount.toFixed(2);
            document.getElementById('buyFee').value = '¥' + fee.toFixed(2);
            document.getElementById('buyTotalAmount').value = '¥' + (amount + fee).toFixed(2);
        });
        
        var currentSellPrice = 0;
        document.getElementById('sellQuantity').addEventListener('input', function() {
            var qty = parseInt(this.value) || 0;
            var amount = qty * 100 * currentSellPrice;
            document.getElementById('sellAmount').value = '¥' + amount.toFixed(2);
        });
        
        document.getElementById('buyStockForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            
            $.ajax({
                url: 'stock_buy.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $('#buyModal button[type="submit"]').attr('disabled', true).text('处理中...');
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        window.location.reload();
                    } else {
                        alert(response.message);
                    }
                    $('#buyModal button[type="submit"]').attr('disabled', false).text('确认买入');
                    $('#buyModal').modal('hide');
                },
                error: function() {
                    alert('网络错误，请稍后重试');
                    $('#buyModal button[type="submit"]').attr('disabled', false).text('确认买入');
                    $('#buyModal').modal('hide');
                }
            });
        });
        
        document.getElementById('sellStockForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            
            $.ajax({
                url: 'stock_sell.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $('#sellModal button[type="submit"]').attr('disabled', true).text('处理中...');
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        window.location.reload();
                    } else {
                        alert(response.message);
                    }
                    $('#sellModal button[type="submit"]').attr('disabled', false).text('确认卖出');
                    $('#sellModal').modal('hide');
                },
                error: function() {
                    alert('网络错误，请稍后重试');
                    $('#sellModal button[type="submit"]').attr('disabled', false).text('确认卖出');
                    $('#sellModal').modal('hide');
                }
            });
        });
        
        var dailyProfitCtx = document.getElementById('dailyProfitChart').getContext('2d');
        var dailyProfitChart = new Chart(dailyProfitCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($dailyProfitData, 'date')); ?>,
                datasets: [{
                    label: '盈亏（元）',
                    data: <?php echo json_encode(array_column($dailyProfitData, 'profit')); ?>,
                    backgroundColor: <?php echo json_encode(array_map(function($d) { return $d['profit'] >= 0 ? 'rgba(231, 76, 60, 0.8)' : 'rgba(39, 174, 96, 0.8)'; }, $dailyProfitData)); ?>,
                    borderColor: <?php echo json_encode(array_map(function($d) { return $d['profit'] >= 0 ? 'rgba(231, 76, 60, 1)' : 'rgba(39, 174, 96, 1)'; }, $dailyProfitData)); ?>,
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var value = context.parsed.y;
                                return (value >= 0 ? '+' : '') + '¥' + value.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '¥' + value.toFixed(0);
                            }
                        }
                    }
                }
            }
        });
        
        var klineCtx = document.getElementById('klineChart').getContext('2d');
        var klineChart = new Chart(klineCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($klineData, 'date')); ?>,
                datasets: [{
                    label: '收盘价',
                    data: <?php echo json_encode(array_column($klineData, 'close')); ?>,
                    backgroundColor: <?php echo json_encode(array_map(function($d) { return $d['close'] >= $d['open'] ? 'rgba(231, 76, 60, 0.6)' : 'rgba(39, 174, 96, 0.6)'; }, $klineData)); ?>,
                    borderColor: <?php echo json_encode(array_map(function($d) { return $d['close'] >= $d['open'] ? 'rgba(231, 76, 60, 1)' : 'rgba(39, 174, 96, 1)'; }, $klineData)); ?>,
                    borderWidth: 1,
                    borderRadius: 2,
                    barPercentage: 0.6,
                    categoryPercentage: 0.8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var idx = context.dataIndex;
                                var data = <?php echo json_encode($klineData); ?>[idx];
                                return [
                                    '开盘: ¥' + data.open.toFixed(2),
                                    '收盘: ¥' + data.close.toFixed(2),
                                    '最高: ¥' + data.high.toFixed(2),
                                    '最低: ¥' + data.low.toFixed(2)
                                ];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            callback: function(value) {
                                return '¥' + value.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>