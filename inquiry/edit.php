<?php
/**
 * Inquiry — Add / Edit form
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permission_helper.php';
require_once __DIR__ . '/../includes/inquiry_helper.php';
require_once __DIR__ . '/../includes/master_helper.php';

requireLogin();
ensureInquiryTables();
ensureMasterTables();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $id > 0;

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

if ($isEdit) {
    requireAccess('inquiry', 'edit');
    $row = getInquiryRow($id);
    if (!$row || (int) ($row['status'] ?? 0) !== 1) {
        header('Location: ' . app_url('inquiry/index.php'));
        exit;
    }
} else {
    requireAccess('inquiry', 'add');
    $row = [
        'inquiry_date'   => date('Y-m-d'),
        'customer_name'  => '',
        'company_name'   => '',
        'email'          => '',
        'phone'          => '',
        'country_id'     => null,
        'state_id'       => null,
        'city_id'        => null,
        'area_id'        => null,
        'subject'        => '',
        'message'        => '',
        'inquiry_status_id'   => getDefaultInquiryStatusId(),
        'follow_up_status_id' => getDefaultFollowUpStatusId(),
        'inquiry_source_id'   => getDefaultInquirySourceId(),
        'next_follow_up_date' => '',
        'inquiry_no'          => '',
    ];
}

if ($formFlash !== []) {
    foreach ($formFlash as $k => $v) {
        if ($k === 'id') {
            continue;
        }
        $row[$k] = $v;
    }
}

$pageTitle = $isEdit ? 'Edit Inquiry' : 'Add Inquiry';
$extraCss = [
    'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css',
];
$useSidebar = true;
$sidebarActive = 'inquiry';
require_once __DIR__ . '/../includes/header.php';

$countries = getMasterSelectOptions('countries');
$states = getMasterSelectOptions('states');
$cities = getMasterSelectOptions('cities');
$areas = getMasterSelectOptions('areas');
$inqStatuses = getInquiryStatuses();
$fuStatuses = getFollowUpStatuses();
$inqSources = getInquirySources();

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
                <h1><?php echo $isEdit ? 'Edit Inquiry' : 'Add Inquiry'; ?></h1>
                <p><?php echo $isEdit ? htmlspecialchars((string) ($row['inquiry_no'] ?? '')) : 'New customer inquiry'; ?></p>
            </div>
        </div>
    </div>

    <div class="data-card data-card-pad">
        <?php if ($formError !== ''): ?>
            <div class="alert alert-error" style="margin-bottom:14px;">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo htmlspecialchars($formError); ?>
            </div>
        <?php endif; ?>
        <form method="post" action="<?php echo app_url('inquiry/save.php'); ?>" id="inquiryForm" class="crm-form" autocomplete="off">
            <input type="hidden" name="id" value="<?php echo (int) $id; ?>">

            <div class="crm-row">
                <?php if ($isEdit): ?>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Inquiry No</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars((string) ($row['inquiry_no'] ?? '')); ?>" readonly>
                    </div>
                </div>
                <?php endif; ?>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="inquiry_date">Inquiry Date <span class="req">*</span></label>
                        <input type="date" id="inquiry_date" name="inquiry_date" class="form-control" required
                               value="<?php echo htmlspecialchars((string) ($row['inquiry_date'] ?? date('Y-m-d'))); ?>">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="customer_name">Customer Name <span class="req">*</span></label>
                        <input type="text" id="customer_name" name="customer_name" class="form-control" required maxlength="150"
                               value="<?php echo htmlspecialchars((string) ($row['customer_name'] ?? '')); ?>">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="company_name">Company</label>
                        <input type="text" id="company_name" name="company_name" class="form-control" maxlength="150"
                               value="<?php echo htmlspecialchars((string) ($row['company_name'] ?? '')); ?>">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone" class="form-control" maxlength="30"
                               value="<?php echo htmlspecialchars((string) ($row['phone'] ?? '')); ?>">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" maxlength="150"
                               value="<?php echo htmlspecialchars((string) ($row['email'] ?? '')); ?>">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="country_id">Country</label>
                        <select id="country_id" name="country_id" class="form-control js-inq-loc">
                            <option value="">Select</option>
                            <?php foreach ($countries as $oid => $olabel): ?>
                                <option value="<?php echo (int) $oid; ?>" <?php echo ((int) ($row['country_id'] ?? 0) === (int) $oid) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string) $olabel); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="state_id">State</label>
                        <select id="state_id" name="state_id" class="form-control js-inq-loc" data-parent="country_id"></select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="city_id">City</label>
                        <select id="city_id" name="city_id" class="form-control js-inq-loc" data-parent="state_id"></select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="area_id">Area</label>
                        <select id="area_id" name="area_id" class="form-control js-inq-loc" data-parent="city_id"></select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="inquiry_source_id">Source of Inquiry</label>
                        <select id="inquiry_source_id" name="inquiry_source_id" class="form-control">
                            <?php
                            $selectedSource = (int) ($row['inquiry_source_id'] ?? 0);
                            if ($selectedSource <= 0) {
                                $selectedSource = getDefaultInquirySourceId();
                            }
                            foreach ($inqSources as $k => $v):
                            ?>
                                <option value="<?php echo (int) $k; ?>" <?php echo ($selectedSource === (int) $k) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($v); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="inquiry_status_id">Inquiry Status <span class="req">*</span></label>
                        <select id="inquiry_status_id" name="inquiry_status_id" class="form-control" required>
                            <?php
                            $selectedStatus = (int) ($row['inquiry_status_id'] ?? 0);
                            if ($selectedStatus <= 0) {
                                $selectedStatus = getDefaultInquiryStatusId();
                            }
                            foreach ($inqStatuses as $k => $v):
                            ?>
                                <option value="<?php echo (int) $k; ?>" <?php echo ($selectedStatus === (int) $k) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($v); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="follow_up_status_id">Follow Up Status</label>
                        <select id="follow_up_status_id" name="follow_up_status_id" class="form-control">
                            <option value="0">Select</option>
                            <?php
                            $selectedFu = (int) ($row['follow_up_status_id'] ?? 0);
                            foreach ($fuStatuses as $k => $v):
                            ?>
                                <option value="<?php echo (int) $k; ?>" <?php echo ($selectedFu === (int) $k) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($v); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="next_follow_up_date">Next Follow Up Date</label>
                        <input type="date" id="next_follow_up_date" name="next_follow_up_date" class="form-control"
                               value="<?php echo htmlspecialchars((string) ($row['next_follow_up_date'] ?? '')); ?>">
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" class="form-control" maxlength="255"
                               value="<?php echo htmlspecialchars((string) ($row['subject'] ?? '')); ?>">
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" class="form-control" rows="4"><?php echo htmlspecialchars((string) ($row['message'] ?? '')); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> <?php echo $isEdit ? 'Update' : 'Save'; ?>
                </button>
                <a href="<?php echo app_url('inquiry/index.php'); ?>" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</main>
<script>
window.INQUIRY_FORM_CASCADE = <?php echo json_encode($formCascade); ?>;
window.INQUIRY_FORM_ERROR = <?php echo json_encode($formError); ?>;
window.INQUIRY_FORM_SELECTED = {
    country_id: <?php echo (int) ($row['country_id'] ?? 0); ?>,
    state_id: <?php echo (int) ($row['state_id'] ?? 0); ?>,
    city_id: <?php echo (int) ($row['city_id'] ?? 0); ?>,
    area_id: <?php echo (int) ($row['area_id'] ?? 0); ?>
};
(function () {
    var data = window.INQUIRY_FORM_CASCADE || {};
    var sel = window.INQUIRY_FORM_SELECTED || {};

    function fillSelect($el, items, parentKey, parentVal, selectedId) {
        if (window.CrmSelect2 && typeof window.CrmSelect2.destroy === 'function') {
            window.CrmSelect2.destroy($el);
        }
        var html = '<option value="">Select</option>';
        (items || []).forEach(function (it) {
            if (parentKey && String(it[parentKey]) !== String(parentVal)) return;
            var s = String(it.id) === String(selectedId) ? ' selected' : '';
            html += '<option value="' + it.id + '"' + s + '>' + $('<div/>').text(it.name).html() + '</option>';
        });
        $el.html(html);
        if (window.CrmSelect2 && typeof window.CrmSelect2.rebind === 'function') {
            window.CrmSelect2.rebind($el);
        } else if (window.CrmSelect2 && typeof window.CrmSelect2.init === 'function') {
            window.CrmSelect2.init($el);
        }
    }

    function refreshChain(from) {
        var c = $('#country_id').val() || '';
        var s = $('#state_id').val() || '';
        var ci = $('#city_id').val() || '';
        if (from === 'country' || from === 'init') {
            fillSelect($('#state_id'), data.states, 'country_id', c, from === 'init' ? sel.state_id : '');
            if (from !== 'init') { s = ''; ci = ''; }
        }
        if (from === 'country' || from === 'state' || from === 'init') {
            fillSelect($('#city_id'), data.cities, 'state_id', from === 'init' ? (sel.state_id || s) : s, from === 'init' ? sel.city_id : '');
            if (from !== 'init') ci = '';
        }
        if (from === 'country' || from === 'state' || from === 'city' || from === 'init') {
            fillSelect($('#area_id'), data.areas, 'city_id', from === 'init' ? (sel.city_id || ci) : ci, from === 'init' ? sel.area_id : '');
        }
    }

    $(function () {
        if (window.toastr && window.INQUIRY_FORM_ERROR) {
            toastr.options = { closeButton: true, progressBar: true, positionClass: 'toast-top-right', timeOut: 5000 };
            toastr.error(window.INQUIRY_FORM_ERROR);
        }
        if (window.CrmSelect2) {
            window.CrmSelect2.init($('#inquiryForm select'));
        }
        refreshChain('init');
        $('#country_id').on('change', function () { refreshChain('country'); });
        $('#state_id').on('change', function () { refreshChain('state'); });
        $('#city_id').on('change', function () { refreshChain('city'); });
    });
})();
</script>
<?php
$extraJs = [
    'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js',
];
require_once __DIR__ . '/../includes/footer.php';
