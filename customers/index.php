<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permission_helper.php';
require_once __DIR__ . '/../includes/customer_helper.php';
require_once __DIR__ . '/../includes/master_helper.php';

requireLogin();
requireAccess('customers', 'view');
ensureCustomerTables();
ensureMasterTables();

$canAdd = canAccess('customers', 'add');
$canDelete = canAccess('customers', 'delete');

$pageTitle = 'Customers';
$extraCss = [
    'https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css',
];
$useSidebar = true;
$sidebarActive = 'customers';
require_once __DIR__ . '/../includes/header.php';

$toastMsg = '';
if (isset($_GET['msg'])) {
    $map = [
        'added' => 'Customer added successfully.',
        'updated' => 'Customer updated successfully.',
        'deleted' => 'Customer deleted successfully.',
        'restored' => 'Customer restored successfully.',
        'converted' => 'Inquiry converted to customer successfully.',
        'already_customer' => 'This inquiry was already converted to a customer.',
    ];
    $toastMsg = $map[$_GET['msg']] ?? '';
}

$filterCascade = ['states' => [], 'cities' => [], 'areas' => []];
foreach (getActiveMasterRows('states', 'name ASC') as $r) {
    $filterCascade['states'][] = ['id' => (int) $r['id'], 'name' => (string) $r['name'], 'country_id' => (int) ($r['country_id'] ?? 0)];
}
foreach (getActiveMasterRows('cities', 'name ASC') as $r) {
    $filterCascade['cities'][] = ['id' => (int) $r['id'], 'name' => (string) $r['name'], 'state_id' => (int) ($r['state_id'] ?? 0)];
}
$countries = getMasterSelectOptions('countries');
$customerTypes = getCustomerTypeOptions();
?>
<main class="dashboard-main">
    <div class="page-toolbar flex-between">
        <a href="<?php echo app_url('dashboard.php'); ?>" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
        <div class="toolbar-actions crm-list-tools">
            <button type="button" class="crm-tool-btn" id="customerFilterToggle" title="Filters" aria-expanded="false" aria-controls="customerFilterCard">
                <i class="fa-solid fa-filter"></i>
            </button>
            <div class="crm-tool-dropdown" id="customerSettingsMenu">
                <button type="button" class="crm-tool-btn" id="customerSettingsBtn" title="Settings" aria-expanded="false" aria-haspopup="true">
                    <i class="fa-solid fa-gear"></i>
                </button>
                <div class="crm-tool-menu" role="menu" hidden>
                    <button type="button" class="crm-tool-menu-item" id="customerExportExcel" role="menuitem">
                        <i class="fa-solid fa-file-excel"></i> Export Excel
                    </button>
                    <button type="button" class="crm-tool-menu-item" id="customerPrintBtn" role="menuitem">
                        <i class="fa-solid fa-print"></i> Print
                    </button>
                </div>
            </div>
            <?php if ($canAdd): ?>
            <a href="<?php echo app_url('customers/add.php'); ?>" class="btn-primary">
                <i class="fa-solid fa-plus"></i> Add Customer
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="list-header">
        <div class="master-list-title">
            <div class="master-list-icon" style="background:#e08a0a;">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <h1>Customers</h1>
                <p>Customer master &amp; converted inquiries</p>
            </div>
        </div>
    </div>

    <div class="data-card data-card-pad crm-filter-card" id="customerFilterCard" hidden>
        <form id="customerFilterForm" class="crm-filter-bar" autocomplete="off">
            <div class="crm-row">
                <div class="col-md-3">
                    <div class="form-group crm-filter-item">
                        <label for="flt_q">Search</label>
                        <input type="text" id="flt_q" name="q" class="form-control" placeholder="No / Name / Phone / Email">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group crm-filter-item">
                        <label for="flt_customer_type_id">Customer Type</label>
                        <select id="flt_customer_type_id" name="customer_type_id" class="form-control js-select2">
                            <option value="">All</option>
                            <?php foreach ($customerTypes as $k => $v): ?>
                                <option value="<?php echo (int) $k; ?>"><?php echo htmlspecialchars($v); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group crm-filter-item">
                        <label for="flt_country_id">Country</label>
                        <select id="flt_country_id" name="country_id" class="form-control js-select2">
                            <option value="">All</option>
                            <?php foreach ($countries as $oid => $olabel): ?>
                                <option value="<?php echo (int) $oid; ?>"><?php echo htmlspecialchars((string) $olabel); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group crm-filter-item">
                        <label for="flt_state_id">State</label>
                        <select id="flt_state_id" name="state_id" class="form-control js-select2"></select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group crm-filter-item">
                        <label for="flt_city_id">City</label>
                        <select id="flt_city_id" name="city_id" class="form-control js-select2"></select>
                    </div>
                </div>
                <?php if ($canDelete): ?>
                <div class="col-md-3">
                    <div class="form-group crm-filter-item">
                        <label for="flt_record_status">Status</label>
                        <select id="flt_record_status" name="record_status" class="form-control js-select2">
                            <option value="active" selected>Active</option>
                            <option value="deleted">Deleted</option>
                            <option value="all">All</option>
                        </select>
                    </div>
                </div>
                <?php endif; ?>
                <div class="col-md-3 crm-filter-actions">
                    <button type="submit" class="btn-primary"><i class="fa-solid fa-filter"></i> Apply</button>
                    <button type="button" class="btn-secondary" id="customerFilterReset"><i class="fa-solid fa-rotate-left"></i> Reset</button>
                </div>
            </div>
        </form>
    </div>

    <div class="data-card data-card-pad crm-dt-shell">
        <div class="table-wrap crm-dt-wrap">
            <table id="customerTable" class="display data-table crm-dt-table" style="width:100%">
                <thead>
                    <tr>
                        <th>Sr.</th>
                        <th>Customer No</th>
                        <th>Type</th>
                        <th>Name</th>
                        <th>Company</th>
                        <th>Phone</th>
                        <th>City</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</main>
<script>
    window.CUSTOMER_TOAST_MSG = <?php echo json_encode($toastMsg); ?>;
    window.CUSTOMER_AJAX_URL = <?php echo json_encode(app_url('customers/ajax_list.php')); ?>;
    window.CUSTOMER_FILTER_CASCADE = <?php echo json_encode($filterCascade); ?>;
</script>
<?php
$extraJs = [
    'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js',
    'assets/js/customer_list.js',
];
require_once __DIR__ . '/../includes/footer.php';
