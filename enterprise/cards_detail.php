<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['enterprise_id'])) {
    redirect('login.php');
}

$enterpriseId = $_SESSION['enterprise_id'];
$cardId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$card = dbGetRow("SELECT * FROM cards WHERE id = ? AND user_id = ? AND user_type = 'enterprise'", [$cardId, $enterpriseId]);
if (!$card) {
    $_SESSION['error'] = '银行卡不存在或无权限查看';
    redirect('cards.php');
}

$activeMenu = 'cards';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>企业银行卡详情 - <?php echo SITE_NAME; ?></title>
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
        .card-display .bank-name { font-size: 18px; font-weight: 600; }
        .card-display .card-type { font-size: 12px; opacity: 0.8; }
        .card-display .card-number { font-size: 22px; letter-spacing: 3px; margin: 20px 0; }
        .card-display .card-holder { font-size: 14px; }
        .card-display .balance { position: absolute; right: 24px; bottom: 24px; text-align: right; }
        .card-display .balance-label { font-size: 12px; opacity: 0.8; }
        .card-display .balance-amount { font-size: 28px; font-weight: 700; }
    </style>
</head>
<body>
    <div class="sidebar">
        <?php $userName = $_SESSION['company_name']; include __DIR__ . '/../templates/sidebar_enterprise.php'; ?>
    </div>
    <div class="main-content">
        <div class="page-header">
            <h2><i class="bi bi-credit-card me-2"></i>银行卡详情</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">首页</a></li>
                    <li class="breadcrumb-item"><a href="cards.php">银行卡</a></li>
                    <li class="breadcrumb-item active">详情</li>
                </ol>
            </nav>
        </div>

        <div class="card-box mb-4">
            <div class="card-display <?php echo $card['status']; ?>">
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

        <div class="card-box">
            <h5 class="card-title">详细信息</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label text-muted">卡号</label>
                    <div class="form-control-plaintext"><?php echo maskBankCard($card['card_no']); ?></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted">开户银行</label>
                    <div class="form-control-plaintext"><?php echo getBankName($card['bank_code']); ?></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted">卡类型</label>
                    <div class="form-control-plaintext"><?php echo $card['card_type'] === 'credit' ? '信用卡' : '借记卡'; ?></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted">持卡人</label>
                    <div class="form-control-plaintext"><?php echo htmlspecialchars($card['card_holder']); ?></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted">手机号</label>
                    <div class="form-control-plaintext"><?php echo htmlspecialchars($card['phone']); ?></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted">开户时间</label>
                    <div class="form-control-plaintext"><?php echo formatDate($card['created_at']); ?></div>
                </div>
                <div class="col-md-12">
                    <label class="form-label text-muted">备注</label>
                    <div class="form-control-plaintext"><?php echo htmlspecialchars($card['frozen_reason'] ?: '无'); ?></div>
                </div>
            </div>
            <div class="mt-4">
                <a href="cards.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>返回银行卡列表</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
