<?php
/**
 * Masters PHP Core — Save (AJAX + redirect)
 */
require_once __DIR__ . '/bootstrap.php';

$ajax = isAjaxRequest();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($ajax) {
        jsonResponse(['ok' => false, 'message' => 'Invalid request method.'], 405);
    }
    header('Location: ' . $masterListUrl);
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
requireAccess('masters', $id > 0 ? 'edit' : 'add');

if ($id > 0) {
    $existing = getMasterRow($master['table'], $id);
    if (!$existing || (int) ($existing['status'] ?? 1) !== 1) {
        if ($ajax) {
            jsonResponse(['ok' => false, 'message' => 'Record is deleted. Restore it first.'], 422);
        }
        header('Location: ' . $masterListUrl);
        exit;
    }
}

$data = collectMasterPostData($master, $_POST);
$error = validateMasterData($master, $data);

if ($error !== '') {
    if ($ajax) {
        jsonResponse(['ok' => false, 'message' => $error], 422);
    }
    die(htmlspecialchars($error) . ' <a href="javascript:history.back()">Go Back</a>');
}

$result = saveMasterRow($master, $data, $id);

if (!$result['ok']) {
    $msg = 'Save failed: ' . ($result['error'] ?: 'Unknown error');
    if ($ajax) {
        jsonResponse(['ok' => false, 'message' => $msg], 500);
    }
    die(htmlspecialchars($msg) . ' <a href="javascript:history.back()">Go Back</a>');
}

$msgKey = $id > 0 ? 'updated' : 'added';
$msgText = $id > 0
    ? ($master['singular'] . ' updated successfully.')
    : ($master['singular'] . ' added successfully.');
$redirect = $masterListUrl . '?msg=' . $msgKey;

if ($ajax) {
    jsonResponse([
        'ok'       => true,
        'message'  => $msgText,
        'id'       => (int) ($result['id'] ?? 0),
        'redirect' => $redirect,
    ]);
}

header('Location: ' . $redirect);
exit;
