<?php
/**
 * Inquiry — Soft delete (AJAX + redirect)
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permission_helper.php';
require_once __DIR__ . '/../includes/inquiry_helper.php';

requireLogin();
requireAccess('inquiry', 'delete');
ensureInquiryTables();

$ajax = isAjaxRequest();
$id = isset($_GET['id']) ? (int) $_GET['id'] : (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    if ($ajax) {
        jsonResponse(['ok' => false, 'message' => 'Invalid record.'], 400);
    }
    header('Location: ' . app_url('inquiry/index.php'));
    exit;
}

$row = getInquiryRow($id);
if (!$row || !canAccessInquiryRecord($row)) {
    if ($ajax) {
        jsonResponse(['ok' => false, 'message' => 'Access denied.'], 403);
    }
    header('Location: ' . app_url('inquiry/index.php'));
    exit;
}

$ok = softDeleteInquiry($id);

if ($ajax) {
    if (!$ok) {
        jsonResponse(['ok' => false, 'message' => 'Delete failed. Record may already be deleted.'], 500);
    }
    jsonResponse([
        'ok'      => true,
        'message' => 'Inquiry deleted successfully.',
        'id'      => $id,
    ]);
}

header('Location: ' . app_url('inquiry/index.php?msg=deleted'));
exit;
