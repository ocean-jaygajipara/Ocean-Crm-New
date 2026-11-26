<?php
/**
 * Inquiry — Edit
 * Form save: normal POST → save.php (no AJAX)
 * Requires ?id=
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permission_helper.php';
require_once __DIR__ . '/../includes/inquiry_helper.php';
require_once __DIR__ . '/../includes/master_helper.php';
require_once __DIR__ . '/../includes/settings.php';

requireLogin();
ensureInquiryTables();
ensureMasterTables();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ' . app_url('inquiry/add.php'));
    exit;
}

requireAccess('inquiry', 'edit');
$isEdit = true;
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

$row = getInquiryRow($id);
if (!$row || (int) ($row['status'] ?? 0) !== 1 || !canAccessInquiryRecord($row)) {
    header('Location: ' . app_url('inquiry/index.php'));
    exit;
}

if ($formFlash !== []) {
    foreach ($formFlash as $k => $v) {
        if ($k === 'id') {
            continue;
        }
        $row[$k] = $v;
    }
}

$pageTitle = 'Edit Inquiry';
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
$createdById = (int) ($row['created_by'] ?? 0);
if ($createdById <= 0) {
    $createdById = $currentUserId;
}
$createdByName = getInquiryUserName($createdById);
if ($createdByName === '') {
    $createdByName = '—';
}
$inquiryProducts = getInquiryProducts($id);
if ($formFlash !== []) {
    $flashPids = $formFlash['product_id'] ?? [];
    $flashQtys = $formFlash['qty'] ?? [];
    $flashReqs = $formFlash['line_requirements'] ?? [];
    if (is_array($flashPids)) {
        $inquiryProducts = [];
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
                <h1>Edit Inquiry</h1>
                <p><?php echo htmlspecialchars((string) ($row['inquiry_no'] ?? '')); ?></p>
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
