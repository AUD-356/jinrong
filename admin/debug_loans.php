<?php
// session_start();
// if (!isset($_SESSION['admin_id'])) {
//     die('Access denied');
// }

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

echo "<pre>";
echo "Checking loans table:\n";

$totalPersonal = dbGetOne("SELECT COUNT(*) FROM loans WHERE status = 'pending' AND user_type = 'personal'");
$totalEnterprise = dbGetOne("SELECT COUNT(*) FROM loans WHERE status = 'pending' AND user_type = 'enterprise'");
$pendingApplications = dbGetOne("SELECT COUNT(*) FROM applications WHERE type = 'loan' AND status = 'pending'");

echo "Pending personal loans: $totalPersonal\n";
echo "Pending enterprise loans: $totalEnterprise\n";
echo "Pending loan applications in applications table: $pendingApplications\n";

if ($totalPersonal > 0) {
    $loans = dbGetAll("SELECT l.id, l.receipt_no, l.amount, l.status, l.user_type, u.username FROM loans l LEFT JOIN users u ON l.user_id = u.id WHERE l.status = 'pending' AND l.user_type = 'personal' LIMIT 5");
    echo "\nSample personal loans:\n";
    foreach ($loans as $loan) {
        echo "- ID: {$loan['id']}, Receipt: {$loan['receipt_no']}, Amount: {$loan['amount']}, User: {$loan['username']}\n";
    }
}

if ($totalEnterprise > 0) {
    $loans = dbGetAll("SELECT l.id, l.receipt_no, l.amount, l.status, l.user_type, e.company_name FROM loans l LEFT JOIN enterprises e ON l.user_id = e.id WHERE l.status = 'pending' AND l.user_type = 'enterprise' LIMIT 5");
    echo "\nSample enterprise loans:\n";
    foreach ($loans as $loan) {
        echo "- ID: {$loan['id']}, Receipt: {$loan['receipt_no']}, Amount: {$loan['amount']}, Company: {$loan['company_name']}\n";
    }
}

echo "\nLoans table columns:\n";
$cols = dbGetAll("DESCRIBE loans");
foreach ($cols as $col) {
    echo "- {$col['Field']} ({$col['Type']})\n";
}

echo "\nApplications table columns:\n";
$cols = dbGetAll("DESCRIBE applications");
foreach ($cols as $col) {
    echo "- {$col['Field']} ({$col['Type']})\n";
}

echo "\nTotal loans in table: " . dbGetOne("SELECT COUNT(*) FROM loans") . "\n";
echo "</pre>";
?>