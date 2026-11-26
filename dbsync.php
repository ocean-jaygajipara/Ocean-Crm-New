<?php
/**
 * Ocean CRM — DB Sync panel
 * Manage / create / upgrade all SQL tables from one place.
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/dbsync.php';
require_once __DIR__ . '/includes/master_helper.php';

requireLogin();
if (!isAdmin()) {
    http_response_code(403);
    die('Admin access required.');
}

$runLog = [];
$synced = false;
$message = '';
$messageType = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'sync');
    if ($action === 'sync') {
        $withSeed = !empty($_POST['with_seed']);
        $result = syncDatabase(null, $withSeed);
        $runLog = $result['log'];
        $synced = true;
        $message = $result['ok'] ? 'Database sync completed.' : 'Sync finished with errors.';
        $messageType = $result['ok'] ? 'ok' : 'error';
    } elseif ($action === 'drop_extra') {
        $runLog = dropExtraDbTables();
        $synced = true;
        $message = 'Extra tables cleanup done.';
        $messageType = 'ok';
    }
}

$status = getDbSyncStatus();
$pageTitle = 'DB Sync';
$useSidebar = true;
$sidebarActive = 'dbsync';
require_once __DIR__ . '/includes/header.php';
?>
<main class="dashboard-main">
    <div class="page-toolbar flex-between">
        <a href="<?php echo htmlspecialchars(app_url('dashboard.php')); ?>" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div class="list-header">
        <div class="master-list-title">
            <div class="master-list-icon" style="background:#2466FB;">
                <i class="fa-solid fa-database"></i>
            </div>
            <div>
                <h1>DB Sync</h1>
                <p>All table CREATE / ALTER / seed managed here</p>
            </div>
        </div>
    </div>

    <?php if ($message !== ''): ?>
        <div class="data-card data-card-pad" style="margin-bottom:14px;border-left:4px solid <?php echo $messageType === 'ok' ? '#16a34a' : '#dc2626'; ?>;">
            <strong><?php echo htmlspecialchars($message); ?></strong>
        </div>
    <?php endif; ?>

    <div class="data-card data-card-pad" style="margin-bottom:14px;">
        <form method="post" class="crm-filter-bar" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
            <input type="hidden" name="action" value="sync">
            <label style="display:inline-flex;align-items:center;gap:8px;font-weight:600;color:#334155;">
                <input type="checkbox" name="with_seed" value="1" checked>
                Run master seed (if empty)
            </label>
            <button type="submit" class="btn-primary">
                <i class="fa-solid fa-rotate"></i> Sync Database
            </button>
        </form>
        <form method="post" style="margin-top:10px;" onsubmit="return confirm('Drop tables that are NOT in the CRM schema?');">
            <input type="hidden" name="action" value="drop_extra">
            <button type="submit" class="btn-secondary">
                <i class="fa-solid fa-trash"></i> Drop Extra Tables
            </button>
        </form>
        <p style="margin:12px 0 0;color:#64748b;font-size:13px;">
            Schema source: <code>includes/dbsync.php</code>
        </p>
    </div>

    <div class="data-card data-card-pad">
        <h3 style="margin:0 0 12px;">Tables</h3>
        <div class="table-wrap">
            <table class="data-table" style="width:100%">
                <thead>
                    <tr>
                        <th>Table</th>
                        <th>Status</th>
                        <th class="text-end">Rows</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($status as $row): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($row['table']); ?></strong>
                                <?php if (!empty($row['extra'])): ?>
                                    <span class="crm-status-badge is-deleted">Extra</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($row['exists'])): ?>
                                    <span style="color:#16a34a;font-weight:700;">Exists</span>
                                <?php else: ?>
                                    <span style="color:#dc2626;font-weight:700;">Missing</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><?php echo $row['rows'] === null ? '—' : (int) $row['rows']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($synced && !empty($runLog)): ?>
        <div class="data-card data-card-pad" style="margin-top:14px;">
            <h3 style="margin:0 0 12px;">Sync Log</h3>
            <ul style="margin:0;padding-left:18px;line-height:1.7;">
                <?php foreach ($runLog as $line): ?>
                    <li style="color:<?php echo ($line['type'] ?? '') === 'error' ? '#dc2626' : '#334155'; ?>;">
                        <?php echo htmlspecialchars($line['msg'] ?? ''); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
