<div class="sidebar">
    <div class="sidebar-header">
        <div class="d-flex align-items-center">
            <i class="bi bi-building2 text-success fs-4 me-2"></i>
            <div>
                <div class="fw-bold text-white"><?php echo SITE_NAME; ?></div>
                <small class="text-muted">企业中心</small>
            </div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">
            <a href="index.php" class="nav-item <?php echo $activeMenu === 'home' ? 'active' : ''; ?>">
                <i class="bi bi-house"></i>
                <span>首页</span>
            </a>
            <a href="transfer.php" class="nav-item <?php echo $activeMenu === 'transfer' ? 'active' : ''; ?>">
                <i class="bi bi-arrow-left-right"></i>
                <span>转账</span>
            </a>
            <a href="batch_transfer.php" class="nav-item <?php echo $activeMenu === 'batch_transfer' ? 'active' : ''; ?>">
                <i class="bi bi-people"></i>
                <span>批量转账</span>
            </a>
            <a href="cards.php" class="nav-item <?php echo $activeMenu === 'cards' ? 'active' : ''; ?>">
                <i class="bi bi-credit-card"></i>
                <span>银行卡</span>
            </a>
            <a href="loan.php" class="nav-item <?php echo $activeMenu === 'loan' ? 'active' : ''; ?>">
                <i class="bi bi-cash-stack"></i>
                <span>企业贷款</span>
            </a>
            <a href="stock.php" class="nav-item <?php echo $activeMenu === 'stock' ? 'active' : ''; ?>">
                <i class="bi bi-graph-up"></i>
                <span>股票交易</span>
            </a>
            <a href="investment.php" class="nav-item <?php echo $activeMenu === 'investment' ? 'active' : ''; ?>">
                <i class="bi bi-piggy-bank"></i>
                <span>投资理财</span>
            </a>
            <a href="invoice.php" class="nav-item <?php echo $activeMenu === 'invoice' ? 'active' : ''; ?>">
                <i class="bi bi-receipt"></i>
                <span>发票申请</span>
            </a>
            <a href="bills.php" class="nav-item <?php echo $activeMenu === 'bills' ? 'active' : ''; ?>">
                <i class="bi bi-file-earmark-text"></i>
                <span>企业账单</span>
            </a>
        </div>
        
        <div class="nav-section">
            <a href="settings.php" class="nav-item <?php echo $activeMenu === 'settings' ? 'active' : ''; ?>">
                <i class="bi bi-gear"></i>
                <span>账户设置</span>
            </a>
            <a href="logout.php" class="nav-item text-danger">
                <i class="bi bi-box-arrow-right"></i>
                <span>退出登录</span>
            </a>
        </div>
    </nav>
</div>