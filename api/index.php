<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_stocks':
        $stocks = dbGetAll("SELECT * FROM stocks ORDER BY id LIMIT 50");
        echo json_encode(['success' => true, 'data' => $stocks]);
        break;
        
    case 'get_stock':
        $code = $_GET['code'] ?? '';
        $stock = dbGetRow("SELECT * FROM stocks WHERE code = ?", [$code]);
        if ($stock) {
            echo json_encode(['success' => true, 'data' => $stock]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Stock not found']);
        }
        break;
        
    case 'get_user_info':
        if (!isset($_SESSION['user_id']) && !isset($_SESSION['enterprise_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            exit;
        }
        
        if (isset($_SESSION['enterprise_id'])) {
            $user = dbGetRow("SELECT * FROM enterprises WHERE id = ?", [$_SESSION['enterprise_id']]);
            $cards = dbGetAll("SELECT * FROM cards WHERE user_id = ? AND user_type = 'enterprise' AND status = 'active'", [$_SESSION['enterprise_id']]);
        } else {
            $user = dbGetRow("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
            $cards = dbGetAll("SELECT * FROM cards WHERE user_id = ? AND user_type = 'personal' AND status = 'active'", [$_SESSION['user_id']]);
        }
        
        $totalBalance = 0;
        foreach ($cards as $card) {
            $totalBalance += $card['balance'];
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'user' => $user,
                'cards' => $cards,
                'total_balance' => $totalBalance
            ]
        ]);
        break;
        
    case 'check_loan':
        if (!isset($_SESSION['user_id']) && !isset($_SESSION['enterprise_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            exit;
        }
        
        $userId = $_SESSION['user_id'] ?? $_SESSION['enterprise_id'];
        $userType = isset($_SESSION['enterprise_id']) ? 'enterprise' : 'personal';
        
        $activeLoan = dbGetRow("SELECT * FROM loans WHERE user_id = ? AND user_type = ? AND status IN ('pending', 'approved', 'contract_signed')", 
            [$userId, $userType]);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'has_active_loan' => !empty($activeLoan),
                'loan' => $activeLoan
            ]
        ]);
        break;
        
    case 'get_system_config':
        $configs = dbGetAll("SELECT config_key, config_value FROM system_configs");
        $configArray = [];
        foreach ($configs as $config) {
            $configArray[$config['config_key']] = $config['config_value'];
        }
        echo json_encode(['success' => true, 'data' => $configArray]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
