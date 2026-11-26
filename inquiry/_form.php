<?php
/**
 * Inquiry — shared form fields (included by add.php / edit.php)
 * Save = normal POST → save.php (no AJAX)
 *
 * Expected vars: $id, $isEdit, $row, $formError,
 *   $countries, $inqStatuses, $fuStatuses, $inqSources, $formCascade
 * Optional: $inquiryNoAuto, $productOptions, $inquiryProducts, $userOptions,
 *   $createdById, $createdByName, $currentUserId
 */
if (!isset($id, $isEdit, $row)) {
    return;
}
if (!isset($inquiryNoAuto)) {
    $inquiryNoAuto = function_exists('isInquiryNoAuto') ? isInquiryNoAuto() : true;
}
if (!isset($productOptions) || !is_array($productOptions)) {
    $productOptions = function_exists('getInquiryProductOptions') ? getInquiryProductOptions() : [];
}
if (!isset($inquiryProducts) || !is_array($inquiryProducts)) {
    $inquiryProducts = [];
}
if (!isset($userOptions) || !is_array($userOptions)) {
    $userOptions = function_exists('getInquiryUserOptions') ? getInquiryUserOptions() : [];
}
if (!isset($currentUserId)) {
    $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
}
if (!isset($createdById)) {
    $createdById = (int) ($row['created_by'] ?? $currentUserId);
    if ($createdById <= 0) {
        $createdById = $currentUserId;
    }
}
if (!isset($createdByName) || $createdByName === '') {
    $createdByName = function_exists('getInquiryUserName')
        ? getInquiryUserName($createdById)
        : (string) ($_SESSION['full_name'] ?? 'User');
    if ($createdByName === '') {
        $createdByName = (string) ($_SESSION['full_name'] ?? 'User');
    }
}
$selectedAssignTo = (int) ($row['assign_to'] ?? 0);
if (!isset($canEditCreatedBy)) {
    $canEditCreatedBy = function_exists('isAdmin') ? isAdmin() : false;
}
?>
        <?php if (!empty($formError)): ?>
            <div class="alert alert-error" style="margin-bottom:14px;">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo htmlspecialchars((string) $formError); ?>
            </div>
        <?php endif; ?>
        <form method="post" action="<?php echo app_url('inquiry/save.php'); ?>" id="inquiryForm" class="crm-form" autocomplete="off">
            <input type="hidden" name="id" value="<?php echo (int) $id; ?>">

            <div class="crm-row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="inquiry_no">
                            Inquiry No <?php if (!$inquiryNoAuto): ?><span class="req">*</span><?php endif; ?>
                            <?php if ($inquiryNoAuto): ?>
                                <small class="form-help" style="display:inline;margin-left:6px;">Auto generated</small>
                            <?php else: ?>
                                <small class="form-help" style="display:inline;margin-left:6px;">Manual entry</small>
                            <?php endif; ?>
                        </label>
                        <?php if ($inquiryNoAuto): ?>
                            <input type="text" id="inquiry_no" class="form-control is-readonly-soft"
                                   value="<?php echo htmlspecialchars((string) ($row['inquiry_no'] ?? '')); ?>" readonly>
                        <?php else: ?>
                            <input type="text" id="inquiry_no" name="inquiry_no" class="form-control" required maxlength="40"
                                   value="<?php echo htmlspecialchars((string) ($row['inquiry_no'] ?? '')); ?>"
                                   placeholder="Enter inquiry number">
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="inquiry_date">Inquiry Date <span class="req">*</span></label>
                        <input type="text" id="inquiry_date" name="inquiry_date" class="form-control js-date" required
                               value="<?php echo htmlspecialchars(dateInputValue($row['inquiry_date'] ?? date('Y-m-d'))); ?>"
                               placeholder="DD-MM-YYYY" autocomplete="off">
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
                        <?php
                        if (!function_exists('getInquiryPhoneMaxLength')) {
                            require_once __DIR__ . '/../includes/settings.php';
                        }
                        $phoneMaxLen = getInquiryPhoneMaxLength();
                        $phoneVal = (string) ($row['phone'] ?? '');
                        $whatsappVal = (string) ($row['whatsapp'] ?? '');
                        $sameAsPhone = ($phoneVal !== '' && $whatsappVal !== '' && $phoneVal === $whatsappVal);
                        ?>
                        <input type="text" id="phone" name="phone" class="form-control js-inq-phone"
                               maxlength="<?php echo (int) $phoneMaxLen; ?>"
                               inputmode="tel"
                               autocomplete="tel"
                               placeholder="e.g. +91 98765-43210"
                               value="<?php echo htmlspecialchars($phoneVal); ?>">
                        <small class="form-help">Maximum <?php echo (int) $phoneMaxLen; ?> characters. Digits and + - ( ) . / space only.</small>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group inq-wa-group">
                        <div class="inq-wa-label-row">
                            <label for="whatsapp">WhatsApp</label>
                            <label class="inq-same-phone-check" for="same_as_phone">
                                <input type="checkbox" id="same_as_phone" <?php echo $sameAsPhone ? 'checked' : ''; ?>>
                                <span>Same as Phone</span>
                            </label>
                        </div>
                        <input type="text" id="whatsapp" name="whatsapp" class="form-control js-inq-phone"
                               maxlength="<?php echo (int) $phoneMaxLen; ?>"
                               inputmode="tel"
                               autocomplete="tel"
                               placeholder="WhatsApp number"
                               value="<?php echo htmlspecialchars($whatsappVal); ?>"
                               <?php echo $sameAsPhone ? 'readonly' : ''; ?>>
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
                        <select id="country_id" name="country_id" class="form-control js-select2 js-inq-loc">
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
                        <select id="state_id" name="state_id" class="form-control js-select2 js-inq-loc" data-parent="country_id"></select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="city_id">City</label>
                        <select id="city_id" name="city_id" class="form-control js-select2 js-inq-loc" data-parent="state_id"></select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="area_id">Area</label>
                        <select id="area_id" name="area_id" class="form-control js-select2 js-inq-loc" data-parent="city_id"></select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="inquiry_source_id">Source of Inquiry</label>
                        <select id="inquiry_source_id" name="inquiry_source_id" class="form-control js-select2">
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
                        <select id="inquiry_status_id" name="inquiry_status_id" class="form-control js-select2" required>
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
                        <select id="follow_up_status_id" name="follow_up_status_id" class="form-control js-select2">
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
                        <label for="follow_up_date">Follow Up Date</label>
                        <input type="text" id="follow_up_date" name="follow_up_date" class="form-control js-date"
                               value="<?php echo htmlspecialchars(dateInputValue($row['follow_up_date'] ?? '')); ?>"
                               placeholder="DD-MM-YYYY" autocomplete="off">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="created_by">Created By</label>
                        <?php if (!empty($canEditCreatedBy)): ?>
                            <select id="created_by" name="created_by" class="form-control js-select2">
                                <option value="0">Select</option>
                                <?php foreach ($userOptions as $uid => $ulabel): ?>
                                    <option value="<?php echo (int) $uid; ?>" <?php echo (int) $createdById === (int) $uid ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars((string) $ulabel); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="hidden" name="created_by" value="<?php echo (int) $createdById; ?>">
                            <input type="text" id="created_by" class="form-control is-readonly-soft"
                                   value="<?php echo htmlspecialchars((string) $createdByName); ?>" readonly>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="assign_to">Assign To</label>
                        <select id="assign_to" name="assign_to" class="form-control js-select2">
                            <option value="0">Select</option>
                            <?php foreach ($userOptions as $uid => $ulabel): ?>
                                <option value="<?php echo (int) $uid; ?>" <?php echo $selectedAssignTo === (int) $uid ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string) $ulabel); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" class="form-control autosize" rows="1" maxlength="255"
                                  placeholder="Street / landmark / full address"><?php echo htmlspecialchars((string) ($row['address'] ?? '')); ?></textarea>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group inq-product-details">
                        <label class="inq-section-title">Product Details</label>
                        <div class="inq-pd-picker">
                            <label for="inqProductPicker" class="inq-pd-picker-label">Product</label>
                            <select id="inqProductPicker" class="form-control js-select2">
                                <option value="">Select Product</option>
                                <?php foreach ($productOptions as $pid => $plabel): ?>
                                    <option value="<?php echo (int) $pid; ?>"><?php echo htmlspecialchars((string) $plabel); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="inq-pd-table" id="inqReqProducts">
                            <div class="inq-pd-head">
                                <span class="inq-pd-col inq-pd-col-name">Product Name</span>
                                <span class="inq-pd-col inq-pd-col-qty">Quantity</span>
                                <span class="inq-pd-col inq-pd-col-req">Requirements</span>
                                <span class="inq-pd-col inq-pd-col-action">Action</span>
                            </div>
                            <div id="inqReqProductRows" class="inq-pd-body">
                                <?php foreach ($inquiryProducts as $line):
                                    $selPid = (int) ($line['product_id'] ?? 0);
                                    if ($selPid <= 0) {
                                        continue;
                                    }
                                    $pName = $productOptions[$selPid] ?? ('#' . $selPid);
                                ?>
                                <div class="inq-pd-row">
                                    <div class="inq-pd-col inq-pd-col-name">
                                        <input type="hidden" name="product_id[]" value="<?php echo $selPid; ?>">
                                        <span class="inq-pd-name"><?php echo htmlspecialchars((string) $pName); ?></span>
                                    </div>
                                    <div class="inq-pd-col inq-pd-col-qty">
                                        <input type="number" name="qty[]" class="form-control js-inq-qty" min="0.01" step="0.01"
                                               value="<?php echo htmlspecialchars((string) ($line['qty'] ?? 1)); ?>" placeholder="Qty">
                                    </div>
                                    <div class="inq-pd-col inq-pd-col-req">
                                        <input type="text" name="line_requirements[]" class="form-control"
                                               value="<?php echo htmlspecialchars((string) ($line['requirements'] ?? '')); ?>"
                                               placeholder="Requirements">
                                    </div>
                                    <div class="inq-pd-col inq-pd-col-action">
                                        <button type="button" class="inq-pd-remove inq-req-remove" title="Remove">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="inq-pd-empty" id="inqPdEmpty" <?php echo $inquiryProducts !== [] ? 'hidden' : ''; ?>>
                                No products added yet. Select a product above.
                            </div>
                        </div>
                        <textarea id="requirement_details" name="requirement_details" class="form-control inq-req-notes" rows="2"
                                  placeholder="Additional notes (optional)"><?php echo htmlspecialchars((string) ($row['requirement_details'] ?? '')); ?></textarea>
                    </div>
                </div>
            </div>
