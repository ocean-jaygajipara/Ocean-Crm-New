<?php
/**
 * CRM Dashboard — overview stats + recent inquiries + quick links
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permission_helper.php';
require_once __DIR__ . '/includes/inquiry_helper.php';

requireLogin();

$canInquiryView = isAdmin() || canAccess('inquiry', 'view');
$canInquiryAdd = isAdmin() || canAccess('inquiry', 'add');
$canMasters = isAdmin() || canAccess('masters', 'view');
$canMastersAdd = isAdmin() || canAccess('masters', 'add');
$canUsers = isAdmin() || canAccess('users', 'view');
$canRoles = isAdmin() || canAccess('roles', 'view');

if (!function_exists('getMastersConfig')) {
    require_once __DIR__ . '/includes/masters_config.php';
}
$masterShortcuts = $canMasters ? getMastersConfig() : [];

$stats = [
    'total'       => 0,
    'today'       => 0,
    'month'       => 0,
    'open'        => 0,
    'follow_up'   => 0,
    'users'       => 0,
    'by_status'   => [],
    'recent'      => [],
];

try {
    $conn = getDBConnection();
    if ($canInquiryView) {
        ensureInquiryTables($conn);
    }

    $scopeSql = '';
    $scopeTypes = '';
    $scopeParams = [];
    if ($canInquiryView && !isAdmin()) {
        $scopeIds = getInquiryScopeUserIds((int) ($_SESSION['user_id'] ?? 0));
        if (empty($scopeIds)) {
            $scopeIds = [(int) ($_SESSION['user_id'] ?? 0)];
        }
        $ph = implode(',', array_fill(0, count($scopeIds), '?'));
        $scopeSql = " AND (created_by IN ({$ph}) OR assign_to IN ({$ph}))";
        $scopeTypes = str_repeat('i', count($scopeIds) * 2);
        foreach ($scopeIds as $sid) {
            $scopeParams[] = (int) $sid;
        }
        foreach ($scopeIds as $sid) {
            $scopeParams[] = (int) $sid;
        }
    }

    $runCount = static function (mysqli $conn, $sql, $types = '', array $params = []) {
        if ($types === '' || empty($params)) {
            $res = $conn->query($sql);
            if ($res && ($row = $res->fetch_assoc())) {
                return (int) ($row['c'] ?? 0);
            }
            return 0;
        }
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int) ($row['c'] ?? 0);
    };

    if ($canInquiryView) {
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');

        $stats['total'] = $runCount(
            $conn,
            'SELECT COUNT(*) AS c FROM inquiries WHERE status = 1' . $scopeSql,
            $scopeTypes,
            $scopeParams
        );
        $stats['today'] = $runCount(
            $conn,
            'SELECT COUNT(*) AS c FROM inquiries WHERE status = 1 AND inquiry_date = ?' . $scopeSql,
            's' . $scopeTypes,
            array_merge([$today], $scopeParams)
        );
        $stats['month'] = $runCount(
            $conn,
            'SELECT COUNT(*) AS c FROM inquiries WHERE status = 1 AND inquiry_date >= ?' . $scopeSql,
            's' . $scopeTypes,
            array_merge([$monthStart], $scopeParams)
        );
        $stats['follow_up'] = $runCount(
            $conn,
            'SELECT COUNT(*) AS c FROM inquiries
             WHERE status = 1 AND follow_up_date IS NOT NULL AND follow_up_date <= ?' . $scopeSql,
            's' . $scopeTypes,
            array_merge([$today], $scopeParams)
        );

        // Status breakdown
        $statusSql = 'SELECT inquiry_status_id, COUNT(*) AS c
                      FROM inquiries WHERE status = 1' . $scopeSql . '
                      GROUP BY inquiry_status_id';
        if ($scopeTypes !== '') {
            $st = $conn->prepare($statusSql);
            if ($st) {
                $st->bind_param($scopeTypes, ...$scopeParams);
                $st->execute();
                $sr = $st->get_result();
                while ($row = $sr->fetch_assoc()) {
                    $stats['by_status'][(int) $row['inquiry_status_id']] = (int) $row['c'];
                }
                $st->close();
            }
        } else {
            $sr = $conn->query($statusSql);
            if ($sr) {
                while ($row = $sr->fetch_assoc()) {
                    $stats['by_status'][(int) $row['inquiry_status_id']] = (int) $row['c'];
                }
            }
        }

        $statusRows = getInquiryStatusRows();
        $convertedIds = [];
        $closedIds = [];
        foreach ($statusRows as $sid => $srow) {
            $n = strtolower((string) ($srow['name'] ?? ''));
            if (strpos($n, 'convert') !== false || strpos($n, 'won') !== false) {
                $convertedIds[] = (int) $sid;
            }
            if (strpos($n, 'close') !== false || strpos($n, 'lost') !== false || strpos($n, 'cancel') !== false) {
                $closedIds[] = (int) $sid;
            }
        }
        $open = 0;
        foreach ($stats['by_status'] as $sid => $cnt) {
            if (!in_array((int) $sid, $convertedIds, true) && !in_array((int) $sid, $closedIds, true)) {
                $open += (int) $cnt;
            }
        }
        $stats['open'] = $open;

        // Recent inquiries
        $recentSql = 'SELECT id, inquiry_no, inquiry_date, customer_name, company_name, phone,
                             inquiry_status_id, follow_up_status_id
                      FROM inquiries
                      WHERE status = 1' . $scopeSql . '
                      ORDER BY inquiry_date DESC, id DESC
                      LIMIT 8';
        if ($scopeTypes !== '') {
            $rst = $conn->prepare($recentSql);
            if ($rst) {
                $rst->bind_param($scopeTypes, ...$scopeParams);
                $rst->execute();
                $rr = $rst->get_result();
                while ($row = $rr->fetch_assoc()) {
                    $stats['recent'][] = $row;
                }
                $rst->close();
            }
        } else {
            $rr = $conn->query($recentSql);
            if ($rr) {
                while ($row = $rr->fetch_assoc()) {
                    $stats['recent'][] = $row;
                }
            }
        }
    }

    if (isAdmin()) {
        $ures = $conn->query('SELECT COUNT(*) AS c FROM users WHERE status = 1');
        if ($ures && ($urow = $ures->fetch_assoc())) {
            $stats['users'] = (int) ($urow['c'] ?? 0);
        }
    }
} catch (Throwable $e) {
    // keep zeros
}

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

$roleLabel = function_exists('getUserRoleLabel') ? getUserRoleLabel() : '';
$statusMap = $canInquiryView ? getInquiryStatusRows() : [];
?>

<main class="dashboard-main dash-workspace">
    <div class="dash-hero">
        <div class="dash-hero-copy">
            <p class="dash-eyebrow"><?php echo htmlspecialchars(strtoupper(getCompanyName())); ?> · CRM</p>
            <h1><?php echo htmlspecialchars($greet); ?>, <?php echo htmlspecialchars($userLabel); ?></h1>
            <p class="dash-sub">
                <?php if ($roleLabel !== ''): ?>
                    <?php echo htmlspecialchars($roleLabel); ?> ·
                <?php endif; ?>
                Today is <?php echo htmlspecialchars(date('l, d M Y')); ?>
            </p>
        </div>
        <?php if ($canInquiryView): ?>
        <div class="dash-hero-stats">
            <div class="dash-stat">
                <span class="dash-stat-num"><?php echo (int) $stats['total']; ?></span>
                <span class="dash-stat-lab">Total</span>
            </div>
            <div class="dash-stat">
                <span class="dash-stat-num"><?php echo (int) $stats['today']; ?></span>
                <span class="dash-stat-lab">Today</span>
            </div>
            <div class="dash-stat">
                <span class="dash-stat-num"><?php echo (int) $stats['open']; ?></span>
                <span class="dash-stat-lab">Open</span>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($canInquiryView): ?>
    <section class="dash-section">
        <div class="dash-kpi-grid">
            <a class="dash-kpi-card is-blue" href="<?php echo app_url('inquiry/index.php'); ?>">
                <div class="dash-kpi-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                <div class="dash-kpi-body">
                    <span class="dash-kpi-lab">Total Inquiries</span>
                    <strong class="dash-kpi-num"><?php echo (int) $stats['total']; ?></strong>
                </div>
            </a>
            <a class="dash-kpi-card is-sky" href="<?php echo app_url('inquiry/index.php?filter=today'); ?>">
                <div class="dash-kpi-icon"><i class="fa-solid fa-calendar-day"></i></div>
                <div class="dash-kpi-body">
                    <span class="dash-kpi-lab">Today</span>
                    <strong class="dash-kpi-num"><?php echo (int) $stats['today']; ?></strong>
                </div>
            </a>
            <a class="dash-kpi-card is-indigo" href="<?php echo app_url('inquiry/index.php'); ?>">
                <div class="dash-kpi-icon"><i class="fa-solid fa-calendar-check"></i></div>
                <div class="dash-kpi-body">
                    <span class="dash-kpi-lab">This Month</span>
                    <strong class="dash-kpi-num"><?php echo (int) $stats['month']; ?></strong>
                </div>
            </a>
            <a class="dash-kpi-card is-orange" href="<?php echo app_url('inquiry/index.php?filter=due_follow_up'); ?>">
                <div class="dash-kpi-icon"><i class="fa-solid fa-bell"></i></div>
                <div class="dash-kpi-body">
                    <span class="dash-kpi-lab">Due Follow Up</span>
                    <strong class="dash-kpi-num"><?php echo (int) $stats['follow_up']; ?></strong>
                </div>
            </a>
            <a class="dash-kpi-card is-teal" href="<?php echo app_url('inquiry/index.php'); ?>">
                <div class="dash-kpi-icon"><i class="fa-solid fa-folder-open"></i></div>
                <div class="dash-kpi-body">
                    <span class="dash-kpi-lab">Open Pipeline</span>
                    <strong class="dash-kpi-num"><?php echo (int) $stats['open']; ?></strong>
                </div>
            </a>
            <?php if (isAdmin()): ?>
            <a class="dash-kpi-card is-slate" href="<?php echo app_url('users/index.php'); ?>">
                <div class="dash-kpi-icon"><i class="fa-solid fa-users"></i></div>
                <div class="dash-kpi-body">
                    <span class="dash-kpi-lab">Active Users</span>
                    <strong class="dash-kpi-num"><?php echo (int) $stats['users']; ?></strong>
                </div>
            </a>
            <?php endif; ?>
        </div>
    </section>

    <?php if (!empty($statusMap)): ?>
    <section class="dash-section">
        <div class="list-header" style="margin-bottom:12px;">
            <div class="master-list-title">
                <div class="master-list-icon" style="background:#e08a0a;">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                <div>
                    <h1 style="font-size:18px;margin:0;">Inquiry by Status</h1>
                    <p style="margin:2px 0 0;">Active pipeline breakdown</p>
                </div>
            </div>
        </div>
        <div class="dash-status-grid">
            <?php foreach ($statusMap as $sid => $srow):
                $cnt = (int) ($stats['by_status'][$sid] ?? 0);
                $color = trim((string) ($srow['color'] ?? ''));
                if ($color !== '' && $color[0] !== '#') {
                    $color = '#' . $color;
                }
                $style = $color !== ''
                    ? 'style="--dash-status-color:' . htmlspecialchars($color) . ';"'
                    : '';
            ?>
            <a class="dash-status-card" <?php echo $style; ?>
               href="<?php echo app_url('inquiry/index.php?inquiry_status_id=' . (int) $sid); ?>">
                <span class="dash-status-dot"></span>
                <div class="dash-status-body">
                    <strong><?php echo htmlspecialchars((string) $srow['name']); ?></strong>
                    <span><?php echo $cnt; ?> inquir<?php echo $cnt === 1 ? 'y' : 'ies'; ?> · view list</span>
                </div>
                <em class="dash-status-count"><?php echo $cnt; ?></em>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    <?php endif; ?>

    <section class="dash-section">
        <div class="list-header" style="margin-bottom:12px;">
            <div class="master-list-title">
                <div class="master-list-icon" style="background:#c77708;">
                    <i class="fa-solid fa-bolt"></i>
                </div>
                <div>
                    <h1 style="font-size:18px;margin:0;">Shortcuts</h1>
                    <p style="margin:2px 0 0;">Open any module in one click</p>
                </div>
            </div>
        </div>

        <div class="dash-shortcut-block">
            <h3 class="dash-shortcut-title">Main Actions</h3>
            <div class="dash-shortcut-grid">
                <?php if ($canInquiryAdd): ?>
                <a class="dash-shortcut" href="<?php echo app_url('inquiry/add.php'); ?>">
                    <i class="fa-solid fa-plus"></i>
                    <span>Add Inquiry</span>
                </a>
                <?php endif; ?>
                <?php if ($canInquiryView): ?>
                <a class="dash-shortcut" href="<?php echo app_url('inquiry/index.php'); ?>">
                    <i class="fa-solid fa-clipboard-question"></i>
                    <span>All Inquiries</span>
                </a>
                <a class="dash-shortcut" href="<?php echo app_url('inquiry/index.php?filter=today'); ?>">
                    <i class="fa-solid fa-calendar-day"></i>
                    <span>Today's Inquiries</span>
                </a>
                <a class="dash-shortcut" href="<?php echo app_url('inquiry/index.php?filter=due_follow_up'); ?>">
                    <i class="fa-solid fa-bell"></i>
                    <span>Due Follow Up</span>
                </a>
                <?php endif; ?>
                <?php if ($canMasters): ?>
                <a class="dash-shortcut" href="<?php echo app_url('masters/index.php'); ?>">
                    <i class="fa-solid fa-table-cells-large"></i>
                    <span>All Masters</span>
                </a>
                <?php endif; ?>
                <?php if (isAdmin() || $canUsers): ?>
                <a class="dash-shortcut" href="<?php echo app_url('users/index.php'); ?>">
                    <i class="fa-solid fa-users-gear"></i>
                    <span>Users</span>
                </a>
                <?php if (isAdmin()): ?>
                <a class="dash-shortcut" href="<?php echo app_url('users/edit.php'); ?>">
                    <i class="fa-solid fa-user-plus"></i>
                    <span>Add User</span>
                </a>
                <?php endif; ?>
                <?php endif; ?>
                <?php if (isAdmin() || $canRoles): ?>
                <a class="dash-shortcut" href="<?php echo app_url('roles/index.php'); ?>">
                    <i class="fa-solid fa-user-shield"></i>
                    <span>Roles &amp; Access</span>
                </a>
                <?php endif; ?>
                <?php if (isAdmin()): ?>
                <a class="dash-shortcut" href="<?php echo app_url('company_settings.php'); ?>">
                    <i class="fa-solid fa-building"></i>
                    <span>Company Settings</span>
                </a>
                <a class="dash-shortcut" href="<?php echo app_url('dbsync.php'); ?>">
                    <i class="fa-solid fa-database"></i>
                    <span>DB Sync</span>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($canMasters && !empty($masterShortcuts)): ?>
        <div class="dash-shortcut-block" style="margin-top:16px;">
            <h3 class="dash-shortcut-title">Masters</h3>
            <div class="dash-shortcut-grid">
                <?php foreach ($masterShortcuts as $sm): ?>
                <a class="dash-shortcut" href="<?php echo app_url('masters/' . $sm['folder'] . '/index.php'); ?>">
                    <i class="fa-solid <?php echo htmlspecialchars($sm['icon']); ?>"></i>
                    <span><?php echo htmlspecialchars($sm['singular']); ?></span>
                </a>
                <?php endforeach; ?>
                <?php if ($canMastersAdd): ?>
                <a class="dash-shortcut is-add" href="<?php echo app_url('masters/products/edit.php'); ?>">
                    <i class="fa-solid fa-box-open"></i>
                    <span>Add Product</span>
                </a>
                <a class="dash-shortcut is-add" href="<?php echo app_url('masters/inquiry_sources/edit.php'); ?>">
                    <i class="fa-solid fa-plus"></i>
                    <span>Add Source</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </section>

    <?php if ($canInquiryView): ?>
    <section class="dash-section">
        <div class="list-header" style="margin-bottom:12px;">
            <div class="master-list-title">
                <div class="master-list-icon" style="background:#e08a0a;">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div>
                    <h1 style="font-size:18px;margin:0;">Recent Inquiries</h1>
                    <p style="margin:2px 0 0;">Latest customer inquiries</p>
                </div>
            </div>
            <a href="<?php echo app_url('inquiry/index.php'); ?>" class="btn-secondary" style="text-decoration:none;">
                View all <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="data-card crm-dt-shell dash-recent-card">
            <div class="table-wrap">
                <table class="data-table dash-recent-table">
                    <thead>
                        <tr>
                            <th>Inquiry No</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Follow Up</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($stats['recent'])): ?>
                        <tr>
                            <td colspan="7" class="text-muted" style="text-align:center;padding:28px;">
                                No inquiries yet.
                                <?php if ($canInquiryAdd): ?>
                                    <a href="<?php echo app_url('inquiry/add.php'); ?>">Add first inquiry</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($stats['recent'] as $r):
                            $rid = (int) $r['id'];
                            $dateShow = !empty($r['inquiry_date']) ? date('d-m-Y', strtotime($r['inquiry_date'])) : '—';
                        ?>
                        <tr>
                            <td><span class="code-badge crm-inq-no"><?php echo htmlspecialchars((string) $r['inquiry_no']); ?></span></td>
                            <td><span class="crm-date-cell"><?php echo htmlspecialchars($dateShow); ?></span></td>
                            <td>
                                <span class="crm-name-cell"><?php echo htmlspecialchars((string) $r['customer_name']); ?></span>
                                <?php if (!empty($r['company_name'])): ?>
                                    <div class="crm-company-cell" style="font-size:12px;font-weight:600;"><?php echo htmlspecialchars((string) $r['company_name']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($r['phone'])): ?>
                                    <span class="crm-phone-cell"><i class="fa-solid fa-phone"></i><?php echo htmlspecialchars((string) $r['phone']); ?></span>
                                <?php else: ?>
                                    <span class="crm-cell-empty">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo inquiryStatusBadge((int) ($r['inquiry_status_id'] ?? 0)); ?></td>
                            <td><?php echo followUpStatusBadge((int) ($r['follow_up_status_id'] ?? 0)); ?></td>
                            <td class="text-center">
                                <a href="<?php echo app_url('inquiry/edit.php?id=' . $rid); ?>" class="action-btn edit" title="Open">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
