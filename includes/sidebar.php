<?php
/**
 * CRM Sidebar — Dashboard / Users / Roles / Company Settings
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
if (!function_exists('canAccess')) {
    require_once __DIR__ . '/permission_helper.php';
}

$sidebarActive = $sidebarActive ?? '';

if (!function_exists('getDashboardLogo')) {
    require_once __DIR__ . '/settings.php';
}
$sidebarLogoPath = function_exists('getLoginLogo') ? getLoginLogo() : getDashboardLogo();
if (function_exists('getDashboardLogo')) {
    $dashLogo = getDashboardLogo();
    if (function_exists('isCustomLogo') && isCustomLogo($sidebarLogoPath) === false && isCustomLogo($dashLogo)) {
        $sidebarLogoPath = $dashLogo;
    }
}
$sidebarLogoSrc = $sidebarLogoPath;
if ($sidebarLogoSrc && strpos($sidebarLogoSrc, 'http') !== 0 && strpos($sidebarLogoSrc, '/') !== 0) {
    $sidebarLogoSrc = app_url($sidebarLogoSrc);
}
$sidebarLogoFile = '';
if (!empty($sidebarLogoPath) && strpos((string) $sidebarLogoPath, 'http') !== 0) {
    $sidebarLogoFile = __DIR__ . '/../' . ltrim((string) $sidebarLogoPath, '/');
}
$sidebarLogoVer = (is_file($sidebarLogoFile) ? (int) @filemtime($sidebarLogoFile) : time());
if ($sidebarLogoSrc !== '') {
    $sidebarLogoSrc .= (strpos($sidebarLogoSrc, '?') === false ? '?' : '&') . 'v=' . $sidebarLogoVer;
}
$sidebarCompanyName = function_exists('getCompanyName') ? getCompanyName() : 'Ocean CRM';
?>

<aside class="app-sidebar app-sidebar--styled" id="appSidebar" aria-label="Sidebar navigation">
    <div class="sidebar-top">
        <a href="<?php echo app_url('dashboard.php'); ?>" class="sidebar-logo-link" title="<?php echo htmlspecialchars($sidebarCompanyName); ?>">
            <img src="<?php echo htmlspecialchars($sidebarLogoSrc); ?>"
                 alt="<?php echo htmlspecialchars($sidebarCompanyName); ?>"
                 class="sidebar-logo-img"
                 onerror="this.src='<?php echo app_url('assets/images/ocean-crm-logo.png'); ?>?v=<?php echo time(); ?>'">
            <span class="sidebar-company-name"><?php echo htmlspecialchars($sidebarCompanyName); ?></span>
        </a>
        <button type="button" class="sidebar-toggle" id="sidebarToggle" title="Hide / Show sidebar" aria-label="Toggle sidebar">
            <i class="fa-solid fa-angles-left"></i>
        </button>
    </div>

    <div class="sidebar-search-wrap">
        <label class="sidebar-search" for="sidebarMenuSearch">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search"
                   id="sidebarMenuSearch"
                   class="sidebar-search-input"
                   placeholder="Search menu…"
                   autocomplete="off"
                   spellcheck="false">
            <button type="button" class="sidebar-search-clear" id="sidebarMenuSearchClear" title="Clear" aria-label="Clear search" hidden>
                <i class="fa-solid fa-xmark"></i>
            </button>
        </label>
        <div class="sidebar-search-empty" id="sidebarSearchEmpty" hidden>No menu items found</div>
    </div>

    <div class="sidebar-scroll" id="sidebarScroll">
        <div class="sidebar-section">
            <div class="sidebar-section-title">Main</div>
            <nav class="sidebar-nav">
                <a href="<?php echo app_url('dashboard.php'); ?>"
                   class="sidebar-link <?php echo $sidebarActive === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-gauge-high"></i><span>Dashboard</span>
                </a>
                <?php if (isAdmin() || canAccess('inquiry', 'view')): ?>
                <a href="<?php echo app_url('inquiry/index.php'); ?>"
                   class="sidebar-link <?php echo $sidebarActive === 'inquiry' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-clipboard-question"></i><span>Inquiry</span>
                </a>
                <?php endif; ?>
                <?php if (isAdmin() || canAccess('customers', 'view')): ?>
                <a href="<?php echo app_url('customers/index.php'); ?>"
                   class="sidebar-link <?php echo $sidebarActive === 'customers' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-users"></i><span>Customers</span>
                </a>
                <?php endif; ?>
                <?php if (isAdmin() || canAccess('masters', 'view')): ?>
                <a href="<?php echo app_url('masters/index.php'); ?>"
                   class="sidebar-link <?php echo in_array($sidebarActive, ['hub', 'departments', 'designations', 'countries', 'states', 'cities', 'areas', 'inquiry_statuses', 'follow_up_statuses', 'inquiry_sources', 'customer_types', 'product_categories', 'product_subcategories', 'products'], true) ? 'active' : ''; ?>">
                    <i class="fa-solid fa-database"></i><span>Masters</span>
                </a>
                <?php endif; ?>
            </nav>
        </div>

        <?php if (isAdmin() || canAccess('masters', 'view')): ?>
        <?php
        if (!function_exists('getMastersConfig')) {
            require_once __DIR__ . '/masters_config.php';
        }
        $sidebarMasters = getMastersConfig();
        ?>
        <div class="sidebar-section">
            <div class="sidebar-section-title">Masters</div>
            <nav class="sidebar-nav">
                <a href="<?php echo app_url('masters/index.php'); ?>"
                   class="sidebar-link <?php echo $sidebarActive === 'hub' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-table-cells-large"></i><span>All Masters</span>
                </a>
                <?php foreach ($sidebarMasters as $sm): ?>
                <a href="<?php echo app_url('masters/' . $sm['folder'] . '/index.php'); ?>"
                   class="sidebar-link <?php echo $sidebarActive === $sm['key'] ? 'active' : ''; ?>">
                    <i class="fa-solid <?php echo htmlspecialchars($sm['icon']); ?>"></i>
                    <span><?php echo htmlspecialchars($sm['singular']); ?></span>
                </a>
                <?php endforeach; ?>
            </nav>
        </div>
        <?php endif; ?>

        <?php if (isAdmin()): ?>
        <div class="sidebar-section">
            <div class="sidebar-section-title">Administration</div>
            <nav class="sidebar-nav">
                <a href="<?php echo app_url('users/index.php'); ?>"
                   class="sidebar-link <?php echo $sidebarActive === 'staff_users' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-users-gear"></i><span>Users</span>
                </a>
                <a href="<?php echo app_url('roles/index.php'); ?>"
                   class="sidebar-link <?php echo $sidebarActive === 'roles' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user-shield"></i><span>Roles &amp; Access</span>
                </a>
                <a href="<?php echo app_url('company_settings.php'); ?>"
                   class="sidebar-link <?php echo $sidebarActive === 'settings' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-building"></i><span>Company Settings</span>
                </a>
                <a href="<?php echo app_url('dbsync.php'); ?>"
                   class="sidebar-link <?php echo $sidebarActive === 'dbsync' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-database"></i><span>DB Sync</span>
                </a>
            </nav>
        </div>
        <?php endif; ?>
    </div>

    <div class="sidebar-bottom">
        <a href="<?php echo app_url('logout.php'); ?>" class="sidebar-link sidebar-logout">
            <i class="fa-solid fa-right-from-bracket"></i><span>Logout</span>
        </a>
    </div>
</aside>
<script src="<?php echo app_url('assets/js/sidebar.js'); ?>"></script>
