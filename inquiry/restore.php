<?php
/**
 * Inquiry — Restore soft-deleted (AJAX + redirect)
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

$ok = restoreInquiry($id);

if ($ajax) {
    if (!$ok) {
        jsonResponse(['ok' => false, 'message' => 'Restore failed. Record may already be active.'], 500);
    }
    jsonResponse([
        'ok'      => true,
        'message' => 'Inquiry restored successfully.',
        'id'      => $id,
    ]);
}

header('Location: ' . app_url('inquiry/index.php?msg=restored'));
exit;
