<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permission_helper.php';
require_once __DIR__ . '/../includes/customer_helper.php';
require_once __DIR__ . '/../includes/master_helper.php';

requireLogin();
requireAccess('customers', 'view');
header('Content-Type: application/json; charset=utf-8');
echo json_encode(customerAjaxListJson());
exit;