<template id="inqReqProductRowTpl">
    <div class="inq-pd-row">
        <div class="inq-pd-col inq-pd-col-name">
            <input type="hidden" name="product_id[]" value="">
            <span class="inq-pd-name"></span>
        </div>
        <div class="inq-pd-col inq-pd-col-qty">
            <input type="number" name="qty[]" class="form-control js-inq-qty" min="0.01" step="0.01" value="1" placeholder="Qty">
        </div>
        <div class="inq-pd-col inq-pd-col-req">
            <input type="text" name="line_requirements[]" class="form-control" value="" placeholder="Requirements">
        </div>
        <div class="inq-pd-col inq-pd-col-action">
            <button type="button" class="inq-pd-remove inq-req-remove" title="Remove">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>
    </div>
</template>

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> <?php echo $isEdit ? 'Update' : 'Save'; ?>
                </button>
                <a href="<?php echo app_url('inquiry/index.php'); ?>" class="btn-secondary">Cancel</a>
            </div>
        </form>
<script>
window.INQUIRY_FORM_CASCADE = <?php echo json_encode($formCascade ?? []); ?>;
window.INQUIRY_FORM_ERROR = <?php echo json_encode((string) ($formError ?? '')); ?>;
window.INQUIRY_FORM_SELECTED = {
    country_id: <?php echo (int) ($row['country_id'] ?? 0); ?>,
    state_id: <?php echo (int) ($row['state_id'] ?? 0); ?>,
    city_id: <?php echo (int) ($row['city_id'] ?? 0); ?>,
    area_id: <?php echo (int) ($row['area_id'] ?? 0); ?>
};
window.INQUIRY_PRODUCTS = <?php echo json_encode(array_values($inquiryProducts)); ?>;
</script>
