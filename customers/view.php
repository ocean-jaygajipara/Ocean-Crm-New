<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permission_helper.php';
require_once __DIR__ . '/../includes/customer_helper.php';
require_once __DIR__ . '/../includes/master_helper.php';
require_once __DIR__ . '/../includes/date_helper.php';

requireLogin();
ensureCustomerTables();
ensureMasterTables();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ' . app_url('customers/index.php'));
    exit;
}
requireAccess('customers', 'view');

$row = getCustomerRow($id);
if (!$row) {
    header('Location: ' . app_url('customers/index.php'));
    exit;
}

$isActive = (int) ($row['status'] ?? 0) === 1;
$canEdit = $isActive && canAccess('customers', 'edit');
$countries = getMasterSelectOptions('countries');
$states = getMasterSelectOptions('states');
$cities = getMasterSelectOptions('cities');
$areas = getMasterSelectOptions('areas');
$types = getCustomerTypeOptions();

if (!function_exists('inqViewVal')) {
    function inqViewVal($v)
    {
        $v = trim((string) $v);
        return $v !== '' ? $v : '—';
    }
}

$toastMsg = '';
if (isset($_GET['msg'])) {
    $map = [
        'converted' => 'Inquiry converted to customer successfully.',
        'already_customer' => 'This inquiry was already converted to a customer.',
    ];
    $toastMsg = $map[$_GET['msg']] ?? '';
}

$pageTitle = 'View Customer';
$extraCss = ['https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css'];
$useSidebar = true;
$sidebarActive = 'customers';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="dashboard-main">
    <div class="page-toolbar flex-between">
        <a href="<?php echo app_url('customers/index.php'); ?>" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Back to Customers
        </a>
        <div class="toolbar-actions">
            <?php if ((int) ($row['inquiry_id'] ?? 0) > 0): ?>
            <a href="<?php echo app_url('inquiry/view.php?id=' . (int) $row['inquiry_id']); ?>" class="btn-secondary">
                <i class="fa-solid fa-clipboard-question"></i> View Inquiry
            </a>
            <?php endif; ?>
            <?php if ($canEdit): ?>
            <a href="<?php echo app_url('customers/edit.php?id=' . $id); ?>" class="btn-primary">
                <i class="fa-solid fa-pen"></i> Edit
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="list-header">
        <div class="master-list-title">
            <div class="master-list-icon" style="background:#e08a0a;"><i class="fa-solid fa-user-check"></i></div>
            <div>
                <h1>View Customer</h1>
                <p><?php echo htmlspecialchars((string) ($row['customer_no'] ?? '')); ?></p>
            </div>
        </div>
    </div>

    <div class="data-card data-card-pad">
        <?php if (!$isActive): ?>
            <div class="crm-view-banner is-deleted">This customer is deleted.</div>
        <?php endif; ?>
        <div class="crm-view-grid">
            <div class="crm-view-item">
                <span class="crm-view-label">Customer No</span>
                <span class="crm-view-value"><?php echo htmlspecialchars(inqViewVal($row['customer_no'] ?? '')); ?></span>
            </div>
            <div class="crm-view-item">
                <span class="crm-view-label">Customer Type</span>
                <span class="crm-view-value"><?php echo htmlspecialchars(inqViewVal($types[(int) ($row['customer_type_id'] ?? 0)] ?? '')); ?></span>
            </div>
            <div class="crm-view-item">
                <span class="crm-view-label">Customer Name</span>
                <span class="crm-view-value"><?php echo htmlspecialchars(inqViewVal($row['customer_name'] ?? '')); ?></span>
            </div>
            <div class="crm-view-item">
                <span class="crm-view-label">Company</span>
                <span class="crm-view-value"><?php echo htmlspecialchars(inqViewVal($row['company_name'] ?? '')); ?></span>
            </div>
            <div class="crm-view-item">
                <span class="crm-view-label">Contact Person</span>
                <span class="crm-view-value"><?php echo htmlspecialchars(inqViewVal($row['contact_person'] ?? '')); ?></span>
            </div>
            <div class="crm-view-item">
                <span class="crm-view-label">Phone</span>
                <span class="crm-view-value"><?php echo htmlspecialchars(inqViewVal($row['phone'] ?? '')); ?></span>
            </div>
            <div class="crm-view-item">
                <span class="crm-view-label">WhatsApp</span>
                <span class="crm-view-value"><?php echo htmlspecialchars(inqViewVal($row['whatsapp'] ?? '')); ?></span>
            </div>
            <div class="crm-view-item">
                <span class="crm-view-label">Email</span>
                <span class="crm-view-value"><?php echo htmlspecialchars(inqViewVal($row['email'] ?? '')); ?></span>
            </div>
            <div class="crm-view-item">
                <span class="crm-view-label">Country</span>
                <span class="crm-view-value"><?php echo htmlspecialchars(inqViewVal($countries[(int) ($row['country_id'] ?? 0)] ?? '')); ?></span>
            </div>
            <div class="crm-view-item">
                <span class="crm-view-label">State</span>
                <span class="crm-view-value"><?php echo htmlspecialchars(inqViewVal($states[(int) ($row['state_id'] ?? 0)] ?? '')); ?></span>
            </div>
            <div class="crm-view-item">
                <span class="crm-view-label">City</span>
                <span class="crm-view-value"><?php echo htmlspecialchars(inqViewVal($cities[(int) ($row['city_id'] ?? 0)] ?? '')); ?></span>
            </div>
            <div class="crm-view-item">
                <span class="crm-view-label">Area</span>
                <span class="crm-view-value"><?php echo htmlspecialchars(inqViewVal($areas[(int) ($row['area_id'] ?? 0)] ?? '')); ?></span>
            </div>
            <div class="crm-view-item crm-view-item-full">
                <span class="crm-view-label">Address</span>
                <span class="crm-view-value"><?php echo nl2br(htmlspecialchars(inqViewVal($row['address'] ?? ''))); ?></span>
            </div>
            <?php if (trim((string) ($row['remarks'] ?? '')) !== ''): ?>
            <div class="crm-view-item crm-view-item-full">
                <span class="crm-view-label">Remarks</span>
                <span class="crm-view-value"><?php echo nl2br(htmlspecialchars((string) $row['remarks'])); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>
<script>window.CUSTOMER_TOAST_MSG = <?php echo json_encode($toastMsg); ?>;</script>
<?php
$extraJs = ['https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js'];
require_once __DIR__ . '/../includes/footer.php';
?>
<script>
(function () {
    if (window.toastr && window.CUSTOMER_TOAST_MSG) {
        toastr.options = { closeButton: true, progressBar: true, positionClass: 'toast-top-right', timeOut: 3500 };
        toastr.success(window.CUSTOMER_TOAST_MSG);
    }
})();
</script>
