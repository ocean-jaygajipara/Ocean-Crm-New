<?php
/**
 * Inquiry — Save (add / update)
 * Normal form POST (no AJAX). Redirects back to list / form.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permission_helper.php';
require_once __DIR__ . '/../includes/inquiry_helper.php';

requireLogin();
ensureInquiryTables();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . app_url('inquiry/index.php'));
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id > 0) {
    requireAccess('inquiry', 'edit');
} else {
    requireAccess('inquiry', 'add');
}

$userId = (int) ($_SESSION['user_id'] ?? 0);
$result = saveInquiry($_POST, $id, $userId);
if (!empty($result['ok'])) {
    unset($_SESSION['inquiry_form_flash'], $_SESSION['inquiry_form_error']);
    $msg = $id > 0 ? 'updated' : 'added';
    header('Location: ' . app_url('inquiry/index.php?msg=' . $msg));
    exit;
}

$_SESSION['inquiry_form_error'] = (string) ($result['error'] ?? 'Save failed');
$_SESSION['inquiry_form_flash'] = $_POST;
$back = $id > 0
    ? app_url('inquiry/edit.php?id=' . $id)
    : app_url('inquiry/add.php');
header('Location: ' . $back);
exit;
