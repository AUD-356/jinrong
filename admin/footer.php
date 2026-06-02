<?php
if (!defined('IN_ADMIN')) {
    die('Access Denied');
}
?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (isset($extraScript)): ?>
    <?php echo $extraScript; ?>
    <?php endif; ?>
    <?php ob_end_flush(); ?>
</body>
</html>
