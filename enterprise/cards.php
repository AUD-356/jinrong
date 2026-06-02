<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['enterprise_id'])) {
    redirect('login.php');
}

$enterpriseId = $_SESSION['enterprise_id'];
$activeMenu = 'cards';

$cards = dbGetAll("SELECT * FROM cards WHERE user_id = ? AND user_type = 'enterprise' ORDER BY created_at DESC", [$enterpriseId]);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>企业银行卡管理 - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .card-display {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border-radius: 16px;
            padding: 24px;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .card-display::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        }
        .card-display .bank-name {
            font-size: 18px;
            font-weight: 600;
        }
        .card-display .card-type {
            font-size: 12px;
            opacity: 0.8;
        }
        .card-display .card-number {
            font-size: 22px;
            letter-spacing: 3px;
            margin: 20px 0;
        }
        .card-display .card-holder {
            font-size: 14px;
        }
        .card-display .balance {
            position: absolute;
            right: 24px;
            bottom: 24px;
            text-align: right;
        }
        .card-display .balance-label {
            font-size: 12px;
            opacity: 0.8;
        }
        .card-display .balance-amount {
            font-size: 28px;
            font-weight: 700;
        }
        .card-display.frozen {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        }
        .card-display.pending {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <?php $userName = $_SESSION['company_name']; include __DIR__ . '/../templates/sidebar_enterprise.php'; ?>
    </div>
    
    <div class="main-content">
        <div class="page-header">
            <h2><i class="bi bi-credit-card me-2"></i>企业银行卡管理</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">首页</a></li>
                    <li class="breadcrumb-item active">银行卡</li>
                </ol>
            </nav>
        </div>
        
        <?php if (empty($cards)): ?>
        <div class="card-box">
            <div class="empty-state">
                <i class="bi bi-credit-card"></i>
                <h4>暂无银行卡</h4>
                <p>您还没有申请银行卡，点击下方按钮立即开户</p>
                <a href="cards_apply.php" class="btn btn-success btn-lg">
                    <i class="bi bi-plus-circle me-2"></i>立即开户
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="row mb-4">
            <?php foreach ($cards as $card): ?>
            <div class="col-md-6">
                <div class="card-display <?php echo $card['status']; ?> mb-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="bank-name"><?php echo getBankName($card['bank_code']); ?></div>
                            <div class="card-type"><?php echo $card['card_type'] === 'credit' ? '信用卡' : '借记卡'; ?></div>
                        </div>
                        <span class="badge bg-light text-dark"><?php echo getCardStatusText($card['status']); ?></span>
                    </div>
                    <div class="card-number"><?php echo maskBankCard($card['card_no']); ?></div>
                    <div class="card-holder"><?php echo htmlspecialchars($card['card_holder']); ?></div>
                    <div class="balance">
                        <div class="balance-label">可用余额</div>
                        <div class="balance-amount">¥<?php echo formatMoney($card['balance']); ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="card-box">
            <div class="card-title d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-ul me-2"></i>银行卡详情</span>
                <a href="cards_apply.php" class="btn btn-success btn-sm">
                    <i class="bi bi-plus me-1"></i>添加银行卡
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>卡号</th>
                            <th>开户银行</th>
                            <th>卡类型</th>
                            <th>余额</th>
                            <th>状态</th>
                            <th>开户时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cards as $card): ?>
                        <tr>
                            <td><strong><?php echo maskBankCard($card['card_no']); ?></strong></td>
                            <td><?php echo getBankName($card['bank_code']); ?></td>
                            <td><?php echo $card['card_type'] === 'credit' ? '信用卡' : '借记卡'; ?></td>
                            <td class="text-success fw-bold"><?php echo formatMoney($card['balance']); ?></td>
                            <td><span class="badge <?php echo getCardStatusClass($card['status']); ?>"><?php echo getCardStatusText($card['status']); ?></span></td>
                            <td><?php echo formatDateShort($card['created_at']); ?></td>
                            <td>
                                <a href="cards_detail.php?id=<?php echo $card['id']; ?>" class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
</body>
</html>
