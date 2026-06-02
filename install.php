<?php
$host = 'localhost';
$port = '3306';
$user = 'bc';
$pass = 'bc1234.';

function runTest($pdo, $name, $testFn) {
    echo "测试: $name\n";
    try {
        $testFn($pdo);
        echo "  ✓ 通过\n";
        return true;
    } catch (Exception $e) {
        echo "  ✗ 失败: " . $e->getMessage() . "\n";
        return false;
    }
}

try {
    $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== 数据库初始化开始 ===\n\n";
    
    echo "创建数据库...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `bachang` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `bachang`");
    echo "✓ 数据库创建成功\n\n";
    
    echo "创建表结构...\n";
    
    $sql = "
    -- 管理员表
    CREATE TABLE IF NOT EXISTS `admin_users` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `real_name` VARCHAR(50) DEFAULT '',
        `email` VARCHAR(100) DEFAULT '',
        `phone` VARCHAR(20) DEFAULT '',
        `role` ENUM('super_admin', 'admin', 'operator') DEFAULT 'operator',
        `status` ENUM('active', 'inactive') DEFAULT 'active',
        `last_login` DATETIME DEFAULT NULL,
        `last_ip` VARCHAR(50) DEFAULT '',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    -- 个人用户表
    CREATE TABLE IF NOT EXISTS `users` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `pay_password` VARCHAR(255) DEFAULT '',
        `real_name` VARCHAR(50) NOT NULL,
        `id_card` VARCHAR(18) DEFAULT '',
        `phone` VARCHAR(20) NOT NULL,
        `email` VARCHAR(100) DEFAULT '',
        `address` VARCHAR(255) DEFAULT '',
        `id_card_front` VARCHAR(255) DEFAULT '',
        `id_card_back` VARCHAR(255) DEFAULT '',
        `status` ENUM('pending', 'active', 'frozen', 'closed') DEFAULT 'pending',
        `freeze_reason` VARCHAR(255) DEFAULT '',
        `last_login` DATETIME DEFAULT NULL,
        `last_ip` VARCHAR(50) DEFAULT '',
        `activation_code` VARCHAR(29) DEFAULT NULL,
        `is_activated` TINYINT(1) DEFAULT 0,
        `activated_at` DATETIME DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    -- 企业用户表
    CREATE TABLE IF NOT EXISTS `enterprises` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `pay_password` VARCHAR(255) DEFAULT '',
        `company_name` VARCHAR(200) NOT NULL,
        `tax_id` VARCHAR(50) DEFAULT '',
        `legal_person` VARCHAR(50) DEFAULT '',
        `legal_id_card` VARCHAR(18) DEFAULT '',
        `contact_person` VARCHAR(50) DEFAULT '',
        `contact_phone` VARCHAR(20) NOT NULL,
        `contact_email` VARCHAR(100) DEFAULT '',
        `address` VARCHAR(255) DEFAULT '',
        `business_license` VARCHAR(255) DEFAULT '',
        `organization_code` VARCHAR(50) DEFAULT '',
        `bank_name` VARCHAR(100) DEFAULT '',
        `bank_account` VARCHAR(50) DEFAULT '',
        `status` ENUM('pending', 'active', 'frozen', 'closed') DEFAULT 'pending',
        `freeze_reason` VARCHAR(255) DEFAULT '',
        `last_login` DATETIME DEFAULT NULL,
        `last_ip` VARCHAR(50) DEFAULT '',
        `activation_code` VARCHAR(29) DEFAULT NULL,
        `is_activated` TINYINT(1) DEFAULT 0,
        `activated_at` DATETIME DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    -- 银行卡表
    CREATE TABLE IF NOT EXISTS `cards` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNSIGNED NOT NULL,
        `user_type` ENUM('personal', 'enterprise') NOT NULL,
        `card_no` VARCHAR(30) NOT NULL,
        `card_pwd` VARCHAR(10) DEFAULT '',
        `bank_code` VARCHAR(20) NOT NULL,
        `card_type` ENUM('debit', 'credit') DEFAULT 'debit',
        `card_holder` VARCHAR(50) NOT NULL,
        `phone` VARCHAR(20) DEFAULT '',
        `balance` DECIMAL(15, 2) DEFAULT 0.00,
        `status` ENUM('pending', 'active', 'frozen', 'closed') DEFAULT 'pending',
        `frozen_reason` VARCHAR(255) DEFAULT '',
        `frozen_at` DATETIME DEFAULT NULL,
        `activated_at` DATETIME DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user (`user_id`, `user_type`),
        INDEX idx_card_no (`card_no`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    -- 申请记录表
    CREATE TABLE IF NOT EXISTS `applications` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `receipt_no` VARCHAR(30) NOT NULL UNIQUE,
        `user_id` INT UNSIGNED NOT NULL,
        `user_type` ENUM('personal', 'enterprise') NOT NULL,
        `type` ENUM('register', 'transfer', 'card_open', 'loan', 'forgot_password', 'other') NOT NULL,
        `status` ENUM('pending', 'processing', 'approved', 'rejected', 'completed') DEFAULT 'pending',
        `title` VARCHAR(200) DEFAULT '',
        `amount` DECIMAL(15, 2) DEFAULT 0.00,
        `content` TEXT,
        `admin_remark` TEXT,
        `processed_by` INT UNSIGNED DEFAULT NULL,
        `processed_at` DATETIME DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_receipt (`receipt_no`),
        INDEX idx_user (`user_id`, `user_type`),
        INDEX idx_status (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    -- 转账记录表
    CREATE TABLE IF NOT EXISTS `transfers` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `receipt_no` VARCHAR(30) NOT NULL UNIQUE,
        `from_user_id` INT UNSIGNED NOT NULL,
        `from_user_type` ENUM('personal', 'enterprise') NOT NULL,
        `from_card_id` INT UNSIGNED DEFAULT NULL,
        `to_card_id` INT UNSIGNED DEFAULT NULL,
        `to_card_no` VARCHAR(30) DEFAULT '',
        `to_bank` VARCHAR(100) DEFAULT '',
        `amount` DECIMAL(15, 2) NOT NULL,
        `fee` DECIMAL(10, 2) DEFAULT 0.00,
        `remark` VARCHAR(255) DEFAULT '',
        `status` ENUM('pending', 'approved', 'rejected', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending',
        `admin_remark` TEXT,
        `processed_by` INT UNSIGNED DEFAULT NULL,
        `processed_at` DATETIME DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_from_user (`from_user_id`, `from_user_type`),
        INDEX idx_receipt (`receipt_no`),
        INDEX idx_status (`status`),
        INDEX idx_from_user_status (`from_user_id`, `from_user_type`, `status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    -- 批量转账记录表
    CREATE TABLE IF NOT EXISTS `batch_transfers` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `receipt_no` VARCHAR(30) NOT NULL UNIQUE,
        `from_user_id` INT UNSIGNED NOT NULL,
        `from_user_type` ENUM('enterprise') NOT NULL,
        `from_card_id` INT UNSIGNED DEFAULT NULL,
        `total_amount` DECIMAL(15, 2) NOT NULL,
        `total_count` INT NOT NULL,
        `total_fee` DECIMAL(10, 2) DEFAULT 0.00,
        `status` ENUM('pending', 'approved', 'rejected', 'processing', 'completed', 'failed') DEFAULT 'pending',
        `admin_remark` TEXT,
        `processed_by` INT UNSIGNED DEFAULT NULL,
        `processed_at` DATETIME DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user (`from_user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    -- 批量转账明细表
    CREATE TABLE IF NOT EXISTS `batch_transfer_items` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `batch_id` INT UNSIGNED NOT NULL,
        `to_card_no` VARCHAR(30) NOT NULL,
        `to_bank` VARCHAR(100) DEFAULT '',
        `to_name` VARCHAR(50) DEFAULT '',
        `amount` DECIMAL(15, 2) NOT NULL,
        `fee` DECIMAL(10, 2) DEFAULT 0.00,
        `status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
        `remark` VARCHAR(255) DEFAULT '',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_batch (`batch_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    -- 贷款产品表
    CREATE TABLE IF NOT EXISTS `loan_products` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `code` VARCHAR(50) NOT NULL UNIQUE,
        `description` TEXT,
        `min_amount` DECIMAL(15, 2) NOT NULL,
        `max_amount` DECIMAL(15, 2) NOT NULL,
        `min_term` INT NOT NULL,
        `max_term` INT NOT NULL,
        `min_rate` DECIMAL(5, 2) NOT NULL,
        `max_rate` DECIMAL(5, 2) NOT NULL,
        `user_type` ENUM('personal', 'enterprise', 'all') DEFAULT 'all',
        `requirements` TEXT,
        `documents` TEXT,
        `status` ENUM('active', 'inactive') DEFAULT 'active',
        `sort_order` INT DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    -- 贷款申请表
    CREATE TABLE IF NOT EXISTS `loans` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `receipt_no` VARCHAR(30) NOT NULL UNIQUE,
        `user_id` INT UNSIGNED NOT NULL,
        `user_type` ENUM('personal', 'enterprise') NOT NULL,
        `product_id` INT UNSIGNED DEFAULT NULL,
        `amount` DECIMAL(15, 2) NOT NULL,
        `term` INT NOT NULL,
        `rate` DECIMAL(5, 2) NOT NULL,
        `monthly_payment` DECIMAL(15, 2) NOT NULL,
        `total_payment` DECIMAL(15, 2) NOT NULL,
        `purpose` VARCHAR(255) DEFAULT '',
        `income` DECIMAL(15, 2) DEFAULT 0.00,
        `employment_status` VARCHAR(50) DEFAULT '',
        `application_content` TEXT,
        `contract_no` VARCHAR(50) DEFAULT '',
        `contract_signed` TINYINT(1) DEFAULT 0,
        `contract_file` VARCHAR(255) DEFAULT '',
        `signature_image` TEXT DEFAULT NULL,
        `contract_signed_at` DATETIME DEFAULT NULL,
        `disbursed_at` DATETIME DEFAULT NULL,
        `status` ENUM('pending', 'approved', 'contract_signed', 'disbursed', 'rejected', 'paid', 'overdue') DEFAULT 'pending',
        `admin_remark` TEXT,
        `processed_by` INT UNSIGNED DEFAULT NULL,
        `processed_at` DATETIME DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user (`user_id`, `user_type`),
        INDEX idx_receipt (`receipt_no`),
        INDEX idx_status (`status`),
        INDEX idx_user_status (`user_id`, `user_type`, `status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    -- 还款记录表
    CREATE TABLE IF NOT EXISTS `repayments` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `loan_id` INT UNSIGNED NOT NULL,
        `user_id` INT UNSIGNED NOT NULL,
        `user_type` ENUM('personal', 'enterprise') NOT NULL,
        `period` INT NOT NULL,
        `principal` DECIMAL(15, 2) NOT NULL,
        `interest` DECIMAL(15, 2) NOT NULL,
        `amount` DECIMAL(15, 2) NOT NULL,
        `due_date` DATE NOT NULL,
        `paid_date` DATE DEFAULT NULL,
        `status` ENUM('pending', 'paid', 'overdue') DEFAULT 'pending',
        `paid_amount` DECIMAL(15, 2) DEFAULT 0.00,
        `late_fee` DECIMAL(10, 2) DEFAULT 0.00,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_loan (`loan_id`),
        INDEX idx_due_date (`due_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    -- 发票申请表
    CREATE TABLE IF NOT EXISTS `invoices` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `receipt_no` VARCHAR(30) NOT NULL UNIQUE,
        `user_id` INT UNSIGNED NOT NULL,
        `user_type` ENUM('personal', 'enterprise') NOT NULL,
        `type` ENUM('personal', 'enterprise') NOT NULL,
        `title` VARCHAR(200) NOT NULL,
        `tax_id` VARCHAR(50) DEFAULT '',
        `amount` DECIMAL(15, 2) NOT NULL,
        `content` VARCHAR(255) DEFAULT '',
        `email` VARCHAR(100) NOT NULL,
        `phone` VARCHAR(20) DEFAULT '',
        `address` VARCHAR(255) DEFAULT '',
        `bank_name` VARCHAR(100) DEFAULT '',
        `bank_account` VARCHAR(50) DEFAULT '',
        `status` ENUM('pending', 'approved', 'rejected', 'sent') DEFAULT 'pending',
        `admin_remark` TEXT,
        `invoice_no` VARCHAR(50) DEFAULT '',
        `processed_by` INT UNSIGNED DEFAULT NULL,
        `processed_at` DATETIME DEFAULT NULL,
        `sent_at` DATETIME DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user (`user_id`, `user_type`),
        INDEX idx_receipt (`receipt_no`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    -- 股票表
    CREATE TABLE IF NOT EXISTS `stocks` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `code` VARCHAR(10) NOT NULL UNIQUE,
        `name` VARCHAR(50) NOT NULL,
        `current_price` DECIMAL(10, 2) NOT NULL,
        `change_rate` DECIMAL(8, 2) DEFAULT 0.00,
        `change_amount` DECIMAL(10, 2) DEFAULT 0.00,
        `open_price` DECIMAL(10, 2) DEFAULT 0.00,
        `close_price` DECIMAL(10, 2) DEFAULT 0.00,
        `high_price` DECIMAL(10, 2) DEFAULT 0.00,
        `low_price` DECIMAL(10, 2) DEFAULT 0.00,
        `volume` BIGINT DEFAULT 0,
        `amount` DECIMAL(20, 2) DEFAULT 0.00,
        `market` ENUM('sh', 'sz') DEFAULT 'sh',
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    -- 用户持仓表
    CREATE TABLE IF NOT EXISTS `stock_holdings` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNSIGNED NOT NULL,
        `user_type` ENUM('personal', 'enterprise') NOT NULL,
        `stock_code` VARCHAR(10) NOT NULL,
        `quantity` INT NOT NULL,
        `available_quantity` INT NOT NULL,
        `avg_cost` DECIMAL(10, 2) NOT NULL,
        `total_cost` DECIMAL(15, 2) NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_user_stock (`user_id`, `user_type`, `stock_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    -- 股票交易记录表
    CREATE TABLE IF NOT EXISTS `stock_trades` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `receipt_no` VARCHAR(30) NOT NULL UNIQUE,
        `user_id` INT UNSIGNED NOT NULL,
        `user_type` ENUM('personal', 'enterprise') NOT NULL,
        `stock_code` VARCHAR(10) NOT NULL,
        `stock_name` VARCHAR(50) NOT NULL,
        `type` ENUM('buy', 'sell') NOT NULL,
        `price` DECIMAL(10, 2) NOT NULL,
        `quantity` INT NOT NULL,
        `amount` DECIMAL(15, 2) NOT NULL,
        `fee` DECIMAL(10, 2) DEFAULT 0.00,
        `status` ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'completed',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (`user_id`, `user_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    -- 理财产品表
    CREATE TABLE IF NOT EXISTS `investments` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `code` VARCHAR(50) NOT NULL UNIQUE,
        `type` ENUM('fixed', 'flexible', 'fund', 'bond') NOT NULL,
        `description` TEXT,
        `min_amount` DECIMAL(15, 2) NOT NULL,
        `max_amount` DECIMAL(15, 2) DEFAULT 999999999.99,
        `expected_rate` DECIMAL(5, 2) NOT NULL,
        `term_days` INT DEFAULT 0,
        `risk_level` ENUM('low', 'medium', 'high') DEFAULT 'low',
        `start_date` DATE DEFAULT NULL,
        `end_date` DATE DEFAULT NULL,
        `status` ENUM('active', 'inactive', 'sold_out') DEFAULT 'active',
        `total_amount` DECIMAL(20, 2) DEFAULT 0.00,
        `sold_amount` DECIMAL(20, 2) DEFAULT 0.00,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    -- 用户理财记录表
    CREATE TABLE IF NOT EXISTS `user_investments` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `receipt_no` VARCHAR(30) NOT NULL UNIQUE,
        `user_id` INT UNSIGNED NOT NULL,
        `user_type` ENUM('personal', 'enterprise') NOT NULL,
        `product_id` INT UNSIGNED NOT NULL,
        `product_name` VARCHAR(100) NOT NULL,
        `amount` DECIMAL(15, 2) NOT NULL,
        `expected_rate` DECIMAL(5, 2) NOT NULL,
        `expected_profit` DECIMAL(15, 2) DEFAULT 0.00,
        `term_days` INT DEFAULT 0,
        `start_date` DATE NOT NULL,
        `end_date` DATE DEFAULT NULL,
        `status` ENUM('pending', 'invested', 'redeemed', 'expired') DEFAULT 'invested',
        `redeemed_at` DATETIME DEFAULT NULL,
        `actual_profit` DECIMAL(15, 2) DEFAULT 0.00,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (`user_id`, `user_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    -- 账单记录表
    CREATE TABLE IF NOT EXISTS `bills` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNSIGNED NOT NULL,
        `user_type` ENUM('personal', 'enterprise') NOT NULL,
        `type` ENUM('transfer_out', 'transfer_in', 'loan_disbursed', 'loan_repayment', 'stock_buy', 'stock_sell', 'investment', 'investment_redeem', 'invoice', 'fee', 'other') NOT NULL,
        `amount` DECIMAL(15, 2) NOT NULL,
        `balance` DECIMAL(15, 2) DEFAULT 0.00,
        `title` VARCHAR(200) DEFAULT '',
        `content` TEXT,
        `related_id` INT UNSIGNED DEFAULT NULL,
        `related_type` VARCHAR(50) DEFAULT '',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (`user_id`, `user_type`),
        INDEX idx_created (`created_at`),
        INDEX idx_user_type_created (`user_id`, `user_type`, `created_at`, `type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    -- 操作日志表
    CREATE TABLE IF NOT EXISTS `operation_logs` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `operator_id` INT UNSIGNED NOT NULL,
        `operator_type` VARCHAR(20) NOT NULL,
        `action` VARCHAR(100) NOT NULL,
        `details` TEXT,
        `target_id` INT UNSIGNED DEFAULT NULL,
        `target_type` VARCHAR(50) DEFAULT '',
        `ip` VARCHAR(50) DEFAULT '',
        `user_agent` VARCHAR(255) DEFAULT '',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_operator (`operator_id`, `operator_type`),
        INDEX idx_created (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    -- 系统配置表
    CREATE TABLE IF NOT EXISTS `system_configs` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `config_key` VARCHAR(100) NOT NULL UNIQUE,
        `config_value` TEXT,
        `config_type` VARCHAR(50) DEFAULT 'string',
        `group_name` VARCHAR(50) DEFAULT 'general',
        `description` VARCHAR(255) DEFAULT '',
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    $statements = explode(';', $sql);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    echo "✓ 表结构创建成功\n\n";
    
    echo "数据修复：检查并添加必要字段...\n";
    
    // 检查并添加 pay_password 字段
    foreach (['users', 'enterprises'] as $table) {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE 'pay_password'");
        $stmt->execute();
        $exists = $stmt->fetch();
        
        if (!$exists) {
            echo "  正在为 `{$table}` 表添加 pay_password 字段...\n";
            $sql = "ALTER TABLE `{$table}` ADD COLUMN `pay_password` VARCHAR(255) DEFAULT '' AFTER `password`";
            $pdo->exec($sql);
            echo "  ✓ `{$table}` 表的 pay_password 字段添加成功\n";
        } else {
            echo "  ✓ `{$table}` 表的 pay_password 字段已存在\n";
        }
    }
    
    // 检查并添加 signature_image 字段
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `loans` LIKE 'signature_image'");
    $stmt->execute();
    $exists = $stmt->fetch();
    
    if (!$exists) {
        try {
            echo "  正在为 loans 表添加 signature_image 字段...\n";
            // 首先尝试使用 IF NOT EXISTS（MySQL 8.0+）
            $sql = "ALTER TABLE `loans` ADD COLUMN IF NOT EXISTS `signature_image` TEXT DEFAULT NULL AFTER `contract_file`";
            $pdo->exec($sql);
        } catch (Exception $e) {
            // 如果失败，尝试不使用 IF NOT EXISTS
            echo "  使用备用语法添加字段...\n";
            $sql = "ALTER TABLE `loans` ADD COLUMN `signature_image` TEXT DEFAULT NULL AFTER `contract_file`";
            $pdo->exec($sql);
        }
        echo "  ✓ signature_image 字段添加成功\n";
    } else {
        echo "  ✓ signature_image 字段已存在\n";
    }
    echo "\n";
    
    echo "插入初始数据...\n";
    
    $pdo->exec("INSERT IGNORE INTO `admin_users` (`username`, `password`, `real_name`, `role`, `status`) VALUES 
        ('admin', '" . password_hash('password', PASSWORD_DEFAULT) . "', '系统管理员', 'super_admin', 'active')");
    echo "✓ 管理员账号\n";
    
    $pdo->exec("INSERT IGNORE INTO `stocks` (`code`, `name`, `current_price`, `change_rate`, `change_amount`, `open_price`, `close_price`, `high_price`, `low_price`, `volume`, `amount`, `market`) VALUES 
        ('600000', '浦发银行', 8.50, 1.25, 0.10, 8.40, 8.40, 8.55, 8.38, 45678900, 387756300.00, 'sh'),
        ('600036', '招商银行', 35.80, 2.15, 0.75, 35.10, 35.05, 35.95, 35.00, 123456789, 4398265320.00, 'sh'),
        ('600519', '贵州茅台', 1688.00, -0.85, -14.50, 1702.50, 1702.50, 1710.00, 1685.00, 2345678, 3956789012.00, 'sh'),
        ('601398', '工商银行', 5.20, 0.58, 0.03, 5.17, 5.17, 5.22, 5.16, 98765432, 512345678.00, 'sh'),
        ('601288', '农业银行', 3.15, 1.28, 0.04, 3.11, 3.11, 3.16, 3.10, 87654321, 276543210.00, 'sh'),
        ('601988', '中国银行', 3.45, 0.29, 0.01, 3.44, 3.44, 3.46, 3.43, 65432109, 225678901.00, 'sh'),
        ('601318', '中国平安', 48.50, 1.68, 0.80, 47.70, 47.70, 48.80, 47.65, 56789012, 2745678901.00, 'sh'),
        ('600028', '中国石化', 4.85, -0.21, -0.01, 4.86, 4.86, 4.88, 4.83, 34567890, 167890123.00, 'sh'),
        ('601857', '中国石油', 5.65, 0.35, 0.02, 5.63, 5.63, 5.68, 5.62, 43210987, 244321098.00, 'sh'),
        ('600016', '民生银行', 4.12, 1.73, 0.07, 4.05, 4.05, 4.15, 4.04, 78901234, 324567890.00, 'sh'),
        ('000001', '平安银行', 12.35, 0.65, 0.08, 12.27, 12.27, 12.40, 12.25, 34567890, 426789012.00, 'sz'),
        ('000002', '万科A', 8.90, -1.22, -0.11, 9.01, 9.01, 9.05, 8.85, 56789012, 505678901.00, 'sz'),
        ('000858', '五粮液', 145.60, 1.45, 2.08, 143.52, 143.52, 146.00, 143.20, 12345678, 1792345678.00, 'sz'),
        ('600887', '伊利股份', 26.80, 0.45, 0.12, 26.68, 26.68, 26.95, 26.65, 23456789, 628456789.00, 'sh'),
        ('601888', '中国国旅', 68.50, 2.18, 1.46, 67.04, 67.04, 68.90, 66.95, 8765432, 599876543.00, 'sh')");
    echo "✓ 股票数据\n";
    
    $pdo->exec("INSERT IGNORE INTO `loan_products` (`name`, `code`, `description`, `min_amount`, `max_amount`, `min_term`, `max_term`, `min_rate`, `max_rate`, `user_type`, `requirements`, `status`, `sort_order`) VALUES 
        ('个人消费贷款', 'PERSONAL_CONSUMER', '用于个人消费的信用贷款产品，额度高，放款快', 5000.00, 500000.00, 3, 60, 4.35, 15.00, 'personal', '年龄22-55周岁，有稳定收入来源，信用记录良好', 'active', 1),
        ('个人经营贷款', 'PERSONAL_BUSINESS', '支持个人创业经营，灵活便捷', 10000.00, 1000000.00, 6, 120, 5.25, 18.00, 'personal', '有营业执照，经营满1年，信用记录良好', 'active', 2),
        ('个人住房贷款', 'PERSONAL_HOUSE', '购房贷款，利率优惠，还款灵活', 100000.00, 5000000.00, 12, 360, 3.25, 6.55, 'personal', '有购房合同，首付比例符合要求', 'active', 3),
        ('个人汽车贷款', 'PERSONAL_CAR', '购车贷款，轻松实现有车梦想', 30000.00, 1000000.00, 12, 60, 4.75, 12.00, 'personal', '有购车意向，支付首付款', 'active', 4),
        ('企业流动资金贷款', 'ENTERPRISE_WORKING', '满足企业日常经营周转需求', 100000.00, 10000000.00, 3, 60, 4.35, 10.00, 'enterprise', '企业注册满2年，有固定经营场所', 'active', 1),
        ('企业项目贷款', 'ENTERPRISE_PROJECT', '支持企业投资项目和发展', 500000.00, 50000000.00, 12, 120, 4.85, 9.50, 'enterprise', '有项目可行性报告，担保或抵押', 'active', 2),
        ('企业设备贷款', 'ENTERPRISE_EQUIPMENT', '采购设备融资，支持企业发展', 200000.00, 5000000.00, 6, 60, 5.00, 10.50, 'enterprise', '有设备采购合同', 'active', 3)");
    echo "✓ 贷款产品\n";
    
    $pdo->exec("INSERT IGNORE INTO `investments` (`name`, `code`, `type`, `description`, `min_amount`, `expected_rate`, `term_days`, `risk_level`, `status`) VALUES 
        ('稳盈宝A', 'SAFE_A', 'fixed', '固定期限理财产品，收益稳定，适合保守型投资者', 1000.00, 3.80, 90, 'low', 'active'),
        ('稳盈宝B', 'SAFE_B', 'fixed', '固定期限理财产品，中等收益，适合稳健型投资者', 10000.00, 4.50, 180, 'low', 'active'),
        ('稳盈宝C', 'SAFE_C', 'fixed', '固定期限理财产品，高收益，适合进取型投资者', 50000.00, 5.20, 365, 'medium', 'active'),
        ('灵活盈', 'FLEX_1', 'flexible', '灵活存取，随时申赎，适合资金周转需求', 100.00, 2.50, 0, 'low', 'active'),
        ('月月盈', 'MONTHLY_1', 'fixed', '每月付息，到期还本，适合固定收入需求', 10000.00, 4.20, 30, 'low', 'active'),
        ('季季盈', 'QUARTER_1', 'fixed', '季度付息，适合中期理财规划', 10000.00, 4.80, 90, 'low', 'active')");
    echo "✓ 理财产品\n";
    
    $pdo->exec("INSERT IGNORE INTO `system_configs` (`config_key`, `config_value`, `config_type`, `group_name`, `description`) VALUES 
        ('site_name', '金融练习靶场', 'string', 'basic', '系统名称'),
        ('site_url', 'http://localhost/bachang', 'string', 'basic', '系统地址'),
        ('transfer_fee_rate', '0.1', 'number', 'finance', '转账手续费率(%)'),
        ('stock_trade_fee_rate', '0.15', 'number', 'finance', '股票交易手续费率(%)'),
        ('min_transfer_amount', '0.01', 'number', 'finance', '最小转账金额'),
        ('max_transfer_amount', '5000000', 'number', 'finance', '最大转账金额'),
        ('personal_transfer_limit', '5000', 'number', 'finance', '个人用户免审核转账限额'),
        ('enterprise_transfer_limit', '10000', 'number', 'finance', '企业用户免审核转账限额'),
        ('contact_phone', '400-888-8888', 'string', 'contact', '客服电话'),
        ('contact_email', 'service@bachang.com', 'string', 'contact', '客服邮箱'),
        ('loan_auto_approve', 'false', 'boolean', 'loan', '贷款自动审核'),
        ('smtp_host', 'smtp.example.com', 'string', 'mail', 'SMTP服务器地址'),
        ('smtp_port', '587', 'number', 'mail', 'SMTP端口'),
        ('smtp_username', 'noreply@example.com', 'string', 'mail', 'SMTP用户名'),
        ('smtp_password', '', 'string', 'mail', 'SMTP密码'),
        ('smtp_encryption', 'tls', 'string', 'mail', 'SMTP加密方式'),
        ('mail_sender', 'noreply@example.com', 'string', 'mail', '发件人邮箱'),
        ('mail_sender_name', '金融练习靶场', 'string', 'mail', '发件人名称')");
    echo "✓ 系统配置\n\n";
    
    echo "=== 功能测试 ===\n\n";
    $passed = 0;
    $failed = 0;
    
    if (runTest($pdo, '验证 users 表存在 pay_password 字段', function($pdo) {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `users` LIKE 'pay_password'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            throw new Exception('users 表缺少 pay_password 字段');
        }
    })) $passed++; else $failed++;
    
    if (runTest($pdo, '验证 enterprises 表存在 pay_password 字段', function($pdo) {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `enterprises` LIKE 'pay_password'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            throw new Exception('enterprises 表缺少 pay_password 字段');
        }
    })) $passed++; else $failed++;
    
    if (runTest($pdo, '验证 loans 表存在 signature_image 字段', function($pdo) {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `loans` LIKE 'signature_image'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            throw new Exception('loans 表缺少 signature_image 字段');
        }
    })) $passed++; else $failed++;
    
    if (runTest($pdo, '验证密码加密功能', function($pdo) {
        $password = '123456';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        if (!password_verify($password, $hash)) {
            throw new Exception('密码加密/验证失败');
        }
    })) $passed++; else $failed++;
    
    if (runTest($pdo, '验证支付密码格式验证', function($pdo) {
        $validPwd = '123456';
        $invalidPwd1 = '12345';
        $invalidPwd2 = '1234567';
        $invalidPwd3 = 'abc123';
        
        if (!preg_match('/^\d{6}$/', $validPwd)) {
            throw new Exception('有效密码格式验证失败');
        }
        if (preg_match('/^\d{6}$/', $invalidPwd1)) {
            throw new Exception('短密码未被正确拒绝');
        }
        if (preg_match('/^\d{6}$/', $invalidPwd2)) {
            throw new Exception('长密码未被正确拒绝');
        }
        if (preg_match('/^\d{6}$/', $invalidPwd3)) {
            throw new Exception('包含字母的密码未被正确拒绝');
        }
    })) $passed++; else $failed++;
    
    if (runTest($pdo, '验证数据库更新操作', function($pdo) {
        $testUserId = 99999;
        $hash = password_hash('123456', PASSWORD_DEFAULT);
        
        $pdo->exec("INSERT INTO users (id, username, password, real_name, phone) VALUES ($testUserId, 'test_user_" . time() . "', '$hash', '测试用户', '13800138000')");
        $pdo->exec("UPDATE users SET pay_password = '$hash' WHERE id = $testUserId");
        
        $stmt = $pdo->prepare("SELECT pay_password FROM users WHERE id = ?");
        $stmt->execute([$testUserId]);
        $user = $stmt->fetch();
        if (empty($user['pay_password'])) {
            throw new Exception('支付密码更新失败');
        }
        
        $pdo->exec("DELETE FROM users WHERE id = $testUserId");
    })) $passed++; else $failed++;
    
    if (runTest($pdo, '验证企业数据库更新操作', function($pdo) {
        $testEntId = 99999;
        $hash = password_hash('654321', PASSWORD_DEFAULT);
        
        $pdo->exec("INSERT INTO enterprises (id, username, password, company_name, contact_phone) VALUES ($testEntId, 'test_ent_" . time() . "', '$hash', '测试企业', '13900139000')");
        $pdo->exec("UPDATE enterprises SET pay_password = '$hash' WHERE id = $testEntId");
        
        $stmt = $pdo->prepare("SELECT pay_password FROM enterprises WHERE id = ?");
        $stmt->execute([$testEntId]);
        $ent = $stmt->fetch();
        if (empty($ent['pay_password'])) {
            throw new Exception('企业支付密码更新失败');
        }
        
        $pdo->exec("DELETE FROM enterprises WHERE id = $testEntId");
    })) $passed++; else $failed++;
    
    echo "\n=== 测试结果汇总 ===\n";
    echo "通过: $passed\n";
    echo "失败: $failed\n";
    
    if ($failed === 0) {
        echo "\n✓ 所有测试通过！\n";
    } else {
        echo "\n✗ 有 $failed 个测试失败\n";
    }
    
    echo "\n=== 数据库初始化完成 ===\n";
    echo "管理员账号: admin\n";
    echo "管理员密码: password\n";
    
} catch (PDOException $e) {
    die('数据库初始化失败: ' . $e->getMessage() . "\n");
}
?>
