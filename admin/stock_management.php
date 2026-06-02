<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    redirect('login.php');
}

define('IN_ADMIN', true);
$pageTitle = '股票管理';
$currentPage = 'stock_management';

$stocks = dbGetAll("SELECT * FROM stocks ORDER BY code");

if (isset($_POST['update_stock'])) {
    $id = $_POST['stock_id'];
    $current_price = $_POST['current_price'];
    $change_rate = $_POST['change_rate'];
    $change_amount = $_POST['change_amount'];
    $open_price = $_POST['open_price'];
    $close_price = $_POST['close_price'];
    $high_price = $_POST['high_price'];
    $low_price = $_POST['low_price'];
    $volume = $_POST['volume'];
    $amount = $_POST['amount'];

    $sql = "UPDATE stocks SET 
            current_price = ?, 
            change_rate = ?, 
            change_amount = ?, 
            open_price = ?, 
            close_price = ?, 
            high_price = ?, 
            low_price = ?, 
            volume = ?, 
            amount = ?,
            updated_at = NOW()
            WHERE id = ?";

    dbExecute($sql, [
        $current_price,
        $change_rate,
        $change_amount,
        $open_price,
        $close_price,
        $high_price,
        $low_price,
        $volume,
        $amount,
        $id
    ]);

    $_SESSION['success'] = '股票数据更新成功';
    header('Location: stock_management.php');
    exit;
}

if (isset($_POST['add_stock'])) {
    $code = $_POST['code'];
    $name = $_POST['name'];
    $current_price = $_POST['current_price'];
    $change_rate = $_POST['change_rate'] ?? 0;
    $change_amount = $_POST['change_amount'] ?? 0;
    $market = $_POST['market'] ?? 'sh';

    $sql = "INSERT INTO stocks (code, name, current_price, change_rate, change_amount, open_price, close_price, high_price, low_price, market, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    dbExecute($sql, [
        $code,
        $name,
        $current_price,
        $change_rate,
        $change_amount,
        $current_price,
        $current_price,
        $current_price,
        $current_price,
        $market
    ]);

    $_SESSION['success'] = '股票添加成功';
    header('Location: stock_management.php');
    exit;
}

include 'header.php';
?>

<div class="content">
    <div class="page-header">
        <h1>股票管理</h1>
        <p>管理股票数据，设置每日涨跌幅度</p>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">股票列表</h3>
                    <button class="btn btn-primary float-end" data-bs-toggle="modal" data-bs-target="#addStockModal">
                        <i class="bi bi-plus"></i> 添加股票
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>代码</th>
                                    <th>名称</th>
                                    <th>当前价格</th>
                                    <th>涨跌幅</th>
                                    <th>涨跌额</th>
                                    <th>开盘价</th>
                                    <th>最高价</th>
                                    <th>最低价</th>
                                    <th>成交量</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stocks as $stock): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($stock['code']); ?></td>
                                    <td><?php echo htmlspecialchars($stock['name']); ?></td>
                                    <td><?php echo number_format($stock['current_price'], 2); ?></td>
                                    <td>
                                        <span class="badge <?php echo $stock['change_rate'] >= 0 ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo ($stock['change_rate'] >= 0 ? '+' : '') . $stock['change_rate'] . '%'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo ($stock['change_amount'] >= 0 ? '+' : '') . number_format($stock['change_amount'], 2); ?></td>
                                    <td><?php echo number_format($stock['open_price'], 2); ?></td>
                                    <td><?php echo number_format($stock['high_price'], 2); ?></td>
                                    <td><?php echo number_format($stock['low_price'], 2); ?></td>
                                    <td><?php echo number_format($stock['volume']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="editStock(<?php echo htmlspecialchars(json_encode($stock)); ?>)">
                                            <i class="bi bi-pencil"></i> 编辑
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editStockModal" tabindex="-1" aria-labelledby="editStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStockModalLabel">编辑股票数据</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editStockForm" method="post">
                    <input type="hidden" name="stock_id" id="edit_stock_id">
                    <input type="hidden" name="update_stock" value="1">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">股票代码</label>
                            <input type="text" class="form-control" id="edit_code" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">股票名称</label>
                            <input type="text" class="form-control" id="edit_name" readonly>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">当前价格 <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" name="current_price" id="edit_current_price" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">涨跌幅 (%)</label>
                            <input type="number" step="0.01" class="form-control" name="change_rate" id="edit_change_rate">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">涨跌额</label>
                            <input type="number" step="0.01" class="form-control" name="change_amount" id="edit_change_amount">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">开盘价</label>
                            <input type="number" step="0.01" class="form-control" name="open_price" id="edit_open_price">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">收盘价</label>
                            <input type="number" step="0.01" class="form-control" name="close_price" id="edit_close_price">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">最高价</label>
                            <input type="number" step="0.01" class="form-control" name="high_price" id="edit_high_price">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">最低价</label>
                            <input type="number" step="0.01" class="form-control" name="low_price" id="edit_low_price">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">成交量</label>
                            <input type="number" class="form-control" name="volume" id="edit_volume">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">成交额</label>
                            <input type="number" step="0.01" class="form-control" name="amount" id="edit_amount">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">保存修改</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addStockModal" tabindex="-1" aria-labelledby="addStockModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addStockModalLabel">添加新股票</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" name="add_stock" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">股票代码 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="code" required placeholder="如：600000">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">股票名称 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required placeholder="如：浦发银行">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">当前价格 <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" name="current_price" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">涨跌幅 (%)</label>
                        <input type="number" step="0.01" class="form-control" name="change_rate" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">涨跌额</label>
                        <input type="number" step="0.01" class="form-control" name="change_amount" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">市场</label>
                        <select class="form-select" name="market">
                            <option value="sh">上海</option>
                            <option value="sz">深圳</option>
                        </select>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">添加</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editStock(stock) {
    document.getElementById('edit_stock_id').value = stock.id;
    document.getElementById('edit_code').value = stock.code;
    document.getElementById('edit_name').value = stock.name;
    document.getElementById('edit_current_price').value = stock.current_price;
    document.getElementById('edit_change_rate').value = stock.change_rate;
    document.getElementById('edit_change_amount').value = stock.change_amount;
    document.getElementById('edit_open_price').value = stock.open_price;
    document.getElementById('edit_close_price').value = stock.close_price;
    document.getElementById('edit_high_price').value = stock.high_price;
    document.getElementById('edit_low_price').value = stock.low_price;
    document.getElementById('edit_volume').value = stock.volume;
    document.getElementById('edit_amount').value = stock.amount;
    
    var modal = new bootstrap.Modal(document.getElementById('editStockModal'));
    modal.show();
}
</script>
<?php include 'footer.php'; ?>