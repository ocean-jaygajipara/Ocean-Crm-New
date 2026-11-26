<?php
/**
 * Inquiry — Convert to Customer
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permission_helper.php';
require_once __DIR__ . '/../includes/inquiry_helper.php';
require_once __DIR__ . '/../includes/customer_helper.php';

requireLogin();
ensureInquiryTables();
ensureCustomerTables();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ' . app_url('inquiry/index.php'));
    exit;
}

if (!(canAccess('customers', 'add') || canAccess('inquiry', 'edit'))) {
    requireAccess('customers', 'add');
}

$customerTypeId = isset($_GET['customer_type_id']) ? (int) $_GET['customer_type_id'] : 0;
$result = convertInquiryToCustomer($id, (int) ($_SESSION['user_id'] ?? 0), $customerTypeId);
if (!$result['ok']) {
    $_SESSION['inquiry_form_error'] = (string) ($result['error'] ?? 'Convert failed.');
    header('Location: ' . app_url('inquiry/view.php?id=' . $id));
    exit;
}

$msg = !empty($result['already']) ? 'already_customer' : 'converted';
header('Location: ' . app_url('customers/view.php?id=' . (int) $result['customer_id'] . '&msg=' . $msg));
exit;
