<?php
/**
 * Add / Edit Staff User (Admin)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/permission_helper.php';

requireAdmin();
ensureRoleTables();
$employeeCodeAuto = isEmployeeCodeAuto();

$id = (int) ($_GET['id'] ?? 0);
$row = $id > 0 ? getStaffUserById($id) : null;
if ($id > 0 && !$row) {
    header('Location: ' . app_url('users/index.php?msg=error&err=' . rawurlencode('User not found.')));
    exit;
}

$formError = '';
$formData = [];
if (!empty($_SESSION['staff_user_form']) && is_array($_SESSION['staff_user_form'])) {
    $formError = (string) ($_SESSION['staff_user_form']['error'] ?? '');
    $formData = is_array($_SESSION['staff_user_form']['data'] ?? null) ? $_SESSION['staff_user_form']['data'] : [];
    unset($_SESSION['staff_user_form']);
}

$val = static function ($key, $fallback = '') use ($formData, $row) {
    if (array_key_exists($key, $formData)) {
        return (string) $formData[$key];
    }
    if ($row && array_key_exists($key, $row) && $row[$key] !== null) {
        return (string) $row[$key];
    }
    return (string) $fallback;
};

$statusVal = array_key_exists('status', $formData)
    ? (int) $formData['status']
    : ($row ? (int) $row['status'] : 1);
$roleId = array_key_exists('role_id', $formData)
    ? (int) $formData['role_id']
    : (int) ($row['role_id'] ?? 0);
$reportsToId = array_key_exists('reports_to', $formData)
    ? (int) $formData['reports_to']
    : (int) ($row['reports_to'] ?? 0);
$reportsToOptions = getReportsToOptions($id);
$roleOptions = fetchRoles(true);

$pageTitle = $row ? 'Edit Staff User' : 'Add Staff User';
$useSidebar = true;
$sidebarMode = 'workspace';
$sidebarActive = 'staff_users';
$extraJs = ['assets/js/case_force.js'];

require_once __DIR__ . '/../includes/header.php';
?>

<main class="dashboard-main">
    <div class="page-toolbar">
        <a href="<?php echo app_url('users/index.php'); ?>" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Back to Staff Users
        </a>
    </div>

    <div class="form-page-card">
        <div class="form-page-header">
            <div class="master-list-title">
                <div class="master-list-icon" style="background:#1D4ED8;">
                    <i class="fa-solid fa-user-gear"></i>
                </div>
                <div>
                    <h1><?php echo $row ? 'Edit Staff User' : 'Add Staff User'; ?></h1>
                    <p>Role access + team hierarchy (Reports To)</p>
                </div>
            </div>
        </div>

        <?php if ($formError !== ''): ?>
            <div class="alert alert-danger" style="margin-bottom:14px;"><?php echo htmlspecialchars($formError); ?></div>
        <?php endif; ?>

        <form method="POST" action="<?php echo app_url('users/save.php'); ?>" class="employee-form" autocomplete="off">
            <input type="hidden" name="id" value="<?php echo (int) $id; ?>">

            <div class="form-grid form-grid-2">
                <div class="form-group">
                    <label for="fullName">Full Name <span class="req">*</span></label>
                    <input type="text" name="full_name" id="fullName" class="form-control" required maxlength="100"
                           value="<?php echo htmlspecialchars($val('full_name')); ?>">
                </div>
                <div class="form-group">
                    <label for="employeeCode">
                        Employee Code <?php if (!$employeeCodeAuto): ?><span class="req">*</span><?php endif; ?>
                        <?php if ($employeeCodeAuto): ?>
                            <small class="form-help" style="display:inline;margin-left:6px;">Auto generated</small>
                        <?php else: ?>
                            <small class="form-help" style="display:inline;margin-left:6px;">Manual entry</small>
                        <?php endif; ?>
                    </label>
                    <?php if ($employeeCodeAuto): ?>
                        <input type="text" id="employeeCode" class="form-control is-readonly-soft"
                               value="<?php echo htmlspecialchars($val('employee_code')); ?>"
                               placeholder="<?php echo $id > 0 ? '' : 'Will generate on save'; ?>" readonly>
                    <?php else: ?>
                        <input type="text" name="employee_code" id="employeeCode" class="form-control" required maxlength="50"
                               value="<?php echo htmlspecialchars($val('employee_code')); ?>"
                               placeholder="e.g. EMP0001" style="text-transform:uppercase;">
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="username">Username <span class="req">*</span></label>
                    <input type="text" name="username" id="username" class="form-control" required maxlength="50"
                           value="<?php echo htmlspecialchars($val('username')); ?>" autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="password">Password <?php echo $id > 0 ? '' : '<span class="req">*</span>'; ?></label>
                    <input type="password" name="password" id="password" class="form-control" maxlength="100"
                           <?php echo $id > 0 ? '' : 'required'; ?>
                           placeholder="<?php echo $id > 0 ? 'Leave blank to keep current' : ''; ?>"
                           autocomplete="new-password">
                </div>
                <input type="hidden" name="department_id" value="0">
                <div class="form-group">
                    <label for="roleId">Role <span class="req">*</span></label>
                    <select name="role_id" id="roleId" class="form-control js-select2" required>
                        <option value="0" <?php echo $roleId <= 0 ? 'selected' : ''; ?>>
                            Admin (Full access)
                        </option>
                        <?php foreach ($roleOptions as $r): ?>
                            <option value="<?php echo (int) $r['id']; ?>" <?php echo $roleId === (int) $r['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string) $r['name']); ?>
                                <?php if (!empty($r['code'])): ?>
                                    (<?php echo htmlspecialchars((string) $r['code']); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Selected role na permissions mujab modules show thase.</small>
                </div>
                <div class="form-group">
                    <label for="reportsTo">Reports To</label>
                    <select name="reports_to" id="reportsTo" class="form-control js-select2">
                        <option value="0">— No manager —</option>
                        <?php foreach ($reportsToOptions as $rid => $rlabel): ?>
                            <option value="<?php echo (int) $rid; ?>" <?php echo $reportsToId === (int) $rid ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($rlabel); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Manager can view this user's inquiries (team data).</small>
                </div>
                <div class="form-group">
                    <label for="userStatus">Status</label>
                    <select name="status" id="userStatus" class="form-control js-select2">
                        <option value="1" <?php echo $statusVal === 1 ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo $statusVal === 0 ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="form-actions sticky-actions">
                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Save User
                </button>
                <a href="<?php echo app_url('users/index.php'); ?>" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
