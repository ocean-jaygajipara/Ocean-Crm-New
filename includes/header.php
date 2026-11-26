<?php
/**
 * Common Header
 * Used on authenticated pages (dashboard etc.)
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/settings.php';
requireLogin();

$companyName = getCompanyName();
$companyLogo = getDashboardLogo();
$loginLogo = getLoginLogo();
// Prefer full brand mark (login logo) in header when available
if (isCustomLogo($loginLogo)) {
    $companyLogo = $loginLogo;
}
// Logo path may be relative - prefix with APP_BASE if needed
$logoSrc = $companyLogo;
if ($logoSrc && strpos($logoSrc, 'http') !== 0 && strpos($logoSrc, '/') !== 0) {
    $logoSrc = app_url($logoSrc);
}

$headerNotifyItems = [];
$headerNotifyUnread = 0;
$headerEmpPhotoUrl = '';
$headerEmpProfileUrl = '';
$staffLeaveApplyToasts = [];

try {
    require_once __DIR__ . '/notification_helper.php';
    ensureNotificationTables();
    $headerNotifyPack = fetchUserNotifications((int) ($_SESSION['user_id'] ?? 0), 15);
    $headerNotifyItems = $headerNotifyPack['items'];
    $headerNotifyUnread = (int) $headerNotifyPack['unread'];
} catch (Throwable $e) {
    $headerNotifyItems = [];
    $headerNotifyUnread = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo htmlspecialchars($companyName); ?></title>
    <?php $favVer = (int) @filemtime(__DIR__ . '/../assets/images/fav.png'); ?>
    <link rel="icon" type="image/png" href="<?php echo app_url('assets/images/fav.png'); ?>?v=<?php echo $favVer; ?>">
    <link rel="shortcut icon" type="image/png" href="<?php echo app_url('assets/images/fav.png'); ?>?v=<?php echo $favVer; ?>">
    <link rel="apple-touch-icon" href="<?php echo app_url('assets/images/fav.png'); ?>?v=<?php echo $favVer; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo app_url('assets/css/style.css'); ?>?v=<?php echo (int) @filemtime(__DIR__ . '/../assets/css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <?php if (!empty($extraCss) && is_array($extraCss)): ?>
        <?php foreach ($extraCss as $css): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars((strpos($css, 'http') === 0) ? $css : app_url($css)); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body<?php echo !empty($useSidebar) ? ' class="has-app-sidebar"' : ''; ?>>
<?php if (!empty($useSidebar)): ?>
    <div class="app-shell" id="appShell">
        <?php require_once __DIR__ . '/sidebar.php'; ?>
        <div class="app-content">
<?php endif; ?>
    <!-- Top Header Bar -->
    <header class="top-header anim-header">
        <div class="header-left">
            <?php if (!empty($useSidebar)): ?>
                <button type="button" class="header-sidebar-btn" id="headerSidebarBtn" title="Toggle sidebar" aria-label="Toggle sidebar">
                    <i class="fa-solid fa-bars"></i>
                </button>
            <?php else: ?>
                <a href="<?php echo app_url('dashboard.php'); ?>" class="brand">
                    <img src="<?php echo htmlspecialchars($logoSrc); ?>"
                         alt="<?php echo htmlspecialchars($companyName); ?>"
                         class="brand-logo"
                         onerror="this.src='<?php echo app_url('assets/images/ocean-crm-logo.png'); ?>'">
                    <span class="brand-text"><?php echo htmlspecialchars($companyName); ?></span>
                </a>
            <?php endif; ?>
        </div>
        <div class="header-right">
            <div class="header-status" id="headerStatus" aria-live="polite">
                <div class="header-status-item">
                    <i class="fa-regular fa-clock" aria-hidden="true"></i>
                    <span class="header-status-line">
                        Time : <strong class="header-clock" id="headerClock">--:--:-- --</strong>
                    </span>
                </div>
                <div class="header-status-divider" aria-hidden="true"></div>
                <div class="header-status-item">
                    <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                    <span class="header-status-line">
                        Day : <strong class="header-day" id="headerDay"><?php echo htmlspecialchars(date('l')); ?></strong>
                    </span>
                </div>
                <div class="header-status-divider" aria-hidden="true"></div>
                <div class="header-status-item">
                    <i class="fa-solid fa-user-check" aria-hidden="true"></i>
                    <span class="header-status-line">
                        Logged in as :
                        <strong class="header-login-user"><?php echo htmlspecialchars(getUserName()); ?></strong>
                        <?php if (!empty($_SESSION['username'])): ?>
                            <em class="header-login-handle">(@<?php echo htmlspecialchars((string) $_SESSION['username']); ?>)</em>
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <div class="header-notify" id="headerNotify"
                 data-ajax-url="<?php echo htmlspecialchars(app_url('notifications/ajax.php')); ?>">
                <button type="button"
                        class="header-bell-btn"
                        id="headerBellBtn"
                        aria-haspopup="true"
                        aria-expanded="false"
                        aria-controls="headerNotifyPanel"
                        title="Notifications">
                    <i class="fa-solid fa-bell"></i>
                    <span class="header-bell-badge" id="headerBellBadge"
                          <?php echo $headerNotifyUnread > 0 ? '' : 'hidden'; ?>>
                        <?php echo $headerNotifyUnread > 99 ? '99+' : (int) $headerNotifyUnread; ?>
                    </span>
                </button>
                <div class="header-notify-panel" id="headerNotifyPanel" hidden>
                    <div class="header-notify-head">
                        <strong>Notifications</strong>
                        <div class="header-notify-head-links">
                            <a href="#" id="headerNotifyMarkAll">Mark all read</a>
                        </div>
                    </div>
                    <div class="header-notify-body" id="headerNotifyBody">
                        <?php if (empty($headerNotifyItems)): ?>
                            <div class="header-notify-empty">No new notifications</div>
                        <?php else: ?>
                            <?php foreach ($headerNotifyItems as $n): ?>
                                <a class="header-notify-item<?php echo empty($n['is_read']) ? ' is-unread' : ''; ?>"
                                   href="<?php echo htmlspecialchars($n['link'] !== '' ? $n['link'] : '#'); ?>"
                                   data-id="<?php echo (int) $n['id']; ?>">
                                    <span class="header-notify-ico">
                                        <i class="fa-solid <?php echo ($n['type'] ?? '') === 'inquiry_added' ? 'fa-clipboard-question' : 'fa-bell'; ?>"></i>
                                    </span>
                                    <span class="header-notify-meta">
                                        <strong><?php echo htmlspecialchars($n['title']); ?></strong>
                                        <small><?php echo htmlspecialchars($n['message']); ?></small>
                                        <small class="header-notify-time"><?php echo htmlspecialchars($n['time_ago']); ?></small>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="user-info">
                <?php if ($headerEmpProfileUrl !== ''): ?>
                <a href="<?php echo htmlspecialchars($headerEmpProfileUrl); ?>"
                   class="user-avatar<?php echo $headerEmpPhotoUrl !== '' ? ' has-photo' : ''; ?>"
                   title="<?php echo htmlspecialchars(getUserName() . ' · My Profile'); ?>">
                    <?php if ($headerEmpPhotoUrl !== ''): ?>
                        <img src="<?php echo htmlspecialchars($headerEmpPhotoUrl); ?>" alt="">
                    <?php else: ?>
                        <i class="fa-solid fa-user"></i>
                    <?php endif; ?>
                </a>
                <?php else: ?>
                <div class="user-avatar" title="<?php echo htmlspecialchars(getUserName() . ' · ' . getUserRoleLabel()); ?>">
                    <i class="fa-solid fa-user"></i>
                </div>
                <?php endif; ?>
                <a href="<?php echo app_url('logout.php'); ?>" class="logout-btn" title="Logout">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            </div>
        </div>
    </header>
<?php // sidebar layout already opened above when $useSidebar ?>
