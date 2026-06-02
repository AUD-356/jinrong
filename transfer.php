<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: personal/transfer.php");
} elseif (isset($_SESSION['enterprise_id'])) {
    header("Location: enterprise/transfer.php");
} elseif (isset($_SESSION['admin_id'])) {
    header("Location: admin/transfers.php");
} else {
    header("Location: index.php");
}
exit;
?>