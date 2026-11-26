<?php
/**
 * Masters PHP Core — List (client-side DataTable + filters)
 */
require_once __DIR__ . '/bootstrap.php';

$pageTitle = $master['title'];
$extraCss = [
    'https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css',
];
$useSidebar = true;
$sidebarMode = 'masters';
$sidebarActive = $master['key'];

require_once __DIR__ . '/../../includes/header.php';

$toastMsg = '';
if (isset($_GET['msg'])) {
    $map = [
        'added'    => $master['singular'] . ' added successfully.',
        'updated'  => $master['singular'] . ' updated successfully.',
        'deleted'  => $master['singular'] . ' deleted successfully.',
        'restored' => $master['singular'] . ' restored successfully.',
    ];
    $toastMsg = $map[$_GET['msg']] ?? '';
}

$colKeys = ['sr'];
foreach ($master['list_columns'] as $col) {
    $colKeys[] = $col['key'];
}
$colKeys[] = 'action';
$actionColIndex = count($colKeys) - 1;

$filters = $master['filters'] ?? [];

// Search first, then other filters; Status last (before Apply)
$searchFilters = [];
$otherFilters = [];
foreach ($filters as $f) {
    if (($f['name'] ?? '') === 'q') {
        $searchFilters[] = $f;
    } else {
        $otherFilters[] = $f;
    }
}
$filters = array_merge($searchFilters, $otherFilters);

if (!empty($canMasterDelete)) {
    $filters[] = [
        'name'    => 'record_status',
        'label'   => 'Status',
        'type'    => 'select',
        'options' => [
            'active'  => 'Active',
            'deleted' => 'Deleted',
            'all'     => 'All',
        ],
        'default' => 'active',
    ];
}

