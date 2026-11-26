<?php
/**
 * API — Current user profile (auth required)
 * Header: Authorization: Bearer <token>
 */
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/permission_helper.php';

$user = apiAuthUser(true);

$roleName = '';
$roleCode = '';
$roleId = (int) ($user['role_id'] ?? 0);
if ($roleId > 0) {
    $conn = getDBConnection();
    $rs = $conn->prepare('SELECT name, code FROM roles WHERE id = ? AND status = 1 LIMIT 1');
    $rs->bind_param('i', $roleId);
    $rs->execute();
    $rr = $rs->get_result()->fetch_assoc();
    $rs->close();
    $roleName = (string) ($rr['name'] ?? '');
    $roleCode = strtoupper(trim((string) ($rr['code'] ?? '')));
}

$apiRole = apiNormalizeLoginRole($user['api_role'] ?? '');
if ($apiRole === '') {
    $apiRole = apiMapUserToLoginRole($user, $roleName, $roleCode);
}

apiJson(true, 'OK', apiUserPayload($user, $apiRole, $roleName, $roleCode));
