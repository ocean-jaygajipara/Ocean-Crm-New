<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permission_helper.php';
require_once __DIR__ . '/../includes/customer_helper.php';

requireLogin();
requireAccess('customers', 'delete');
ensureCustomerTables();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$ajax = isset($_GET['ajax']) && (string) $_GET['ajax'] === '1';
$ok = $id > 0 ? softDeleteCustomer($id) : false;

if ($ajax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => $ok,
        'message' => $ok ? 'Customer deleted.' : 'Delete failed.',
    ]);
    exit;
}

header('Location: ' . app_url('customers/index.php?msg=' . ($ok ? 'deleted' : 'error')));
exit;
