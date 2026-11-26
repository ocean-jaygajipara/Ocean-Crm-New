<?php
/**
 * Common Footer
 */
require_once __DIR__ . '/../config/app.php';
?>
<?php if (!empty($useSidebar)): ?>
        </div><!-- /.app-content -->
    </div><!-- /.app-shell -->
<?php endif; ?>
    <footer class="page-footer">
        <div class="footer-left">2026 &copy; Ocean CRM</div>
        <div class="footer-right">Designed &amp; Developed by <a href="https://www.oceaninfotech.co.in/" target="_blank" rel="noopener">Ocean Infotech</a></div>
    </footer>

    <!-- Delete confirmation popup -->
    <div id="confirmDeleteModal" class="confirm-modal" hidden aria-hidden="true" role="dialog" aria-labelledby="confirmDeleteTitle">
        <div class="confirm-modal-backdrop"></div>
        <div class="confirm-modal-box">
            <div class="confirm-modal-icon">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3 id="confirmDeleteTitle">Are you sure?</h3>
            <p class="confirm-modal-msg">Are you sure you want to delete this record?</p>
            <div class="confirm-modal-actions">
                <button type="button" class="btn-secondary confirm-cancel">Cancel</button>
                <button type="button" class="btn-primary confirm-yes">
                    <i class="fa-solid fa-trash"></i> Yes, Delete
                </button>
            </div>
        </div>
    </div>

    <script src="<?php echo app_url('assets/js/dashboard.js'); ?>?v=<?php echo (int) @filemtime(__DIR__ . '/../assets/js/dashboard.js'); ?>"></script>
    <script src="<?php echo app_url('assets/js/header_clock.js'); ?>?v=<?php echo (int) @filemtime(__DIR__ . '/../assets/js/header_clock.js'); ?>"></script>
    <script src="<?php echo app_url('assets/js/header_notify.js'); ?>?v=<?php echo (int) @filemtime(__DIR__ . '/../assets/js/header_notify.js'); ?>"></script>
    <script src="<?php echo app_url('assets/js/confirm_delete.js'); ?>?v=<?php echo (int) @filemtime(__DIR__ . '/../assets/js/confirm_delete.js'); ?>"></script>
    <?php if (!empty($useSidebar)): ?>
        <script src="<?php echo app_url('assets/js/sidebar.js'); ?>?v=<?php echo (int) @filemtime(__DIR__ . '/../assets/js/sidebar.js'); ?>"></script>
    <?php endif; ?>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script src="<?php echo app_url('assets/js/select2_init.js'); ?>?v=<?php echo (int) @filemtime(__DIR__ . '/../assets/js/select2_init.js'); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="<?php echo app_url('assets/js/date_format.js'); ?>?v=<?php echo (int) @filemtime(__DIR__ . '/../assets/js/date_format.js'); ?>"></script>
    <?php if (!empty($extraJs) && is_array($extraJs)): ?>
        <?php foreach ($extraJs as $js): ?>
            <?php
            if (strpos($js, 'jquery') !== false && strpos($js, 'dataTables') === false) {
                continue;
            }
            $src = (strpos($js, 'http') === 0) ? $js : app_url($js);
            if (strpos($js, 'http') !== 0 && strpos($js, '?') === false) {
                $local = __DIR__ . '/../' . ltrim(explode('?', $js)[0], '/');
                $src .= '?v=' . (int) @filemtime($local);
            }
            ?>
            <script src="<?php echo htmlspecialchars($src); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    <script>
        // Ensure every dropdown has Select2 after page scripts (filters/forms/DataTables)
        if (window.CrmSelect2 && typeof window.CrmSelect2.init === 'function') {
            window.CrmSelect2.init(document);
        }
    </script>
</body>
</html>
