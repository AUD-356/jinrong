<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    redirect('login.php');
}

define('IN_ADMIN', true);
$pageTitle = '系统配置';

require_once 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $configs = $_POST['config'] ?? [];
    dbBeginTransaction();
    try {
        foreach ($configs as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            if (is_array($value)) {
                $value = implode(',', array_map('sanitize', $value));
            } else {
                $value = sanitize($value);
            }
            $stmt = getDB()->prepare("UPDATE system_configs SET config_value = ?, updated_at = NOW() WHERE config_key = ?");
            $stmt->execute([$value, $key]);
        }
        $logStmt = getDB()->prepare("INSERT INTO operation_logs (operator_id, operator_type, action, details, ip, created_at) VALUES (?, 'admin', 'update_config', '更新系统配置', ?, NOW())");
        $logStmt->execute([$_SESSION['admin_id'], $_SERVER['REMOTE_ADDR'] ?? '']);
        dbCommit();
        $_SESSION['success'] = '配置已保存';
    } catch (Exception $e) {
        dbRollback();
        $_SESSION['error'] = '保存配置失败: ' . $e->getMessage();
    }
    redirect('system_config.php');
}

$basicConfigs = dbGetAll("SELECT * FROM system_configs WHERE group_name = 'basic' ORDER BY id");
$financeConfigs = dbGetAll("SELECT * FROM system_configs WHERE group_name = 'finance' ORDER BY id");
$contactConfigs = dbGetAll("SELECT * FROM system_configs WHERE group_name = 'contact' ORDER BY id");
$loanConfigs = dbGetAll("SELECT * FROM system_configs WHERE group_name = 'loan' ORDER BY id");
$mailConfigs = dbGetAll("SELECT * FROM system_configs WHERE group_name = 'mail' ORDER BY id");

ob_start();
?>
<style>
.nav-tabs .nav-link { color: #666; }
.nav-tabs .nav-link.active { color: #667eea; font-weight: 600; }
</style>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-gear me-2"></i>系统配置</h2>
            <p class="text-muted mb-0">管理系统各项配置参数</p>
        </div>
    </div>
</div>

<div class="card-box">
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#basic" type="button">
                <i class="bi bi-info-circle me-1"></i>基本配置
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#finance" type="button">
                <i class="bi bi-currency-exchange me-1"></i>财务配置
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#contact" type="button">
                <i class="bi bi-telephone me-1"></i>联系方式
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#loan" type="button">
                <i class="bi bi-cash-stack me-1"></i>贷款配置
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#mail" type="button">
                <i class="bi bi-envelope me-1"></i>邮件配置
            </button>
        </li>
    </ul>
    
    <form method="POST">
        <div class="tab-content">
            <div class="tab-pane fade show active" id="basic">
                <h5 class="card-title mb-3"><i class="bi bi-info-circle me-2"></i>基本配置</h5>
                <div class="row g-3">
                    <?php foreach ($basicConfigs as $config): ?>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><?php echo htmlspecialchars($config['description']); ?></label>
                            <input type="text" class="form-control" name="config[<?php echo htmlspecialchars($config['config_key']); ?>]" value="<?php echo htmlspecialchars($config['config_value']); ?>">
                            <input type="hidden" name="config_keys[]" value="<?php echo htmlspecialchars($config['config_key']); ?>">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="tab-pane fade" id="finance">
                <h5 class="card-title mb-3"><i class="bi bi-currency-exchange me-2"></i>财务配置</h5>
                <div class="row g-3">
                    <?php foreach ($financeConfigs as $config): ?>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><?php echo htmlspecialchars($config['description']); ?></label>
                            <?php if ($config['config_type'] === 'boolean'): ?>
                            <select class="form-select" name="config[<?php echo htmlspecialchars($config['config_key']); ?>]">
                                <option value="true" <?php echo $config['config_value'] === 'true' ? 'selected' : ''; ?>>启用</option>
                                <option value="false" <?php echo $config['config_value'] === 'false' ? 'selected' : ''; ?>>禁用</option>
                            </select>
                            <?php else: ?>
                            <input type="<?php echo $config['config_type'] === 'number' ? 'number' : 'text'; ?>" 
                                   class="form-control" 
                                   name="config[<?php echo htmlspecialchars($config['config_key']); ?>]" 
                                   value="<?php echo htmlspecialchars($config['config_value']); ?>">
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="tab-pane fade" id="contact">
                <h5 class="card-title mb-3"><i class="bi bi-telephone me-2"></i>联系方式</h5>
                <div class="row g-3">
                    <?php foreach ($contactConfigs as $config): ?>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><?php echo htmlspecialchars($config['description']); ?></label>
                            <input type="text" class="form-control" name="config[<?php echo htmlspecialchars($config['config_key']); ?>]" value="<?php echo htmlspecialchars($config['config_value']); ?>">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="tab-pane fade" id="loan">
                <h5 class="card-title mb-3"><i class="bi bi-cash-stack me-2"></i>贷款配置</h5>
                <div class="row g-3">
                    <?php foreach ($loanConfigs as $config): ?>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><?php echo htmlspecialchars($config['description']); ?></label>
                            <select class="form-select" name="config[<?php echo htmlspecialchars($config['config_key']); ?>]">
                                <option value="true" <?php echo $config['config_value'] === 'true' ? 'selected' : ''; ?>>启用</option>
                                <option value="false" <?php echo $config['config_value'] === 'false' ? 'selected' : ''; ?>>禁用</option>
                            </select>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="tab-pane fade" id="mail">
                <h5 class="card-title mb-3"><i class="bi bi-envelope me-2"></i>邮件配置</h5>
                <div class="row g-3">
                    <?php foreach ($mailConfigs as $config): ?>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><?php echo htmlspecialchars($config['description']); ?></label>
                            <?php if ($config['config_key'] === 'smtp_password'): ?>
                            <input type="password" class="form-control" name="config[<?php echo htmlspecialchars($config['config_key']); ?>]" value="<?php echo htmlspecialchars($config['config_value']); ?>">
                            <?php elseif ($config['config_key'] === 'smtp_port'): ?>
                            <input type="number" class="form-control" name="config[<?php echo htmlspecialchars($config['config_key']); ?>]" value="<?php echo htmlspecialchars($config['config_value']); ?>">
                            <?php elseif ($config['config_key'] === 'smtp_encryption'): ?>
                            <select class="form-select" name="config[<?php echo htmlspecialchars($config['config_key']); ?>]">
                                <option value="tls" <?php echo $config['config_value'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                <option value="ssl" <?php echo $config['config_value'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                <option value="none" <?php echo $config['config_value'] === 'none' ? 'selected' : ''; ?>>无</option>
                            </select>
                            <?php else: ?>
                            <input type="text" class="form-control" name="config[<?php echo htmlspecialchars($config['config_key']); ?>]" value="<?php echo htmlspecialchars($config['config_value']); ?>">
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="alert alert-info mt-3">
                    <i class="bi bi-info-circle me-2"></i>
                    配置完成后，请确保SMTP服务器支持外部访问，并正确设置发件人邮箱。
                </div>
            </div>
        </div>
        
        <hr class="my-4">
        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i>保存配置
            </button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
echo $content;
require_once 'footer.php';
