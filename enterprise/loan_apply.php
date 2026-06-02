<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['enterprise_id'])) {
    redirect('login.php');
}

$enterpriseId = $_SESSION['enterprise_id'];
$productId = intval($_GET['product_id'] ?? 0);

$hasActiveLoan = dbGetRow("SELECT * FROM loans WHERE user_id = ? AND user_type = 'enterprise' AND status IN ('pending', 'approved', 'contract_signed')", [$enterpriseId]);
if ($hasActiveLoan) {
    $_SESSION['error'] = '您有一笔贷款正在处理中，请等待完成后再申请';
    redirect('loan.php');
}

if ($productId > 0) {
    $product = dbGetRow("SELECT * FROM loan_products WHERE id = ? AND user_type IN ('enterprise', 'all') AND status = 'active'", [$productId]);
} else {
    $product = null;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>申请企业贷款 - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="sidebar">
        <?php $userName = $_SESSION['company_name']; $activeMenu = 'loan'; include __DIR__ . '/../templates/sidebar_enterprise.php'; ?>
    </div>
    
    <div class="main-content">
        <div class="page-header">
            <h2><i class="bi bi-file-earmark-text me-2"></i>申请企业贷款</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">首页</a></li>
                    <li class="breadcrumb-item"><a href="loan.php">贷款产品</a></li>
                    <li class="breadcrumb-item active">填写申请</li>
                </ol>
            </nav>
        </div>
        
        <!-- 步骤指示器 -->
        <div class="card-box mb-4">
            <div class="row text-center">
                <div class="col-4">
                    <div class="d-flex flex-column align-items-center">
                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="bi bi-collection fs-4"></i>
                        </div>
                        <span class="mt-2 fw-bold text-success">1. 选择产品</span>
                    </div>
                </div>
                <div class="col-4">
                    <div class="d-flex flex-column align-items-center">
                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="bi bi-pencil-square fs-4"></i>
                        </div>
                        <span class="mt-2 fw-bold text-success">2. 填写申请</span>
                    </div>
                </div>
                <div class="col-4">
                    <div class="d-flex flex-column align-items-center">
                        <div class="rounded-circle bg-light text-muted d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="bi bi-check-circle fs-4"></i>
                        </div>
                        <span class="mt-2 text-muted">3. 提交确认</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card-box">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    <form action="loan_apply_do.php" method="POST" id="loanForm">
                        <?php if ($product): ?>
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <div class="alert alert-success mb-4">
                            <h5><i class="bi bi-star me-2"></i><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="mb-0"><?php echo htmlspecialchars($product['description']); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="form-section">
                            <h5><i class="bi bi-currency-dollar me-2"></i>贷款信息</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">贷款金额（元）<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="amount" id="amount" 
                                           placeholder="请输入贷款金额" required data-money>
                                    <?php if ($product): ?>
                                    <small class="text-muted">
                                        额度范围：<?php echo formatMoney($product['min_amount']); ?> - <?php echo formatMoney($product['max_amount']); ?>
                                    </small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">贷款期限（月）<span class="text-danger">*</span></label>
                                    <select class="form-select" name="term" id="term" required>
                                        <option value="">请选择期限</option>
                                        <?php 
                                        $maxTerm = $product['max_term'] ?? 60;
                                        $minTerm = $product['min_term'] ?? 3;
                                        for ($i = $minTerm; $i <= $maxTerm; $i += 3): 
                                        ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?>个月</option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">贷款用途 <span class="text-danger">*</span></label>
                                    <select class="form-select" name="purpose" required>
                                        <option value="">请选择用途</option>
                                        <option value="流动资金周转">流动资金周转</option>
                                        <option value="设备采购">设备采购</option>
                                        <option value="扩大经营">扩大经营</option>
                                        <option value="技术升级">技术升级</option>
                                        <option value="厂房建设">厂房建设</option>
                                        <option value="原材料采购">原材料采购</option>
                                        <option value="其他">其他</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">年利率（%）<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="rate" id="rate" 
                                           value="<?php echo $product ? $product['min_rate'] : ''; ?>" readonly>
                                    <?php if ($product): ?>
                                    <small class="text-muted">
                                        利率范围：<?php echo $product['min_rate']; ?>% - <?php echo $product['max_rate']; ?>%
                                    </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">月营业额（元）<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="income" placeholder="请输入月营业额" required data-money>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">企业经营年限 <span class="text-danger">*</span></label>
                                    <select class="form-select" name="employment_status" required>
                                        <option value="">请选择</option>
                                        <option value="1年以下">1年以下</option>
                                        <option value="1-3年">1-3年</option>
                                        <option value="3-5年">3-5年</option>
                                        <option value="5-10年">5-10年</option>
                                        <option value="10年以上">10年以上</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5><i class="bi bi-file-text me-2"></i>申请内容</h5>
                            <div class="mb-3">
                                <label class="form-label">申请说明 <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="application_content" rows="4" 
                                          placeholder="请详细描述您的贷款用途、还款计划、企业经营情况等信息" required></textarea>
                                <small class="text-muted">请提供详细的申请说明，包括贷款的具体用途、还款来源、企业经营状况等信息，有助于加快审核进度。</small>
                            </div>
                        </div>
                        
                        <div class="alert alert-info" id="loanPreview" style="display: none;">
                            <h6><i class="bi bi-calculator me-2"></i>贷款预览</h6>
                            <div class="row">
                                <div class="col-4">
                                    <div>贷款金额</div>
                                    <div class="fw-bold" id="previewAmount">-</div>
                                </div>
                                <div class="col-4">
                                    <div>贷款期限</div>
                                    <div class="fw-bold" id="previewTerm">-</div>
                                </div>
                                <div class="col-4">
                                    <div>月供</div>
                                    <div class="fw-bold text-danger" id="previewPayment">-</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            提交申请即表示您同意我们的贷款条款和隐私政策。贷款申请将经过后台审核，审核通过后需签署合同方可放款。
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-send me-2"></i>提交申请
                            </button>
                            <a href="loan.php" class="btn btn-outline-secondary">取消</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card-box">
                    <div class="card-title">
                        <i class="bi bi-shield-check me-2"></i>申请须知
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            依法注册的企业法人
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            有固定经营场所
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            企业经营状况良好
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            信用记录良好
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            贷款用途合法合规
                        </li>
                    </ul>
                </div>
                
                <div class="card-box mt-4">
                    <div class="card-title">
                        <i class="bi bi-question-circle me-2"></i>常见问题
                    </div>
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    贷款审批需要多长时间？
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    企业贷款审批需要3-7个工作日。
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    如何还款？
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    审批通过后，您需签署贷款合同，系统将在约定日期自动从您的企业银行卡扣款。
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script>
        function calculateMonthlyPayment(principal, rate, months) {
            var monthlyRate = rate / 100 / 12;
            if (monthlyRate === 0) return principal / months;
            var payment = principal * monthlyRate * Math.pow(1 + monthlyRate, months) / (Math.pow(1 + monthlyRate, months) - 1);
            return payment.toFixed(2);
        }
        
        function updatePreview() {
            var amount = parseFloat(document.getElementById('amount').value) || 0;
            var term = parseInt(document.getElementById('term').value) || 0;
            var rate = parseFloat(document.getElementById('rate').value) || 0;
            
            if (amount > 0 && term > 0 && rate > 0) {
                var payment = calculateMonthlyPayment(amount, rate, term);
                document.getElementById('loanPreview').style.display = 'block';
                document.getElementById('previewAmount').innerText = '¥' + amount.toLocaleString();
                document.getElementById('previewTerm').innerText = term + '个月';
                document.getElementById('previewPayment').innerText = '¥' + parseFloat(payment).toLocaleString();
            }
        }
        
        document.getElementById('amount').addEventListener('input', updatePreview);
        document.getElementById('term').addEventListener('change', updatePreview);
        
        <?php if ($product): ?>
        // 页面加载时自动填充利率并更新预览
        document.addEventListener('DOMContentLoaded', function() {
            updatePreview();
        });
        <?php endif; ?>
        
        document.getElementById('loanForm').addEventListener('submit', function(e) {
            var amount = parseFloat(document.getElementById('amount').value);
            
            <?php if ($product): ?>
            if (amount < <?php echo $product['min_amount']; ?> || amount > <?php echo $product['max_amount']; ?>) {
                e.preventDefault();
                alert('贷款金额超出允许范围');
                return;
            }
            <?php endif; ?>
        });
    </script>
</body>
</html>
