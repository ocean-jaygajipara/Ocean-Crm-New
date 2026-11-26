<?php
/**
 * Inquiry — AJAX list (JSON for DataTables)
 * DB: open via getDBConnection (shared), closed in jsonResponse.
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