// Cascade option payloads for State / City / Product Subcategory selects
$filterCascade = [
    'states' => [],
    'cities' => [],
    'product_subcategories' => [],
];
foreach (getActiveMasterRows('states', 'name ASC') as $r) {
    $filterCascade['states'][] = [
        'id' => (int) $r['id'],
        'name' => (string) $r['name'],
        'country_id' => (int) ($r['country_id'] ?? 0),
    ];
}
foreach (getActiveMasterRows('cities', 'name ASC') as $r) {
    $filterCascade['cities'][] = [
        'id' => (int) $r['id'],
        'name' => (string) $r['name'],
        'state_id' => (int) ($r['state_id'] ?? 0),
    ];
}
foreach (getActiveMasterRows('product_subcategories', 'name ASC') as $r) {
    $filterCascade['product_subcategories'][] = [
        'id' => (int) $r['id'],
        'name' => (string) $r['name'],
        'product_category_id' => (int) ($r['product_category_id'] ?? 0),
    ];
}
?>
<main class="dashboard-main">
    <div class="page-toolbar flex-between">
        <a href="<?php echo htmlspecialchars($mastersHubUrl); ?>" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Back to Masters
        </a>
        <div class="toolbar-actions">
            <?php if (!empty($canMasterAdd)): ?>
            <a href="<?php echo htmlspecialchars($masterAddUrl); ?>" class="btn-primary">
                <i class="fa-solid fa-plus"></i> Add <?php echo htmlspecialchars($master['singular']); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="list-header">
        <div class="master-list-title">
            <div class="master-list-icon" style="background: <?php echo htmlspecialchars($master['color']); ?>;">
                <i class="fa-solid <?php echo htmlspecialchars($master['icon']); ?>"></i>
            </div>
            <div>
                <h1><?php echo htmlspecialchars($master['title']); ?></h1>
            </div>
        </div>
    </div>

    <?php if (!empty($filters)): ?>
    <div class="data-card data-card-pad crm-filter-card">
        <form id="masterFilterForm" class="crm-filter-bar" autocomplete="off">
            <div class="crm-row">
                <?php foreach ($filters as $f): ?>
                    <?php
                    $fname = $f['name'];
                    $flabel = $f['label'] ?? $fname;
                    $ftype = $f['type'] ?? 'text';
                    ?>
                    <div class="col-md-3">
                        <div class="form-group crm-filter-item">
                            <label for="flt_<?php echo htmlspecialchars($fname); ?>"><?php echo htmlspecialchars($flabel); ?></label>
                            <?php if ($ftype === 'select'): ?>
                                <?php
                                $opts = !empty($f['options']) && is_array($f['options'])
                                    ? $f['options']
                                    : (!empty($f['options_from']) ? getMasterSelectOptions($f['options_from']) : []);
                                $parent = $f['parent'] ?? '';
                                $parentKey = $f['parent_key'] ?? '';
                                $defaultVal = (string) ($f['default'] ?? '');
                                $isStatusFlt = ($fname === 'record_status');
                                ?>
                                <select id="flt_<?php echo htmlspecialchars($fname); ?>"
                                        name="<?php echo htmlspecialchars($fname); ?>"
                                        class="form-control js-master-filter"
                                        data-filter="<?php echo htmlspecialchars($fname); ?>"
                                        <?php if ($parent): ?>data-parent="<?php echo htmlspecialchars($parent); ?>"<?php endif; ?>
                                        <?php if ($parentKey): ?>data-parent-key="<?php echo htmlspecialchars($parentKey); ?>"<?php endif; ?>>
                                    <?php if (!$isStatusFlt): ?>
                                        <option value="">All</option>
                                    <?php endif; ?>
                                    <?php if (empty($parent)): ?>
                                        <?php foreach ($opts as $oid => $olabel): ?>
                                            <option value="<?php echo htmlspecialchars((string) $oid); ?>"
                                                <?php echo ($defaultVal !== '' && (string) $oid === $defaultVal) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars((string) $olabel); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            <?php else: ?>
                                <input type="text"
                                       id="flt_<?php echo htmlspecialchars($fname); ?>"
                                       name="<?php echo htmlspecialchars($fname); ?>"
                                       class="form-control js-master-filter"
                                       data-filter="<?php echo htmlspecialchars($fname); ?>"
                                       placeholder="<?php echo htmlspecialchars($f['placeholder'] ?? $flabel); ?>">
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="col-md-3 crm-filter-actions">
                    <button type="submit" class="btn-primary" id="masterFilterApply">
                        <i class="fa-solid fa-filter"></i> Apply
                    </button>
                    <button type="button" class="btn-secondary" id="masterFilterReset">
                        <i class="fa-solid fa-rotate-left"></i> Reset
                    </button>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="data-card data-card-pad">
        <div class="table-wrap">
            <table id="mastersTable" class="display data-table crm-dt-table nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>Sr.</th>
                        <?php foreach ($master['list_columns'] as $col): ?>
                            <th><?php echo htmlspecialchars($col['label']); ?></th>
                        <?php endforeach; ?>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</main>
<script>
    window.MASTER_TOAST_MSG = <?php echo json_encode($toastMsg); ?>;
    window.MASTER_AJAX_URL  = <?php echo json_encode($masterAjaxUrl); ?>;
    window.MASTER_ACTION_COL = <?php echo (int) $actionColIndex; ?>;
    window.MASTER_LABEL = <?php echo json_encode($master['singular']); ?>;
    window.MASTER_COL_KEYS = <?php echo json_encode($colKeys); ?>;
    window.MASTER_FILTER_CASCADE = <?php echo json_encode($filterCascade); ?>;
    window.MASTER_CAN_DELETE = <?php echo !empty($canMasterDelete) ? 'true' : 'false'; ?>;
</script>
<?php
$extraJs = [
    'https://code.jquery.com/jquery-3.7.1.min.js',
    'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js',
    'assets/js/masters_list.js',
];
require_once __DIR__ . '/../../includes/footer.php';
