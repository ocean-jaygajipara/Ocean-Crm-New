<?php
/**
 * CRM Dashboard
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permission_helper.php';

requireLogin();

$pageTitle = 'Dashboard';
$useSidebar = true;
$sidebarActive = 'dashboard';
require_once __DIR__ . '/includes/header.php';

$userLabel = function_exists('getUserName') ? getUserName() : 'User';
$hour = (int) date('G');
if ($hour < 12) {
    $greet = 'Good morning';
} elseif ($hour < 17) {
    $greet = 'Good afternoon';
} else {
    $greet = 'Good evening';
}

$userCount = 0;
try {
    $conn = getDBConnection();
    $res = $conn->query('SELECT COUNT(*) AS c FROM users WHERE status = 1');
    if ($res && ($row = $res->fetch_assoc())) {
        $userCount = (int) $row['c'];
    }
} catch (Throwable $e) {
    $userCount = 0;
}
?>

<main class="dashboard-main dash-workspace">
    <div class="dash-hero">
        <div class="dash-hero-copy">
            <p class="dash-eyebrow"><?php echo htmlspecialchars(strtoupper(getCompanyName())); ?> · CRM</p>
            <h1><?php echo htmlspecialchars($greet); ?>, <?php echo htmlspecialchars($userLabel); ?></h1>
            <p class="dash-sub">Welcome to Ocean CRM workspace.</p>
        </div>
    </div>

    <div class="dash-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-top:20px;">
        <a class="dash-card" href="<?php echo app_url('dashboard.php'); ?>" style="display:block;padding:18px;border-radius:14px;background:#fff;border:1px solid #e8edf3;text-decoration:none;color:inherit;">
            <div style="font-size:22px;color:#2466FB;margin-bottom:8px;"><i class="fa-solid fa-gauge-high"></i></div>
            <strong>Dashboard</strong>
            <p style="margin:6px 0 0;color:#64748b;font-size:13px;">CRM overview</p>
        </a>

        <?php if (isAdmin() || canAccess('inquiry', 'view')): ?>
        <a class="dash-card" href="<?php echo app_url('inquiry/index.php'); ?>" style="display:block;padding:18px;border-radius:14px;background:#fff;border:1px solid #e8edf3;text-decoration:none;color:inherit;">
            <div style="font-size:22px;color:#2466FB;margin-bottom:8px;"><i class="fa-solid fa-clipboard-question"></i></div>
            <strong>Inquiry</strong>
            <p style="margin:6px 0 0;color:#64748b;font-size:13px;">Customer inquiries</p>
        </a>
        <?php endif; ?>

        <?php if (isAdmin() || canAccess('masters', 'view')): ?>
        <a class="dash-card" href="<?php echo app_url('masters/index.php'); ?>" style="display:block;padding:18px;border-radius:14px;background:#fff;border:1px solid #e8edf3;text-decoration:none;color:inherit;">
            <div style="font-size:22px;color:#2466FB;margin-bottom:8px;"><i class="fa-solid fa-database"></i></div>
            <strong>Masters</strong>
            <p style="margin:6px 0 0;color:#64748b;font-size:13px;">Department, Designation, Location…</p>
        </a>
        <?php endif; ?>

        <?php if (isAdmin()): ?>
        <a class="dash-card" href="<?php echo app_url('users/index.php'); ?>" style="display:block;padding:18px;border-radius:14px;background:#fff;border:1px solid #e8edf3;text-decoration:none;color:inherit;">
            <div style="font-size:22px;color:#2466FB;margin-bottom:8px;"><i class="fa-solid fa-users-gear"></i></div>
            <strong>Users</strong>
            <p style="margin:6px 0 0;color:#64748b;font-size:13px;"><?php echo (int) $userCount; ?> active users</p>
        </a>
        <a class="dash-card" href="<?php echo app_url('roles/index.php'); ?>" style="display:block;padding:18px;border-radius:14px;background:#fff;border:1px solid #e8edf3;text-decoration:none;color:inherit;">
            <div style="font-size:22px;color:#2466FB;margin-bottom:8px;"><i class="fa-solid fa-user-shield"></i></div>
            <strong>Roles &amp; Access</strong>
            <p style="margin:6px 0 0;color:#64748b;font-size:13px;">Permissions setup</p>
        </a>
        <a class="dash-card" href="<?php echo app_url('company_settings.php'); ?>" style="display:block;padding:18px;border-radius:14px;background:#fff;border:1px solid #e8edf3;text-decoration:none;color:inherit;">
            <div style="font-size:22px;color:#2466FB;margin-bottom:8px;"><i class="fa-solid fa-building"></i></div>
            <strong>Company Settings</strong>
            <p style="margin:6px 0 0;color:#64748b;font-size:13px;">Logo &amp; company profile</p>
        </a>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
