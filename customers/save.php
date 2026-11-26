<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permission_helper.php';
require_once __DIR__ . '/../includes/customer_helper.php';

requireLogin();
ensureCustomerTables();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . app_url('customers/index.php'));
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
if ($id > 0) {
    requireAccess('customers', 'edit');
} else {
    requireAccess('customers', 'add');
}

$result = saveCustomer($_POST, $id, (int) ($_SESSION['user_id'] ?? 0));
if (!$result['ok']) {
    $_SESSION['customer_form_error'] = (string) ($result['error'] ?? 'Save failed.');
    $_SESSION['customer_form_flash'] = $_POST;
    $redir = $id > 0
        ? app_url('customers/edit.php?id=' . $id)
        : app_url('customers/add.php');
    header('Location: ' . $redir);
    exit;
}

$msg = $id > 0 ? 'updated' : 'added';
header('Location: ' . app_url('customers/index.php?msg=' . $msg));
exit;
