<?php
/**
 * Authentication Helper Functions
 * Admin, HR, and Employee portal logins.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('navResolveBack')) {
    require_once __DIR__ . '/nav_helper.php';
}

/**
 * Check if user is logged in
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Force login - redirect if not logged in
 */
function requireLogin()
{
    if (!isLoggedIn()) {
        require_once __DIR__ . '/../config/app.php';
        header('Location: ' . app_url('index.php'));
        exit;
    }
    syncSessionAccessFromDb();
}

/**
 * Derive login type from role_id / employee_id (users.role column removed).
 * admin = no role_id | hr = has role_id | employee = linked employee_id
 */
function deriveUserType(array $user)
{
    if ((int) ($user['employee_id'] ?? 0) > 0) {
        return 'employee';
    }
    if ((int) ($user['role_id'] ?? 0) > 0) {
        return 'hr';
    }
    return 'admin';
}

/**
 * Keep session role_id / permissions in sync with DB (avoids stale Admin access).
 */
function syncSessionAccessFromDb()
{
    static $synced = false;
    if ($synced || !isLoggedIn()) {
        return;
    }
    $synced = true;

    $uid = (int) ($_SESSION['user_id'] ?? 0);
    if ($uid <= 0) {
        return;
    }

    require_once __DIR__ . '/../config/database.php';
    $conn = getDBConnection();
    $stmt = $conn->prepare(
        'SELECT id, role_id, employee_id, department_id FROM users WHERE id = ? AND status = 1 LIMIT 1'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$user) {
        return;
    }

    $roleId = (int) ($user['role_id'] ?? 0);
    $empId = (int) ($user['employee_id'] ?? 0);
    $_SESSION['role_id'] = $roleId;
    $_SESSION['employee_id'] = $empId;
    $_SESSION['department_id'] = $user['department_id'] ?? null;
    $_SESSION['role'] = deriveUserType($user);

    $roleName = '';
    $roleCode = '';
    if ($roleId > 0) {
        $rs = $conn->prepare('SELECT name, code FROM roles WHERE id = ? AND status = 1 LIMIT 1');
        $rs->bind_param('i', $roleId);
        $rs->execute();
        $rr = $rs->get_result()->fetch_assoc();
        $rs->close();
        $roleName = (string) ($rr['name'] ?? '');
        $roleCode = strtoupper(trim((string) ($rr['code'] ?? '')));
    } elseif ($_SESSION['role'] === 'admin') {
        $roleName = 'Admin';
        $roleCode = 'ADMIN';
    }
    $_SESSION['role_name'] = $roleName;
    $_SESSION['role_code'] = $roleCode;

    if ($roleId > 0) {
        if (!function_exists('loadUserPermissionsIntoSession')) {
            require_once __DIR__ . '/permission_helper.php';
        }
        loadUserPermissionsIntoSession($roleId, true);
    } else {
        $_SESSION['permissions'] = [];
        $_SESSION['permissions_role_id'] = 0;
    }
}

/**
 * Check if current user is Admin (full access — no CRM role_id)
 */
function isAdmin()
{
    if (!isLoggedIn()) {
        return false;
    }
    return (int) ($_SESSION['role_id'] ?? 0) <= 0 && (int) ($_SESSION['employee_id'] ?? 0) <= 0;
}

/**
 * Check if current user is HR / staff with CRM role
 */
function isHR()
{
    if (!isLoggedIn()) {
        return false;
    }
    return (int) ($_SESSION['role_id'] ?? 0) > 0 && (int) ($_SESSION['employee_id'] ?? 0) <= 0;
}

/**
 * Employee portal user (linked employee_id)
 */
function isEmployee()
{
    if (!isLoggedIn()) {
        return false;
    }
    return (int) ($_SESSION['employee_id'] ?? 0) > 0;
}

/**
 * Admin or HR (staff portal users — not employee)
 */
function isStaffUser()
{
    return isAdmin() || isHR();
}

/**
 * Any portal user: Admin, HR, or Employee with login
 */
function isPortalUser()
{
    return isAdmin() || isHR() || isEmployee();
}

/**
 * Force Admin role
 */
function requireAdmin()
{
    requireLogin();
    if (!isAdmin()) {
        require_once __DIR__ . '/../config/app.php';
        header('Location: ' . app_url('dashboard.php'));
        exit;
    }
}

/**
 * Force Admin / HR / Employee portal (module pages use requireAccess after this)
 */
function requireStaff()
{
    requireLogin();
    if (!isPortalUser()) {
        require_once __DIR__ . '/../config/app.php';
        header('Location: ' . app_url('index.php'));
        exit;
    }
}

/**
 * Get logged-in user display name
 */
function getUserName()
{
    return isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User';
}

/**
 * Get logged-in user role label
 */
function getUserRoleLabel()
{
    $name = trim((string) ($_SESSION['role_name'] ?? ''));
    if ($name !== '') {
        return strtoupper($name);
    }
    if (isAdmin()) {
        return 'ADMINISTRATOR';
    }
    if (isEmployee()) {
        return 'EMPLOYEE';
    }
    if (isHR()) {
        return 'STAFF';
    }
    return '';
}

/**
 * Linked employee id for employee portal users
 */
function getSessionEmployeeId()
{
    return (int) ($_SESSION['employee_id'] ?? 0);
}
