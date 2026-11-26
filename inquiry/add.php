<?php
/**
 * Inquiry — Add
 * Form save: normal POST → save.php (no AJAX)
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permission_helper.php';
require_once __DIR__ . '/../includes/inquiry_helper.php';
require_once __DIR__ . '/../includes/master_helper.php';
require_once __DIR__ . '/../includes/settings.php';

requireLogin();
requireAccess('inquiry', 'add');
ensureInquiryTables();
ensureMasterTables();

$id = 0;
$isEdit = false;
$inquiryNoAuto = isInquiryNoAuto();

$formError = '';
if (!empty($_SESSION['inquiry_form_error'])) {
    $formError = (string) $_SESSION['inquiry_form_error'];
    unset($_SESSION['inquiry_form_error']);
}
$formFlash = [];
if (!empty($_SESSION['inquiry_form_flash']) && is_array($_SESSION['inquiry_form_flash'])) {
    $formFlash = $_SESSION['inquiry_form_flash'];
    unset($_SESSION['inquiry_form_flash']);
}

$row = [
    'inquiry_date'        => date('Y-m-d'),
    'customer_name'       => '',
    'company_name'        => '',
    'email'               => '',
    'phone'               => '',
    'country_id'          => null,
    'state_id'            => null,
    'city_id'             => null,
    'area_id'             => null,
    'address'             => '',
    'requirement_details' => '',
    'inquiry_status_id'   => getDefaultInquiryStatusId(),
    'follow_up_status_id' => getDefaultFollowUpStatusId(),
    'inquiry_source_id'   => getDefaultInquirySourceId(),
    'next_follow_up_date' => '',
    'inquiry_no'          => $inquiryNoAuto ? generateInquiryNo() : '',
    'created_by'          => (int) ($_SESSION['user_id'] ?? 0),
    'assign_to'           => (int) ($_SESSION['user_id'] ?? 0),
];

if ($formFlash !== []) {
    foreach ($formFlash as $k => $v) {
        if ($k === 'id') {
            continue;
        }
        if ($k === 'inquiry_no' && $inquiryNoAuto) {
            continue;
        }
        $row[$k] = $v;
    }
}
if ($inquiryNoAuto && trim((string) ($row['inquiry_no'] ?? '')) === '') {
    $row['inquiry_no'] = generateInquiryNo();
}

$pageTitle = 'Add Inquiry';
$extraCss = [
    'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css',
];
$useSidebar = true;
$sidebarActive = 'inquiry';
require_once __DIR__ . '/../includes/header.php';

$countries = getMasterSelectOptions('countries');
$inqStatuses = getInquiryStatuses();
$fuStatuses = getFollowUpStatuses();
$inqSources = getInquirySources();
$productOptions = getInquiryProductOptions();
$userOptions = getInquiryUserOptions();
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$canEditCreatedBy = isAdmin();
$createdById = (int) ($row['created_by'] ?? $currentUserId);
if ($createdById <= 0) {
    $createdById = $currentUserId;
}
$createdByName = getInquiryUserName($createdById);
if ($createdByName === '') {
    $createdByName = (string) ($_SESSION['full_name'] ?? 'User');
}
$inquiryProducts = [];
if ($formFlash !== []) {
    $flashPids = $formFlash['product_id'] ?? [];
    $flashQtys = $formFlash['qty'] ?? [];
    $flashReqs = $formFlash['line_requirements'] ?? [];
    if (is_array($flashPids) && $flashPids !== []) {
        foreach ($flashPids as $i => $pid) {
            $pid = (int) $pid;
            if ($pid <= 0) {
                continue;
            }
            $inquiryProducts[] = [
                'product_id' => $pid,
                'qty' => (float) ($flashQtys[$i] ?? 1),
                'requirements' => (string) ($flashReqs[$i] ?? ''),
            ];
        }
    }
}

$formCascade = ['states' => [], 'cities' => [], 'areas' => []];
foreach (getActiveMasterRows('states', 'name ASC') as $r) {
    $formCascade['states'][] = [
        'id' => (int) $r['id'],
        'name' => (string) $r['name'],
        'country_id' => (int) ($r['country_id'] ?? 0),
    ];
}
foreach (getActiveMasterRows('cities', 'name ASC') as $r) {
    $formCascade['cities'][] = [
        'id' => (int) $r['id'],
        'name' => (string) $r['name'],
        'state_id' => (int) ($r['state_id'] ?? 0),
    ];
}
foreach (getActiveMasterRows('areas', 'name ASC') as $r) {
    $formCascade['areas'][] = [
        'id' => (int) $r['id'],
        'name' => (string) $r['name'],
        'city_id' => (int) ($r['city_id'] ?? 0),
    ];
}
?>
<main class="dashboard-main">
    <div class="page-toolbar flex-between">
        <a href="<?php echo app_url('inquiry/index.php'); ?>" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Back to Inquiry List
        </a>
    </div>

    <div class="list-header">
        <div class="master-list-title">
            <div class="master-list-icon" style="background:#2466FB;">
                <i class="fa-solid fa-clipboard-question"></i>
            </div>
            <div>
                <h1>Add Inquiry</h1>
                <p><?php echo htmlspecialchars((string) ($row['inquiry_no'] ?? 'New customer inquiry')); ?></p>
            </div>
        </div>
    </div>

    <div class="data-card data-card-pad">
        <?php require __DIR__ . '/_form.php'; ?>
    </div>
</main>
<?php
$extraJs = [
    'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js',
    'assets/js/inquiry_form.js',
];
require_once __DIR__ . '/../includes/footer.php';
