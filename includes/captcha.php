<?php

// 首先定义常量，防止重复包含
define('CAPTCHA_INCLUDED', true);

function generateCaptcha() {
    $code = '';
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    for ($i = 0; $i < 4; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    $_SESSION['captcha'] = strtolower($code);
    
    $width = 140;
    $height = 45;
    $image = imagecreatetruecolor($width, $height);
    
    $bgColor = imagecolorallocate($image, 248, 248, 255);
    imagefill($image, 0, 0, $bgColor);
    $lineColor = imagecolorallocate($image, 200, 220, 240);
    
    for ($i = 0; $i < 2; $i++) {
        $startX = rand(0, $width);
        $startY = rand(0, $height);
        $endX = rand(0, $width);
        $endY = rand(0, $height);
        imageline($image, $startX, $startY, $endX, $endY, $lineColor);
    }
    
    for ($i = 0; $i < 25; $i++) {
        imagesetpixel($image, rand(0, $width), rand(0, $height), $lineColor);
    }
    
    $fontSize = 24;
    $fontX = 15;
    $fontY = 32;
    
    $colorOptions = [
        imagecolorallocate($image, 60, 80, 180),
        imagecolorallocate($image, 180, 60, 80),
        imagecolorallocate($image, 80, 180, 100),
        imagecolorallocate($image, 180, 160, 60)
    ];
    
    $fontPaths = [
        'C:\\Windows\\Fonts\\arial.ttf',
        'C:\\Windows\\Fonts\\arialbd.ttf',
        'C:\\Windows\\Fonts\\simhei.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        '/usr/share/fonts/TTF/arial.ttf',
        __DIR__ . '/../assets/arial.ttf'
    ];
    
    $fontFile = null;
    foreach ($fontPaths as $path) {
        if (file_exists($path)) {
            $fontFile = $path;
            break;
        }
    }
    
    if ($fontFile) {
        for ($i = 0; $i < strlen($code); $i++) {
            $x = $fontX + $i * 28;
            $y = $fontY + rand(-2, 2);
            
            $color = $colorOptions[$i % count($colorOptions)];
            
            $angle = rand(-6, 6);
            
            imagettftext($image, $fontSize, $angle, $x, $y, $color, $fontFile, $code[$i]);
        }
    } else {
        for ($i = 0; $i < strlen($code); $i++) {
            $x = $fontX + $i * 28;
            $y = $fontY - 10;
            
            $color = $colorOptions[$i % count($colorOptions)];
            
            imagestring($image, 5, $x, $y, $code[$i], $color);
        }
    }
    
    ob_clean();
    header('Content-Type: image/png');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    imagepng($image);
    imagedestroy($image);
}

function verifyCaptcha($input) {
    if (!isset($_SESSION['captcha'])) {
        return false;
    }
    $result = strtolower($input) === $_SESSION['captcha'];
    unset($_SESSION['captcha']);
    return $result;
}

function refreshCaptcha() {
    generateCaptcha();
}

// 只有当直接访问文件时才生成验证码图片
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    error_reporting(0);
    ini_set('display_errors', 'Off');
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_secure' => false,
            'cookie_httponly' => true,
            'use_strict_mode' => false
        ]);
    }
    
    generateCaptcha();
    exit;
}
