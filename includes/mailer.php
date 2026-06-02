<?php

/**
 * Native PHP Mailer - Pure PHP implementation without third-party dependencies
 */

function sendEmail($to, $subject, $body, $isHtml = true, $fromEmail = null, $fromName = null) {
    $configs = dbGetAll("SELECT config_key, config_value FROM system_configs WHERE group_name = 'mail'");
    $mailConfig = [];
    foreach ($configs as $config) {
        $mailConfig[$config['config_key']] = $config['config_value'];
    }

    $senderEmail = $fromEmail ?? ($mailConfig['mail_sender'] ?? 'noreply@example.com');
    $senderName = $fromName ?? ($mailConfig['mail_sender_name'] ?? SITE_NAME);

    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    if ($isHtml) {
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
    } else {
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    }
    $headers[] = 'From: ' . encodeMailHeader($senderName) . ' <' . $senderEmail . '>';
    $headers[] = 'Reply-To: ' . encodeMailHeader($senderName) . ' <' . $senderEmail . '>';
    $headers[] = 'X-Mailer: PHP/' . phpversion();
    $headers[] = 'X-Priority: 3';

    $subject = encodeMailHeader($subject);

    $success = @mail($to, $subject, $body, implode("\r\n", $headers));

    if ($success) {
        return ['success' => true, 'message' => '邮件发送成功'];
    } else {
        return ['success' => false, 'message' => '邮件发送失败'];
    }
}

function encodeMailHeader($string) {
    $string = trim($string);
    if (preg_match('/[\x80-\xff]/', $string)) {
        return '=?UTF-8?B?' . base64_encode($string) . '?=';
    }
    return $string;
}

function sendSmtpEmail($to, $subject, $body, $isHtml = true) {
    $configs = dbGetAll("SELECT config_key, config_value FROM system_configs WHERE group_name = 'mail'");
    $mailConfig = [];
    foreach ($configs as $config) {
        $mailConfig[$config['config_key']] = $config['config_value'];
    }

    $host = $mailConfig['smtp_host'] ?? 'localhost';
    $port = (int)($mailConfig['smtp_port'] ?? 25);
    $username = $mailConfig['smtp_username'] ?? '';
    $password = $mailConfig['smtp_password'] ?? '';
    $encryption = $mailConfig['smtp_encryption'] ?? 'none';
    $senderEmail = $mailConfig['mail_sender'] ?? 'noreply@example.com';
    $senderName = $mailConfig['mail_sender_name'] ?? SITE_NAME;

    $errno = 0;
    $errstr = '';

    if ($encryption === 'ssl') {
        $host = 'ssl://' . $host;
    }

    $socket = @fsockopen($host, $port, $errno, $errstr, 30);

    if (!$socket) {
        return ['success' => false, 'message' => '无法连接到邮件服务器: ' . $errstr];
    }

    $response = fgets($socket, 512);
    if (substr($response, 0, 3) !== '220') {
        fclose($socket);
        return ['success' => false, 'message' => '邮件服务器响应异常'];
    }

    $localhost = 'localhost';
    fwrite($socket, "EHLO $localhost\r\n");
    $response = fgets($socket, 512);

    if (substr($response, 0, 3) !== '250') {
        fwrite($socket, "HELO $localhost\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) !== '250') {
            fclose($socket);
            return ['success' => false, 'message' => 'SMTP握手失败'];
        }
    }

    while (substr($response, 3, 1) === '-') {
        $response = fgets($socket, 512);
    }

    if (!empty($username) && !empty($password)) {
        fwrite($socket, "AUTH LOGIN\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) !== '334') {
            fclose($socket);
            return ['success' => false, 'message' => '认证不被支持'];
        }

        fwrite($socket, base64_encode($username) . "\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) !== '334') {
            fclose($socket);
            return ['success' => false, 'message' => '用户名认证失败'];
        }

        fwrite($socket, base64_encode($password) . "\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) !== '235') {
            fclose($socket);
            return ['success' => false, 'message' => '密码认证失败'];
        }
    }

    fwrite($socket, "MAIL FROM:<$senderEmail>\r\n");
    $response = fgets($socket, 512);
    if (substr($response, 0, 3) !== '250') {
        fclose($socket);
        return ['success' => false, 'message' => '发件人地址被拒绝'];
    }

    fwrite($socket, "RCPT TO:<$to>\r\n");
    $response = fgets($socket, 512);
    if (substr($response, 0, 3) !== '250') {
        fclose($socket);
        return ['success' => false, 'message' => '收件人地址被拒绝'];
    }

    fwrite($socket, "DATA\r\n");
    $response = fgets($socket, 512);
    if (substr($response, 0, 3) !== '354') {
        fclose($socket);
        return ['success' => false, 'message' => '数据命令被拒绝'];
    }

    $contentType = $isHtml ? 'Content-Type: text/html; charset=UTF-8' : 'Content-Type: text/plain; charset=UTF-8';
    $subject = encodeMailHeader($subject);

    $message = "From: " . encodeMailHeader($senderName) . " <$senderEmail>\r\n";
    $message .= "To: $to\r\n";
    $message .= "Subject: $subject\r\n";
    $message .= "$contentType\r\n";
    $message .= "MIME-Version: 1.0\r\n";
    $message .= "\r\n";
    $message .= "$body\r\n";
    $message .= ".\r\n";

    fwrite($socket, $message);
    $response = fgets($socket, 512);
    if (substr($response, 0, 3) !== '250') {
        fclose($socket);
        return ['success' => false, 'message' => '邮件发送失败'];
    }

    fwrite($socket, "QUIT\r\n");
    fclose($socket);

    return ['success' => true, 'message' => '邮件发送成功'];
}

