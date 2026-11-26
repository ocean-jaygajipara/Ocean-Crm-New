<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permission_helper.php';
require_once __DIR__ . '/../includes/customer_helper.php';
require_once __DIR__ . '/../includes/master_helper.php';
require_once __DIR__ . '/../includes/settings.php';

requireLogin();
requireAccess('customers', 'add');
ensureCustomerTables();
ensureMasterTables();

$id = 0;
$isEdit = false;

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

$row = [
    'customer_no' => '',
    'customer_type_id' => 0,
    'customer_name' => '',
    'company_name' => '',
    'contact_person' => '',
    'email' => '',
    'phone' => '',
    'whatsapp' => '',
    'country_id' => null,
    'state_id' => null,
    'city_id' => null,
    'area_id' => null,
    'address' => '',
    'remarks' => '',
    'inquiry_id' => 0,
];
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

$pageTitle = 'Add Customer';
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
            <div class="master-list-icon" style="background:#e08a0a;"><i class="fa-solid fa-user-plus"></i></div>
            <div>
                <h1>Add Customer</h1>
                <p>Create a new customer</p>
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
