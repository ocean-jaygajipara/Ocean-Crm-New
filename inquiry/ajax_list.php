<?php
/**
 * Inquiry — AJAX list only (JSON for DataTables)
 * Used by index.php list. Not used by add/edit forms.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permission_helper.php';
require_once __DIR__ . '/../includes/inquiry_helper.php';

requireLogin();
requireAccess('inquiry', 'view');
ensureInquiryTables();

jsonResponse(inquiryAjaxListJson());
