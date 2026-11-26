<?php
/**
 * Inquiry — View (read-only)
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permission_helper.php';
require_once __DIR__ . '/../includes/inquiry_helper.php';
require_once __DIR__ . '/../includes/customer_helper.php';
require_once __DIR__ . '/../includes/master_helper.php';
require_once __DIR__ . '/../includes/date_helper.php';

requireLogin();
ensureInquiryTables();
ensureMasterTables();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ' . app_url('inquiry/index.php'));
    exit;
}

requireAccess('inquiry', 'view');

$row = getInquiryRow($id);
if (!$row || !canAccessInquiryRecord($row)) {
    header('Location: ' . app_url('inquiry/index.php'));
    exit;
}

$isActive = (int) ($row['status'] ?? 0) === 1;
$canEdit = $isActive && canAccess('inquiry', 'edit');
$canDelete = canAccess('inquiry', 'delete');
$canConvert = $isActive && (canAccess('customers', 'add') || canAccess('inquiry', 'edit'));
$linkedCustomerId = (int) ($row['customer_id'] ?? 0);

$inqStatuses = getInquiryStatuses();
$fuStatuses = getFollowUpStatuses();
$inqSources = getInquirySources();
$productOptions = getInquiryProductOptions();
$inquiryProducts = getInquiryProducts($id);

$countries = getMasterSelectOptions('countries');
$states = getMasterSelectOptions('states');
$cities = getMasterSelectOptions('cities');
$areas = getMasterSelectOptions('areas');

$createdByName = getInquiryUserName((int) ($row['created_by'] ?? 0)) ?: '—';
$assignToName = getInquiryUserName((int) ($row['assign_to'] ?? 0)) ?: '—';

if (!function_exists('inqViewVal')) {
    function inqViewVal($v)
    {
        $v = trim((string) $v);
        return $v !== '' ? $v : '—';
    }
}

$pageTitle = 'View Inquiry';
$useSidebar = true;
$sidebarActive = 'inquiry';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="dashboard-main">
    <div class="page-toolbar flex-between">
        <a href="<?php echo app_url('inquiry/index.php'); ?>" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Back to Inquiry List
        </a>
        <div class="toolbar-actions">
            <?php if ($canConvert && $linkedCustomerId <= 0): ?>
            <a href="<?php echo app_url('inquiry/convert_customer.php?id=' . $id); ?>" class="btn-secondary js-convert-customer">
                <i class="fa-solid fa-user-plus"></i> Convert to Customer
            </a>
            <?php elseif ($linkedCustomerId > 0 && canAccess('customers', 'view')): ?>
            <a href="<?php echo app_url('customers/view.php?id=' . $linkedCustomerId); ?>" class="btn-secondary">
                <i class="fa-solid fa-user-check"></i> View Customer
            </a>
            <?php endif; ?>
            <?php if ($canEdit): ?>
            <a href="<?php echo app_url('inquiry/edit.php?id=' . $id); ?>" class="btn-primary">
                <i class="fa-solid fa-pen"></i> Edit
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="list-header">
        <div class="master-list-title">
            <div class="master-list-icon" style="background:#e08a0a;">
                <i class="fa-solid fa-eye"></i>
            </div>
            <div>
                <h1>View Inquiry</h1>
                <p><?php echo htmlspecialchars((string) ($row['inquiry_no'] ?? '')); ?></p>
            </div>
        </div>
    </div>

    <div class="data-card data-card-pad">
        <?php if (!$isActive): ?>
            <div class="crm-view-banner is-deleted">This inquiry is deleted.</div>
        <?php endif; ?>

        <div class="crm-view-grid">
            <div class="crm-view-item">
                <span class="crm-view-label">Inquiry No</span>
                <span class="crm-view-value"><?php echo htmlspecialchars(inqViewVal($row['inquiry_no'] ?? '')); ?></span>
            </div>
            <div class="crm-view-item">
                <span class="crm-view-label">Inquiry Date</span>
                <span class="crm-view-value"><?php echo htmlspecialchars(formatDateDisplay($row['inquiry_date'] ?? '') ?: '—'); ?></span>
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
            <div class="crm-view-item">
                <span class="crm-view-label">Source of Inquiry</span>
                <span class="crm-view-value"><?php echo htmlspecialchars(inqViewVal($inqSources[(int) ($row['inquiry_source_id'] ?? 0)] ?? '')); ?></span>
            </div>
            <div class="crm-view-item">
                <span class="crm-view-label">Inquiry Status</span>
                <span class="crm-view-value"><?php echo htmlspecialchars(inqViewVal($inqStatuses[(int) ($row['inquiry_status_id'] ?? 0)] ?? '')); ?></span>
            </div>
            <div class="crm-view-item">
                <span class="crm-view-label">Follow Up Status</span>
                <span class="crm-view-value"><?php echo htmlspecialchars(inqViewVal($fuStatuses[(int) ($row['follow_up_status_id'] ?? 0)] ?? '')); ?></span>
            </div>
            <div class="crm-view-item">
                <span class="crm-view-label">Follow Up Date</span>
                <span class="crm-view-value"><?php echo htmlspecialchars(formatDateDisplay($row['follow_up_date'] ?? '') ?: '—'); ?></span>
            </div>
            <div class="crm-view-item">
                <span class="crm-view-label">Created By</span>
                <span class="crm-view-value"><?php echo htmlspecialchars($createdByName); ?></span>
            </div>
            <div class="crm-view-item">
                <span class="crm-view-label">Assign To</span>
                <span class="crm-view-value"><?php echo htmlspecialchars($assignToName); ?></span>
            </div>
            <div class="crm-view-item crm-view-item-full">
                <span class="crm-view-label">Address</span>
                <span class="crm-view-value"><?php echo nl2br(htmlspecialchars(inqViewVal($row['address'] ?? ''))); ?></span>
            </div>
            <?php if (trim((string) ($row['requirement_details'] ?? '')) !== ''): ?>
            <div class="crm-view-item crm-view-item-full">
                <span class="crm-view-label">Additional Notes</span>
                <span class="crm-view-value"><?php echo nl2br(htmlspecialchars((string) $row['requirement_details'])); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="crm-view-products">
            <h2 class="crm-view-section-title">Product Details</h2>
            <?php if ($inquiryProducts === []): ?>
                <p class="crm-view-empty">No products added.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data-table crm-view-product-table">
                        <thead>
                            <tr>
                                <th>Sr.</th>
                                <th>Product Name</th>
                                <th>Quantity</th>
                                <th>Requirements</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $sr = 1; foreach ($inquiryProducts as $line):
                                $pid = (int) ($line['product_id'] ?? 0);
                                $pname = $productOptions[$pid] ?? ('#' . $pid);
                            ?>
                            <tr>
                                <td><?php echo $sr++; ?></td>
                                <td><?php echo htmlspecialchars((string) $pname); ?></td>
                                <td><?php echo htmlspecialchars((string) ($line['qty'] ?? '1')); ?></td>
                                <td><?php echo htmlspecialchars(inqViewVal($line['requirements'] ?? '')); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>
<script>
(function () {
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.js-convert-customer');
        if (!btn) return;
        e.preventDefault();
        var href = btn.getAttribute('href');
        if (!href) return;
        var go = function () { window.location.href = href; };
        if (window.ConfirmDelete && typeof ConfirmDelete.ask === 'function') {
            ConfirmDelete.ask(
                'Convert this inquiry to a customer? Inquiry status will be set to Converted.',
                go,
                { title: 'Convert to Customer?', yesLabel: 'Yes, Convert', yesHtml: '<i class="fa-solid fa-user-plus"></i> Yes, Convert', iconClass: 'fa-solid fa-user-plus' }
            );
            return;
        }
        if (window.confirm('Convert this inquiry to a customer?')) go();
    });
})();
</script>
