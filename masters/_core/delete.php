<?php
/**
 * Masters PHP Core — Soft Delete (AJAX + redirect)
 * Requires masters delete permission.
 */
require_once __DIR__ . '/bootstrap.php';

requireAccess('masters', 'delete');

$ajax = isAjaxRequest();
$id = isset($_GET['id']) ? (int) $_GET['id'] : (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    if ($ajax) {
        jsonResponse(['ok' => false, 'message' => 'Invalid record.'], 400);
    }
    header('Location: ' . $masterListUrl);
    exit;
}

$ok = softDeleteMasterRow($master['table'], $id);

if ($ajax) {
    if (!$ok) {
        jsonResponse(['ok' => false, 'message' => 'Delete failed. Record may already be deleted.'], 500);
    }
    jsonResponse([
        'ok'      => true,
        'message' => $master['singular'] . ' deleted successfully.',
        'id'      => $id,
    ]);
}

header('Location: ' . $masterListUrl . '?msg=deleted');
exit;