function sendInvoiceEmail($to, $invoiceData) {
    $subject = '您的发票申请已处理 - ' . SITE_NAME;

    $body = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
        <h2 style="color: #333;">发票申请处理通知</h2>
        <p>尊敬的用户，您好！</p>
        <p>您的发票申请已处理完成，详情如下：</p>
        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd;"><strong>发票抬头</strong></td>
                <td style="padding: 10px; border: 1px solid #ddd;">' . htmlspecialchars($invoiceData['title']) . '</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd;"><strong>发票金额</strong></td>
                <td style="padding: 10px; border: 1px solid #ddd;">¥' . formatMoney($invoiceData['amount']) . '</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd;"><strong>发票类型</strong></td>
                <td style="padding: 10px; border: 1px solid #ddd;">' . ($invoiceData['type'] === 'personal' ? '个人发票' : '企业发票') . '</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd;"><strong>处理状态</strong></td>
                <td style="padding: 10px; border: 1px solid #ddd;">' . getInvoiceStatusText($invoiceData['status']) . '</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd;"><strong>申请时间</strong></td>
                <td style="padding: 10px; border: 1px solid #ddd;">' . formatDate($invoiceData['created_at']) . '</td>
            </tr>
        </table>
        <p>如有任何疑问，请联系客服。</p>
        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
        <p style="color: #666; font-size: 12px;">此邮件由系统自动发送，请勿回复。</p>
    </div>';

    return sendEmail($to, $subject, $body);
}

function sendLoanApprovalEmail($to, $loanData) {
    $subject = '您的贷款申请已批准 - ' . SITE_NAME;

    $body = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
        <h2 style="color: #333;">贷款申请批准通知</h2>
        <p>尊敬的客户，您好！</p>
        <p>恭喜！您的贷款申请已通过审核，详情如下：</p>
        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd;"><strong>贷款金额</strong></td>
                <td style="padding: 10px; border: 1px solid #ddd;">¥' . formatMoney($loanData['amount']) . '</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd;"><strong>贷款期限</strong></td>
                <td style="padding: 10px; border: 1px solid #ddd;">' . $loanData['term'] . '个月</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd;"><strong>年利率</strong></td>
                <td style="padding: 10px; border: 1px solid #ddd;">' . $loanData['rate'] . '%</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd;"><strong>月还款额</strong></td>
                <td style="padding: 10px; border: 1px solid #ddd;">¥' . formatMoney($loanData['monthly_payment']) . '</td>
            </tr>
        </table>
        <p style="color: #e74c3c;">请尽快登录系统完成合同签署，签署后方可放款。</p>
        <p><a href="' . SITE_URL . '" style="display: inline-block; padding: 10px 20px; background: #007bff; color: #fff; text-decoration: none; border-radius: 5px;">立即签署合同</a></p>
        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
        <p style="color: #666; font-size: 12px;">此邮件由系统自动发送，请勿回复。</p>
    </div>';

    return sendEmail($to, $subject, $body);
}

