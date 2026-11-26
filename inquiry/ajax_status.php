<?php
/**
 * Inquiry — quick status update from list grid (AJAX)
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permission_helper.php';
require_once __DIR__ . '/../includes/inquiry_helper.php';

requireLogin();
requireAccess('inquiry', 'edit');
ensureInquiryTables();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'message' => 'Invalid method.'], 405);
}

$id = (int) ($_POST['id'] ?? 0);
$field = trim((string) ($_POST['field'] ?? ''));
$statusId = (int) ($_POST['status_id'] ?? 0);

if (!in_array($field, ['inquiry_status_id', 'follow_up_status_id'], true)) {
    jsonResponse(['ok' => false, 'message' => 'Invalid field.'], 400);
}

$result = updateInquiryStatusField($id, $field, $statusId);
if (empty($result['ok'])) {
    jsonResponse([
        'ok'      => false,
        'message' => (string) ($result['error'] ?? 'Update failed.'),
    ], 400);
}

jsonResponse([
    'ok'        => true,
    'message'   => $field === 'follow_up_status_id'
        ? 'Follow up status updated.'
        : 'Inquiry status updated.',
    'status_id' => (int) ($result['status_id'] ?? $statusId),
    'field'     => $field,
]);
