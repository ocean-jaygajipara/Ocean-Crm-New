<?php
/**
 * Ocean CRM API — shared bootstrap (JSON)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Api-Token, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/dbsync.php';

/** Allowed login role enum for mobile/API */
function apiLoginRoleEnum()
{
    return ['admin', 'sales_person'];
}

/**
 * JSON response.
 * $extra keys (e.g. token) are merged after message and before data.
 */
function apiJson($status, $message, $data = null, $httpCode = 200, array $extra = [])
{
    http_response_code((int) $httpCode);
    $out = [
        'status'  => (bool) $status,
        'message' => (string) $message,
    ];
    foreach ($extra as $k => $v) {
        if ($k === 'status' || $k === 'ok' || $k === 'message' || $k === 'data') {
            continue;
        }
        $out[$k] = $v;
    }
    if ($data !== null) {
        $out['data'] = $data;
    }
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function apiReadInput()
{
    $data = [];
    if (!empty($_POST) && is_array($_POST)) {
        $data = $_POST;
    }
    $raw = file_get_contents('php://input');
    if (is_string($raw) && trim($raw) !== '') {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $data = array_merge($data, $json);
        } else {
            $parsed = [];
            parse_str($raw, $parsed);
            if (is_array($parsed) && $parsed !== []) {
                $data = array_merge($data, $parsed);
            }
        }
    }
    if (!empty($_GET) && is_array($_GET)) {
        // Prefer body/POST over GET for credentials; merge GET for optional flags
        foreach ($_GET as $k => $v) {
            if (!array_key_exists($k, $data)) {
                $data[$k] = $v;
            }
        }
    }
    return $data;
}

function apiParam($data, $key, $default = '')
{
    if (!is_array($data) || !array_key_exists($key, $data)) {
        return $default;
    }
    $v = $data[$key];
    if (is_string($v)) {
        return trim($v);
    }
    if (is_numeric($v) || is_bool($v)) {
        return $v;
    }
    return $default;
}

function apiNormalizeUsername($username)
{
    $username = trim((string) $username);
    if ($username === '') {
        return '';
    }
    if (function_exists('mb_strtoupper')) {
        return mb_strtoupper($username, 'UTF-8');
    }
    return strtoupper($username);
}

function apiNormalizeLoginRole($role)
{
    $role = strtolower(trim((string) $role));
    $role = str_replace([' ', '-'], '_', $role);
    if ($role === 'salesperson' || $role === 'sales') {
        $role = 'sales_person';
    }
    return $role;
}

function apiMapUserToLoginRole(array $user, $roleName = '', $roleCode = '')
{
    $roleId = (int) ($user['role_id'] ?? 0);
    // No CRM role_id = Admin (full access)
    if ($roleId <= 0) {
        return 'admin';
    }
    $code = strtoupper(trim((string) $roleCode));
    $name = strtolower(trim((string) $roleName));
    if ($code === 'SALES' || strpos($name, 'sales') !== false) {
        return 'sales_person';
    }
    // Any assigned CRM role → sales_person for API enum
    return 'sales_person';
}

function apiEnsureTokenTable($conn = null)
{
    if ($conn === null) {
        $conn = getDBConnection();
    }
    syncDatabase($conn, false);
}

function apiCreateToken($userId, $apiRole, $deviceInfo = '')
{
    $conn = getDBConnection();
    apiEnsureTokenTable($conn);
    $userId = (int) $userId;
    $apiRole = apiNormalizeLoginRole($apiRole);
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + (60 * 60 * 24 * 30)); // 30 days
    $deviceInfo = mb_substr(trim((string) $deviceInfo), 0, 255);

    // Optional: revoke older tokens for same user+role (keep latest session clean)
    $del = $conn->prepare('DELETE FROM api_tokens WHERE user_id = ? AND api_role = ?');
    $del->bind_param('is', $userId, $apiRole);
    $del->execute();
    $del->close();

    $stmt = $conn->prepare(
        'INSERT INTO api_tokens (user_id, token, api_role, device_info, expires_at)
         VALUES (?,?,?,?,?)'
    );
    $stmt->bind_param('issss', $userId, $token, $apiRole, $deviceInfo, $expiresAt);
    $ok = $stmt->execute();
    $err = $stmt->error;
    $stmt->close();
    if (!$ok) {
        return ['ok' => false, 'token' => '', 'expires_at' => '', 'error' => $err];
    }
    return ['ok' => true, 'token' => $token, 'expires_at' => $expiresAt, 'error' => ''];
}

function apiGetBearerToken()
{
    $header = '';
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $header = (string) $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $header = (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (!empty($_SERVER['HTTP_X_API_TOKEN'])) {
        return trim((string) $_SERVER['HTTP_X_API_TOKEN']);
    }
    if (preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
        return trim($m[1]);
    }
    $input = apiReadInput();
    $t = apiParam($input, 'token', '');
    return is_string($t) ? trim($t) : '';
}

function apiAuthUser($required = true)
{
    $token = apiGetBearerToken();
    if ($token === '') {
        if ($required) {
            apiJson(false, 'Unauthorized. Token required.', null, 401);
        }
        return null;
    }
    $conn = getDBConnection();
    apiEnsureTokenTable($conn);
    $stmt = $conn->prepare(
        'SELECT t.id AS token_id, t.token, t.api_role, t.expires_at,
                u.id, u.username, u.full_name, u.department_id, u.role_id, u.employee_id, u.status,
                u.employee_code
         FROM api_tokens t
         INNER JOIN users u ON u.id = t.user_id
         WHERE t.token = ? LIMIT 1'
    );
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || (int) ($row['status'] ?? 0) !== 1) {
        if ($required) {
            apiJson(false, 'Unauthorized. Invalid token.', null, 401);
        }
        return null;
    }
    if (!empty($row['expires_at']) && strtotime($row['expires_at']) < time()) {
        if ($required) {
            apiJson(false, 'Unauthorized. Token expired.', null, 401);
        }
        return null;
    }

    $tid = (int) $row['token_id'];
    $conn->query('UPDATE api_tokens SET last_used_at = NOW() WHERE id = ' . $tid);

    return $row;
}

function apiRevokeToken($token)
{
    $token = trim((string) $token);
    if ($token === '') {
        return false;
    }
    $conn = getDBConnection();
    apiEnsureTokenTable($conn);
    $stmt = $conn->prepare('DELETE FROM api_tokens WHERE token = ?');
    $stmt->bind_param('s', $token);
    $ok = $stmt->execute();
    $n = $stmt->affected_rows;
    $stmt->close();
    return $ok && $n > 0;
}

function apiUserPayload(array $user, $apiRole = '', $roleName = '', $roleCode = '')
{
    $roleId = (int) ($user['role_id'] ?? 0);
    $roleName = trim((string) $roleName);
    $roleCode = strtoupper(trim((string) $roleCode));

    // No role_id → Admin (full access)
    if ($roleId <= 0) {
        $roleName = $roleName !== '' ? $roleName : 'Admin';
        $roleCode = $roleCode !== '' ? $roleCode : 'ADMIN';
    }

    return [
        'id'            => (int) ($user['id'] ?? 0),
        'username'      => (string) ($user['username'] ?? ''),
        'full_name'     => (string) ($user['full_name'] ?? ''),
        'role_id'       => $roleId,
        'role_name'     => $roleName,
        'department_id' => (int) ($user['department_id'] ?? 0),
        'employee_code' => (string) ($user['employee_code'] ?? ''),
    ];
}
