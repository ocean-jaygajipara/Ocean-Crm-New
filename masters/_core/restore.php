<?php
/**
 * Masters PHP Core — Restore soft-deleted row (AJAX + redirect)
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

$ok = restoreMasterRow($master['table'], $id);

if ($ajax) {
    if (!$ok) {
        jsonResponse(['ok' => false, 'message' => 'Restore failed. Record may already be active.'], 500);
    }
    jsonResponse([
        'ok'      => true,
        'message' => $master['singular'] . ' restored successfully.',
        'id'      => $id,
    ]);
}

header('Location: ' . $masterListUrl . '?msg=restored');
exit;
