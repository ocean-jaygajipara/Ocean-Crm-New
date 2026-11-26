<?php
/**
 * Inquiry — List
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permission_helper.php';
require_once __DIR__ . '/../includes/inquiry_helper.php';
require_once __DIR__ . '/../includes/master_helper.php';

requireLogin();
requireAccess('inquiry', 'view');
ensureInquiryTables();
ensureMasterTables();

$canAdd = canAccess('inquiry', 'add');
$canDelete = canAccess('inquiry', 'delete');

$pageTitle = 'Inquiry';
$extraCss = [
    'https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css',
];
$useSidebar = true;
$sidebarActive = 'inquiry';
require_once __DIR__ . '/../includes/header.php';

$toastMsg = '';
if (isset($_GET['msg'])) {
    $map = [
        'added'    => 'Inquiry added successfully.',
        'updated'  => 'Inquiry updated successfully.',
        'deleted'  => 'Inquiry deleted successfully.',
        'restored' => 'Inquiry restored successfully.',
    ];
    $toastMsg = $map[$_GET['msg']] ?? '';
}

$filterCascade = ['states' => [], 'cities' => [], 'areas' => []];
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
foreach (getActiveMasterRows('areas', 'name ASC') as $r) {
    $filterCascade['areas'][] = [
        'id' => (int) $r['id'],
        'name' => (string) $r['name'],
        'city_id' => (int) ($r['city_id'] ?? 0),
    ];
}
$countries = getMasterSelectOptions('countries');
$inqStatuses = getInquiryStatuses();
$fuStatuses = getFollowUpStatuses();
$inqSources = getInquirySources();
?>
<main class="dashboard-main">
    <div class="page-toolbar flex-between">
        <a href="<?php echo app_url('dashboard.php'); ?>" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
        <div class="toolbar-actions">
            <?php if ($canAdd): ?>
            <a href="<?php echo app_url('inquiry/edit.php'); ?>" class="btn-primary">
                <i class="fa-solid fa-plus"></i> Add Inquiry
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="list-header">
        <div class="master-list-title">
            <div class="master-list-icon" style="background:#2466FB;">
                <i class="fa-solid fa-clipboard-question"></i>
            </div>
            <div>
                <h1>Inquiry</h1>
                <p>Customer inquiries</p>
            </div>
        </div>
    </div>

    <div class="data-card data-card-pad crm-filter-card">
        <form id="inquiryFilterForm" class="crm-filter-bar" autocomplete="off">
            <div class="crm-row">
                <div class="col-md-3">
                    <div class="form-group crm-filter-item">
                        <label for="flt_q">Search</label>
                        <input type="text" id="flt_q" name="q" class="form-control" placeholder="No / Name / Phone / Email">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group crm-filter-item">
                        <label for="flt_country_id">Country</label>
                        <select id="flt_country_id" name="country_id" class="form-control js-inq-filter">
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
                        <select id="flt_state_id" name="state_id" class="form-control js-inq-filter" data-parent="country_id"></select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group crm-filter-item">
                        <label for="flt_city_id">City</label>
                        <select id="flt_city_id" name="city_id" class="form-control js-inq-filter" data-parent="state_id"></select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group crm-filter-item">
                        <label for="flt_inquiry_source_id">Source of Inquiry</label>
                        <select id="flt_inquiry_source_id" name="inquiry_source_id" class="form-control js-inq-filter">
                            <option value="">All</option>
                            <?php foreach ($inqSources as $k => $v): ?>
                                <option value="<?php echo (int) $k; ?>"><?php echo htmlspecialchars($v); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group crm-filter-item">
                        <label for="flt_inquiry_status_id">Inquiry Status</label>
                        <select id="flt_inquiry_status_id" name="inquiry_status_id" class="form-control js-inq-filter">
                            <option value="">All</option>
                            <?php foreach ($inqStatuses as $k => $v): ?>
                                <option value="<?php echo (int) $k; ?>"><?php echo htmlspecialchars($v); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group crm-filter-item">
                        <label for="flt_follow_up_status_id">Follow Up Status</label>
                        <select id="flt_follow_up_status_id" name="follow_up_status_id" class="form-control js-inq-filter">
                            <option value="">All</option>
                            <?php foreach ($fuStatuses as $k => $v): ?>
                                <option value="<?php echo (int) $k; ?>"><?php echo htmlspecialchars($v); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php if ($canDelete): ?>
                <div class="col-md-3">
                    <div class="form-group crm-filter-item">
                        <label for="flt_record_status">Status</label>
                        <select id="flt_record_status" name="record_status" class="form-control js-inq-filter">
                            <option value="active" selected>Active</option>
                            <option value="deleted">Deleted</option>
                            <option value="all">All</option>
                        </select>
                    </div>
                </div>
                <?php endif; ?>
                <div class="col-md-3 crm-filter-actions">
                    <button type="submit" class="btn-primary"><i class="fa-solid fa-filter"></i> Apply</button>
                    <button type="button" class="btn-secondary" id="inquiryFilterReset"><i class="fa-solid fa-rotate-left"></i> Reset</button>
                </div>
            </div>
        </form>
    </div>

    <div class="data-card data-card-pad">
        <div class="table-wrap">
            <table id="inquiryTable" class="display data-table crm-dt-table nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>Sr.</th>
                        <th>Inquiry No</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Company</th>
                        <th>Phone</th>
                        <th>Location</th>
                        <th>Source</th>
                        <th>Inquiry Status</th>
                        <th>Follow Up</th>
                        <th>Next Follow Up</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</main>
<script>
    window.INQUIRY_TOAST_MSG = <?php echo json_encode($toastMsg); ?>;
    window.INQUIRY_AJAX_URL = <?php echo json_encode(app_url('inquiry/ajax_list.php')); ?>;
    window.INQUIRY_FILTER_CASCADE = <?php echo json_encode($filterCascade); ?>;
</script>
<?php
$extraJs = [
    'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js',
    'assets/js/inquiry_list.js',
];
require_once __DIR__ . '/../includes/footer.php';
