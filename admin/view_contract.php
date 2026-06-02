<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    redirect('login.php');
}

define('IN_ADMIN', true);
$pageTitle = '查看贷款合同';

$loanId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($loanId <= 0) {
    $_SESSION['error'] = '参数错误';
    redirect('index.php');
}

$loan = dbGetRow("SELECT l.*, 
    u.real_name, u.id_card,
    e.company_name, e.tax_id, e.legal_person
    FROM loans l
    LEFT JOIN users u ON l.user_id = u.id AND l.user_type = 'personal'
    LEFT JOIN enterprises e ON l.user_id = e.id AND l.user_type = 'enterprise'
    WHERE l.id = ?", [$loanId]);

if (!$loan) {
    $_SESSION['error'] = '贷款记录不存在';
    if ($loan['user_type'] === 'personal') {
        redirect('loans_personal.php');
    } else {
        redirect('loans_enterprise.php');
    }
}

$userType = $loan['user_type'];
$userInfo = [];

if ($userType === 'personal') {
    $backUrl = 'loans_personal.php';
    $userInfo['name'] = $loan['real_name'];
    $userInfo['idCard'] = $loan['id_card'];
} else {
    $backUrl = 'loans_enterprise.php';
    $userInfo['name'] = $loan['company_name'];
    $userInfo['idCard'] = $loan['tax_id'];
    $userInfo['legalPerson'] = $loan['legal_person'];
}

require_once 'header.php';
?>
<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-file-earmark-text me-2"></i>查看贷款合同</h2>
        </div>
        <a href="<?php echo $backUrl; ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>返回列表
        </a>
    </div>
</div>

<div class="contract-box" style="max-width: 900px; margin: 0 auto;">
    <div class="text-center mb-4">
        <h4>贷款合同</h4>
        <p class="text-muted">合同编号：<?php echo htmlspecialchars($loan['contract_no'] ?? ('HT' . date('Ymd', strtotime($loan['created_at'])) . $loan['id'])); ?></p>
    </div>
    
    <div class="contract-content mb-4">
        <h5 class="text-center fw-bold mb-4"><?php echo $userType === 'personal' ? '个人贷款合同' : '企业贷款合同'; ?></h5>
        
        <div class="mb-4 pb-4 border-bottom border-gray-200">
            <p class="mb-1"><strong>甲方（贷款人）：</strong><?php echo htmlspecialchars(SITE_NAME); ?></p>
            <p class="mb-1"><strong>乙方（借款人）：</strong><?php echo htmlspecialchars($userInfo['name']); ?></p>
            <p class="mb-1"><strong><?php echo $userType === 'personal' ? '身份证号' : '统一社会信用代码'; ?>：</strong><?php echo htmlspecialchars($userInfo['idCard']); ?></p>
            <?php if ($userType === 'enterprise' && !empty($userInfo['legalPerson'])): ?>
                <p class="mb-1"><strong>法定代表人：</strong><?php echo htmlspecialchars($userInfo['legalPerson']); ?></p>
            <?php endif; ?>
        </div>
        
        <h6 class="fw-bold">第一条 贷款金额及期限</h6>
        <p>甲方向乙方提供贷款人民币（大写）<strong class="text-primary"><?php echo formatMoneyCN($loan['amount']); ?></strong>（小写：￥<?php echo formatMoney($loan['amount']); ?>），贷款期限为<strong class="text-primary"><?php echo $loan['term']; ?></strong>个月，自贷款发放之日起计算。</p>
        
        <h6 class="fw-bold">第二条 贷款利率</h6>
        <p>本合同项下贷款利率为年利率<strong class="text-primary"><?php echo $loan['rate']; ?>%</strong>，按月计息，利息计算公式为：月利息 = 剩余本金 × 年利率 ÷ 12。</p>
        
        <h6 class="fw-bold">第三条 还款方式</h6>
        <p>乙方同意采用<strong class="text-primary">按月等额本息</strong>方式还款，每月还款金额为人民币<strong class="text-primary">￥<?php echo formatMoney($loan['monthly_payment']); ?></strong>元，还款日期为每月的放款对应日。</p>
        
        <h6 class="fw-bold">第四条 权利与义务</h6>
        <p>甲方有权按照本合同约定收取贷款本金和利息；乙方应按照本合同约定按时足额偿还贷款本息。如乙方提前还款，需提前7个工作日向甲方提出书面申请，经甲方同意后方可办理。<?php if ($userType === 'enterprise'): ?>乙方保证所提供的企业资料真实有效，并承担因资料不实所产生的一切法律责任。<?php endif; ?></p>
        
        <h6 class="fw-bold">第五条 违约责任</h6>
        <p>如乙方未按约定日期还款，甲方有权按逾期天数收取罚息，罚息利率为合同约定利率的1.5倍。连续逾期超过30天的，甲方有权宣布贷款提前到期，要求乙方立即偿还全部剩余本息。<?php if ($userType === 'enterprise'): ?>并追究乙方的违约责任。<?php endif; ?></p>
        
        <h6 class="fw-bold">第六条 争议解决</h6>
        <p>本合同履行过程中如发生争议，双方应友好协商解决；协商不成的，任何一方均有权提交甲方所在地有管辖权的人民法院诉讼解决。</p>
        
        <h6 class="fw-bold">第七条 其他条款</h6>
        <p>本合同自双方签字（盖章）之日起生效，一式两份，甲乙双方各执一份，具有同等法律效力。本合同未尽事宜，可由双方另行签订补充协议。<?php if ($userType === 'enterprise'): ?>乙方承诺已获得企业内部必要的授权签署本合同。<?php endif; ?></p>
        
        <div class="mt-5">
            <div class="row justify-content-between">
                <div class="col-md-5 text-center">
                    <p class="mb-2"><?php echo htmlspecialchars(SITE_NAME); ?></p>
                    <div class="signature-line mb-2" style="border-bottom: 1px solid #333; width: 150px; margin: 0 auto; height: 40px;"></div>
                    <div class="text-muted small">甲方（盖章）</div>
                </div>
                <div class="col-md-5 text-center">
                    <p class="mb-2"><?php echo htmlspecialchars($userInfo['name']); ?></p>
                    <?php if (isset($loan['signature_image']) && !empty($loan['signature_image'])): ?>
                        <div class="mb-2">
                            <img src="<?php echo htmlspecialchars($loan['signature_image']); ?>" alt="乙方签名" style="max-height: 60px; max-width: 200px;">
                        </div>
                    <?php else: ?>
                        <div class="signature-line mb-2" style="border-bottom: 1px solid #333; width: 150px; margin: 0 auto; height: 40px;"></div>
                    <?php endif; ?>
                    <div class="text-muted small"><?php echo $userType === 'personal' ? '乙方（签字）' : '乙方（盖章）'; ?></div>
                </div>
            </div>
        </div>
        
        <div class="mt-4 text-center text-muted small">
            <?php if ($loan['contract_signed'] && !empty($loan['contract_signed_at'])): ?>
                签署日期：<?php echo formatDate($loan['contract_signed_at']); ?>
            <?php else: ?>
                签署日期：<?php echo date('Y年m月d日'); ?>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($loan['contract_signed']): ?>
        <div class="alert alert-success mt-4">
            <i class="bi bi-check-circle me-2 text-lg"></i>
            <div>
                <strong>合同已签署</strong>
                <p class="mb-0 mt-1 small">合同于 <?php echo formatDate($loan['contract_signed_at']); ?> 签署完成</p>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning mt-4">
            <i class="bi bi-exclamation-triangle me-2 text-lg"></i>
            <div>
                <strong>合同尚未签署</strong>
                <p class="mb-0 mt-1 small">乙方尚未签署此合同</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.contract-box {
    background: #fff;
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}
.contract-content {
    max-height: none;
    overflow: visible;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 30px;
    background: #fafafa;
}
.contract-content h6 {
    font-weight: bold;
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
</style>

<?php
require_once 'footer.php';
