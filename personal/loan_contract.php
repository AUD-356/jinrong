<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$userId = $_SESSION['user_id'];
$loanId = intval($_GET['id'] ?? 0);

$loan = dbGetRow("SELECT * FROM loans WHERE id = ? AND user_id = ? AND user_type = 'personal'", [$loanId, $userId]);

if (!$loan) {
    $_SESSION['error'] = '贷款记录不存在';
    redirect('loan.php');
}

if ($loan['status'] !== 'approved') {
    $_SESSION['error'] = '当前状态不能签署合同';
    redirect('loan_detail.php?id=' . $loanId);
}

$user = dbGetRow("SELECT * FROM users WHERE id = ?", [$userId]);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>签署合同 - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .contract-box {
            background: #fff;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            max-width: 800px;
            margin: 0 auto;
        }
        .contract-content {
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 30px;
            background: #fafafa;
        }
        .contract-content h5 {
            text-align: center;
            font-weight: 700;
            font-size: 20px;
            margin-bottom: 30px;
            color: #1a1a2e;
        }
        .contract-content h6 {
            font-weight: 600;
            color: #333;
            margin-top: 24px;
            margin-bottom: 12px;
        }
        .contract-content p {
            line-height: 1.8;
            color: #444;
            margin-bottom: 12px;
            text-indent: 2em;
        }
        .contract-content p.no-indent {
            text-indent: 0;
        }
        .contract-signatures {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
        }
        .signature-block {
            text-align: center;
            flex: 1;
        }
        .signature-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }
        .signature-line {
            border-bottom: 1px solid #333;
            width: 150px;
            margin: 0 auto;
            height: 30px;
        }
        .btn-sign {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            border: none;
            padding: 14px 40px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-sign:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(17, 153, 142, 0.4);
        }
        .btn-sign:disabled {
            background: #ccc;
            transform: none;
            box-shadow: none;
            cursor: not-allowed;
        }
        .form-check-input:checked {
            background-color: #11998e;
            border-color: #11998e;
        }
        .alert-success {
            border-radius: 8px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .signature-canvas-wrapper {
            margin-top: 20px;
            padding: 20px;
            border: 2px dashed #ddd;
            border-radius: 8px;
            background: #fff;
        }
        .signature-canvas {
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            cursor: crosshair;
            background: #fafafa;
            width: 100%;
            max-width: 400px;
            height: 150px;
            display: block;
            margin: 0 auto;
        }
        .signature-btn-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 15px;
        }
        .signature-preview {
            margin-top: 15px;
            text-align: center;
            padding: 10px;
            background: #f0f9ff;
            border-radius: 8px;
            display: none;
        }
        .signature-preview img {
            max-width: 300px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <?php $userName = $_SESSION['real_name']; $activeMenu = 'loan'; include __DIR__ . '/../templates/sidebar_personal.php'; ?>
    </div>
    
    <div class="main-content">
        <div class="page-header">
            <h2><i class="bi bi-pen me-2"></i>贷款合同签署</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">首页</a></li>
                    <li class="breadcrumb-item"><a href="loan.php">贷款</a></li>
                    <li class="breadcrumb-item"><a href="loan_detail.php?id=<?php echo $loanId; ?>">详情</a></li>
                    <li class="breadcrumb-item active">签署合同</li>
                </ol>
            </nav>
        </div>
        
        <div class="contract-box" style="max-width: 800px; margin: 0 auto;">
            <div class="text-center mb-4">
                <h4>贷款合同</h4>
                <p class="text-muted">合同编号：<?php echo 'HT' . date('Ymd') . $loan['id']; ?></p>
            </div>
            
            <div class="contract-content mb-4">
                <h5>个人贷款合同</h5>
                
                <div class="mb-4 pb-4 border-bottom border-gray-200">
                    <p class="no-indent mb-1"><strong>甲方（贷款人）：</strong><?php echo SITE_NAME; ?></p>
                    <p class="no-indent mb-1"><strong>乙方（借款人）：</strong><?php echo htmlspecialchars($user['real_name']); ?></p>
                    <p class="no-indent"><strong>身份证号：</strong><?php echo $user['id_card']; ?></p>
                </div>
                
                <h6>第一条 贷款金额及期限</h6>
                <p>甲方向乙方提供贷款人民币（大写）<strong class="text-primary"><?php echo formatMoneyCN($loan['amount']); ?></strong>（小写：¥<?php echo formatMoney($loan['amount']); ?>），贷款期限为<strong class="text-primary"><?php echo $loan['term']; ?></strong>个月，自贷款发放之日起计算。</p>
                
                <h6>第二条 贷款利率</h6>
                <p>本合同项下贷款利率为年利率<strong class="text-primary"><?php echo $loan['rate']; ?>%</strong>，按月计息，利息计算公式为：月利息 = 剩余本金 × 年利率 ÷ 12。</p>
                
                <h6>第三条 还款方式</h6>
                <p>乙方同意采用<strong class="text-primary">按月等额本息</strong>方式还款，每月还款金额为人民币<strong class="text-primary">¥<?php echo formatMoney($loan['monthly_payment']); ?></strong>元，还款日期为每月的放款对应日。</p>
                
                <h6>第四条 权利与义务</h6>
                <p>甲方有权按照本合同约定收取贷款本金和利息；乙方应按照本合同约定按时足额偿还贷款本息。如乙方提前还款，需提前7个工作日向甲方提出书面申请，经甲方同意后方可办理。</p>
                
                <h6>第五条 违约责任</h6>
                <p>如乙方未按约定日期还款，甲方有权按逾期天数收取罚息，罚息利率为合同约定利率的1.5倍。连续逾期超过30天的，甲方有权宣布贷款提前到期，要求乙方立即偿还全部剩余本息。</p>
                
                <h6>第六条 争议解决</h6>
                <p>本合同履行过程中如发生争议，双方应友好协商解决；协商不成的，任何一方均有权提交甲方所在地有管辖权的人民法院诉讼解决。</p>
                
                <h6>第七条 其他条款</h6>
                <p>本合同自双方签字（盖章）之日起生效，一式两份，甲乙双方各执一份，具有同等法律效力。本合同未尽事宜，可由双方另行签订补充协议。</p>
                
                <div class="contract-signatures">
                    <div class="signature-block">
                        <div class="signature-label">甲方（盖章）</div>
                        <div class="signature-line"></div>
                        <div class="mt-2 text-sm text-muted"><?php echo SITE_NAME; ?></div>
                    </div>
                    <div class="signature-block">
                        <div class="signature-label">乙方（签字）</div>
                        <div class="signature-line"></div>
                        <div class="mt-2 text-sm text-muted"><?php echo htmlspecialchars($user['real_name']); ?></div>
                    </div>
                </div>
                
                <div class="mt-4 text-center text-muted text-sm">
                    签署日期：<?php echo date('Y年m月d日'); ?>
                </div>
            </div>
            
            <?php if ($loan['contract_signed']): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-2 text-lg"></i>
                <div>
                    <strong>合同签署成功</strong>
                    <p class="mb-0 mt-1 text-sm">合同已于 <?php echo formatDate($loan['contract_signed_at']); ?> 签署完成</p>
                </div>
            </div>
            <div class="d-flex gap-3">
                <a href="loan_detail.php?id=<?php echo $loanId; ?>" class="btn btn-primary flex-1">
                    <i class="bi bi-arrow-left me-2"></i>返回详情
                </a>
                <a href="loan.php" class="btn btn-outline-primary flex-1">
                    <i class="bi bi-list me-2"></i>贷款列表
                </a>
            </div>
            <?php else: ?>
            <form method="POST" action="loan_sign_do.php" id="signForm">
                <input type="hidden" name="loan_id" value="<?php echo $loanId; ?>">
                <input type="hidden" name="signature" id="signatureData">
                
                <div class="border border-dashed border-gray-300 rounded-lg p-4 mb-4">
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="agreeContract" required>
                        <label class="form-check-label" for="agreeContract">
                            <strong>我已认真阅读并同意</strong>《个人贷款合同》的所有条款，自愿签署本合同。
                        </label>
                    </div>
                    
                    <div class="signature-canvas-wrapper">
                        <h6 class="text-center mb-3"><i class="bi bi-pencil-square me-2"></i>乙方手写签名</h6>
                        <canvas id="signatureCanvas" class="signature-canvas"></canvas>
                        <div class="signature-btn-group">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="clearSignature">
                                <i class="bi bi-eraser me-1"></i>清除签名
                            </button>
                            <button type="button" class="btn btn-primary btn-sm" id="confirmSignature">
                                <i class="bi bi-check-circle me-1"></i>确认签名
                            </button>
                        </div>
                        <div class="signature-preview" id="signaturePreview">
                            <p class="text-sm text-muted mb-2">签名已确认</p>
                            <img id="signatureImg" alt="签名预览">
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-3">
                    <a href="loan_detail.php?id=<?php echo $loanId; ?>" class="btn btn-outline-secondary flex-1">
                        <i class="bi bi-arrow-left me-2"></i>返回修改
                    </a>
                    <button type="submit" class="btn btn-sign flex-1" id="signBtn" disabled>
                        <i class="bi bi-pen me-2"></i>确认签署合同
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let canvas = null;
        let ctx = null;
        let isDrawing = false;
        let lastX = 0;
        let lastY = 0;
        let signatureConfirmed = false;

        document.addEventListener('DOMContentLoaded', function() {
            canvas = document.getElementById('signatureCanvas');
            ctx = canvas.getContext('2d');
            
            // 设置canvas尺寸
            resizeCanvas();
            window.addEventListener('resize', resizeCanvas);
            
            // 初始化画布
            initCanvas();
            
            // 鼠标事件
            canvas.addEventListener('mousedown', startDrawing);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDrawing);
            canvas.addEventListener('mouseout', stopDrawing);
            
            // 触摸事件（支持移动端）
            canvas.addEventListener('touchstart', handleTouchStart);
            canvas.addEventListener('touchmove', handleTouchMove);
            canvas.addEventListener('touchend', stopDrawing);
            
            // 按钮事件
            document.getElementById('clearSignature').addEventListener('click', clearCanvas);
            document.getElementById('confirmSignature').addEventListener('click', confirmSignature);
            
            document.getElementById('agreeContract').addEventListener('change', checkCanSign);
        });
        
        function resizeCanvas() {
            const rect = canvas.getBoundingClientRect();
            canvas.width = 400;
            canvas.height = 150;
            initCanvas();
        }
        
        function initCanvas() {
            ctx.fillStyle = '#fafafa';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
        }
        
        function startDrawing(e) {
            if (signatureConfirmed) return;
            isDrawing = true;
            const coords = getCoordinates(e);
            lastX = coords.x;
            lastY = coords.y;
        }
        
        function draw(e) {
            if (!isDrawing || signatureConfirmed) return;
            e.preventDefault();
            const coords = getCoordinates(e);
            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(coords.x, coords.y);
            ctx.stroke();
            lastX = coords.x;
            lastY = coords.y;
        }
        
        function stopDrawing() {
            isDrawing = false;
        }
        
        function handleTouchStart(e) {
            e.preventDefault();
            const touch = e.touches[0];
            const mouseEvent = new MouseEvent('mousedown', {
                clientX: touch.clientX,
                clientY: touch.clientY
            });
            canvas.dispatchEvent(mouseEvent);
        }
        
        function handleTouchMove(e) {
            e.preventDefault();
            const touch = e.touches[0];
            const mouseEvent = new MouseEvent('mousemove', {
                clientX: touch.clientX,
                clientY: touch.clientY
            });
            canvas.dispatchEvent(mouseEvent);
        }
        
        function getCoordinates(e) {
            const rect = canvas.getBoundingClientRect();
            return {
                x: e.clientX - rect.left,
                y: e.clientY - rect.top
            };
        }
        
        function clearCanvas() {
            signatureConfirmed = false;
            document.getElementById('signaturePreview').style.display = 'none';
            initCanvas();
            checkCanSign();
        }
        
        function confirmSignature() {
            // 检查是否有签名
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            let hasSignature = false;
            for (let i = 0; i < imageData.data.length; i += 4) {
                if (imageData.data[i] !== 250 || imageData.data[i+1] !== 250 || imageData.data[i+2] !== 250) {
                    hasSignature = true;
                    break;
                }
            }
            
            if (!hasSignature) {
                alert('请先在画布上签名！');
                return;
            }
            
            signatureConfirmed = true;
            const dataURL = canvas.toDataURL('image/png');
            document.getElementById('signatureData').value = dataURL;
            document.getElementById('signatureImg').src = dataURL;
            document.getElementById('signaturePreview').style.display = 'block';
            checkCanSign();
        }
        
        function checkCanSign() {
            const agreeChecked = document.getElementById('agreeContract').checked;
            document.getElementById('signBtn').disabled = !(agreeChecked && signatureConfirmed);
        }
    </script>
</body>
</html>
