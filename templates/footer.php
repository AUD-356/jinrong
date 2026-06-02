    </main>
    
    <footer class="footer mt-5 py-3 bg-light">
        <div class="container text-center">
            <span class="text-muted">© 2024 <?php echo SITE_NAME; ?> 版权所有</span>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="<?php echo isset($assets_path) ? $assets_path : ''; ?>/js/common.js"></script>
    <?php if (isset($extraScript)): ?>
    <?php echo $extraScript; ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
    <script>
        $(document).ready(function() {
            showToast('<?php echo addslashes($_SESSION['success']); ?>', 'success');
        });
    </script>
    <?php unset($_SESSION['success']); endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
    <script>
        $(document).ready(function() {
            showToast('<?php echo addslashes($_SESSION['error']); ?>', 'danger');
        });
    </script>
    <?php unset($_SESSION['error']); endif; ?>
</body>
</html>