function sendCardActivationEmail($to, $cardData) {
    $subject = '您的银行卡已开户成功 - ' . SITE_NAME;

    $body = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
        <h2 style="color: #333;">银行卡开户成功通知</h2>
        <p>尊敬的客户，您好！</p>
        <p>您的银行卡已开户成功，详情如下：</p>
        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd;"><strong>卡号</strong></td>
                <td style="padding: 10px; border: 1px solid #ddd;">' . maskBankCard($cardData['card_no']) . '</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd;"><strong>开户银行</strong></td>
                <td style="padding: 10px; border: 1px solid #ddd;">' . getBankName($cardData['bank_code']) . '</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd;"><strong>开户时间</strong></td>
                <td style="padding: 10px; border: 1px solid #ddd;">' . formatDate($cardData['created_at']) . '</td>
            </tr>
        </table>
        <p><a href="' . SITE_URL . '" style="display: inline-block; padding: 10px 20px; background: #28a745; color: #fff; text-decoration: none; border-radius: 5px;">查看详情</a></p>
        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
        <p style="color: #666; font-size: 12px;">此邮件由系统自动发送，请勿回复。</p>
    </div>';

    return sendEmail($to, $subject, $body);
}

function sendActivationEmail($to, $userData) {
    $subject = '您的账户已激活 - ' . SITE_NAME;

    $body = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
        <h2 style="color: #333;">账户激活成功</h2>
        <p>尊敬的用户，您好！</p>
        <p>您的账户已成功激活，详情如下：</p>
        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd;"><strong>用户类型</strong></td>
                <td style="padding: 10px; border: 1px solid #ddd;">' . ($userData['user_type'] === 'personal' ? '个人用户' : '企业用户') . '</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd;"><strong>激活时间</strong></td>
                <td style="padding: 10px; border: 1px solid #ddd;">' . formatDate($userData['activated_at']) . '</td>
            </tr>
        </table>
        <p>您现在可以正常使用系统所有功能。</p>
        <p><a href="' . SITE_URL . '" style="display: inline-block; padding: 10px 20px; background: #007bff; color: #fff; text-decoration: none; border-radius: 5px;">立即登录</a></p>
        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
        <p style="color: #666; font-size: 12px;">此邮件由系统自动发送，请勿回复。</p>
    </div>';

    return sendEmail($to, $subject, $body);
}

function sendPasswordResetEmail($to, $resetData) {
    $subject = '密码重置请求 - ' . SITE_NAME;

    $body = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
        <h2 style="color: #333;">密码重置请求</h2>
        <p>尊敬的用户，您好！</p>
        <p>我们收到了您的密码重置请求，请点击以下链接设置新密码：</p>
        <p><a href="' . $resetData['reset_link'] . '" style="display: inline-block; padding: 10px 20px; background: #007bff; color: #fff; text-decoration: none; border-radius: 5px;">重置密码</a></p>
        <p>或者复制以下链接到浏览器地址栏：</p>
        <p style="word-break: break-all;">' . $resetData['reset_link'] . '</p>
        <p>此链接将在 ' . $resetData['expires_in'] . ' 分钟后失效。</p>
        <p>如果您没有发起密码重置请求，请忽略此邮件。</p>
        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
        <p style="color: #666; font-size: 12px;">此邮件由系统自动发送，请勿回复。</p>
    </div>';

    return sendEmail($to, $subject, $body);
}
