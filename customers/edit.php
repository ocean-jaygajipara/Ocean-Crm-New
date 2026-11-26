<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permission_helper.php';
require_once __DIR__ . '/../includes/customer_helper.php';
require_once __DIR__ . '/../includes/master_helper.php';
require_once __DIR__ . '/../includes/settings.php';

requireLogin();
ensureCustomerTables();
ensureMasterTables();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ' . app_url('customers/add.php'));
    exit;
}
requireAccess('customers', 'edit');
$isEdit = true;

$formError = '';
if (!empty($_SESSION['customer_form_error'])) {
    $formError = (string) $_SESSION['customer_form_error'];
    unset($_SESSION['customer_form_error']);
}
$formFlash = [];
if (!empty($_SESSION['customer_form_flash']) && is_array($_SESSION['customer_form_flash'])) {
    $formFlash = $_SESSION['customer_form_flash'];
    unset($_SESSION['customer_form_flash']);
}

$row = getCustomerRow($id);
if (!$row || (int) ($row['status'] ?? 0) !== 1) {
    header('Location: ' . app_url('customers/index.php'));
    exit;
}
if ($formFlash !== []) {
    foreach ($formFlash as $k => $v) {
        if ($k === 'id') continue;
        $row[$k] = $v;
    }
}

$countries = getMasterSelectOptions('countries');
$customerTypes = getCustomerTypeOptions();
$formCascade = ['states' => [], 'cities' => [], 'areas' => []];
foreach (getActiveMasterRows('states', 'name ASC') as $r) {
    $formCascade['states'][] = ['id' => (int) $r['id'], 'name' => (string) $r['name'], 'country_id' => (int) ($r['country_id'] ?? 0)];
}
foreach (getActiveMasterRows('cities', 'name ASC') as $r) {
    $formCascade['cities'][] = ['id' => (int) $r['id'], 'name' => (string) $r['name'], 'state_id' => (int) ($r['state_id'] ?? 0)];
}
foreach (getActiveMasterRows('areas', 'name ASC') as $r) {
    $formCascade['areas'][] = ['id' => (int) $r['id'], 'name' => (string) $r['name'], 'city_id' => (int) ($r['city_id'] ?? 0)];
}

$pageTitle = 'Edit Customer';
$useSidebar = true;
$sidebarActive = 'customers';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="dashboard-main">
    <div class="page-toolbar flex-between">
        <a href="<?php echo app_url('customers/index.php'); ?>" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Back to Customers
        </a>
    </div>
    <div class="list-header">
        <div class="master-list-title">
            <div class="master-list-icon" style="background:#e08a0a;"><i class="fa-solid fa-user-pen"></i></div>
            <div>
                <h1>Edit Customer</h1>
                <p><?php echo htmlspecialchars((string) ($row['customer_no'] ?? '')); ?></p>
            </div>
        </div>
    </div>
    <div class="data-card data-card-pad">
        <?php require __DIR__ . '/_form.php'; ?>
    </div>
</main>
<?php
$extraJs = [
    'https://cdn.jsdelivr.net/npm/autosize@4.0.2/dist/autosize.min.js',
    'assets/js/customer_form.js',
];
require_once __DIR__ . '/../includes/footer.php';
