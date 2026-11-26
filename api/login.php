<?php
/**
 * API — Login
 * POST: username, password, role (admin | sales_person)
 */
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/permission_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiJson(false, 'Method not allowed. Use POST.', null, 405);
}

$input = apiReadInput();
$usernameInput = (string) apiParam($input, 'username', '');
$password = (string) apiParam($input, 'password', '');
$roleInput = apiNormalizeLoginRole(apiParam($input, 'role', ''));
$deviceInfo = (string) apiParam($input, 'device_info', '');

$allowed = apiLoginRoleEnum();
if ($usernameInput === '' || $password === '') {
    apiJson(false, 'username and password are required.', [
        'required' => ['username', 'password', 'role'],
        'role_enum' => $allowed,
    ], 422);
}

if ($roleInput === '' || !in_array($roleInput, $allowed, true)) {
    apiJson(false, 'Invalid role. Allowed: admin, sales_person.', [
        'role_enum' => $allowed,
    ], 422);
}

$usernameKey = apiNormalizeUsername($usernameInput);
ensureRoleTables();
$conn = getDBConnection();
apiEnsureTokenTable($conn);

$stmt = $conn->prepare(
    'SELECT u.id, u.username, u.password, u.full_name, u.department_id, u.role_id,
            u.employee_id, u.status, u.employee_code
     FROM users u
     WHERE u.username = ? AND u.status = 1 LIMIT 1'
);
$stmt->bind_param('s', $usernameKey);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || (string) ($user['password'] ?? '') !== $password) {
    apiJson(false, 'Invalid username or password.', null, 401);
}

$roleName = '';
$roleCode = '';
if (!empty($user['role_id'])) {
    $rs = $conn->prepare('SELECT name, code FROM roles WHERE id = ? AND status = 1 LIMIT 1');
    $rid = (int) $user['role_id'];
    $rs->bind_param('i', $rid);
    $rs->execute();
    $rr = $rs->get_result()->fetch_assoc();
    $rs->close();
    $roleName = (string) ($rr['name'] ?? '');
    $roleCode = strtoupper(trim((string) ($rr['code'] ?? '')));
}

$userApiRole = apiMapUserToLoginRole($user, $roleName, $roleCode);
if ($userApiRole === '' || $userApiRole !== $roleInput) {
    apiJson(false, 'Selected role does not match this account.', [
        'requested_role' => $roleInput,
        'account_role'   => $userApiRole !== '' ? $userApiRole : 'unknown',
        'role_enum'      => $allowed,
    ], 403);
}

$tokenRes = apiCreateToken((int) $user['id'], $roleInput, $deviceInfo);
if (!$tokenRes['ok']) {
    apiJson(false, 'Login OK but failed to create token.', [
        'error' => $tokenRes['error'] ?? '',
    ], 500);
}

apiJson(
    true,
    'Login successful.',
    apiUserPayload($user, $roleInput, $roleName, $roleCode),
    200,
    [
        'token' => $tokenRes['token'],
    ]
);
