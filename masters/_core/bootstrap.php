<?php
/**
 * Masters PHP Core — shared bootstrap
 * Set $masterKey before requiring this file.
 */

if (!isset($masterKey) || $masterKey === '') {
    http_response_code(500);
    die('Master key not set.');
}

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/master_helper.php';
require_once __DIR__ . '/../../includes/permission_helper.php';

requireLogin();
requireAccess('masters', 'view');

$master = getMasterConfig($masterKey);
if (!$master) {
    http_response_code(404);
    die('Master not found.');
}

ensureMasterTables();

$masterBase    = 'masters/' . $master['folder'];
$masterListUrl = app_url($masterBase . '/index.php');
$masterAddUrl  = app_url($masterBase . '/edit.php');
$masterSaveUrl = app_url($masterBase . '/save.php');
$masterAjaxUrl = app_url($masterBase . '/ajax_list.php');
$mastersHubUrl = app_url('masters/index.php');

$canMasterAdd    = canAccess('masters', 'add');
$canMasterEdit   = canAccess('masters', 'edit');
$canMasterDelete = canAccess('masters', 'delete'); // also used for restore
