<?php
/**
 * Customer — shared form (add/edit)
 * Expected: $id, $isEdit, $row, $formError, $formCascade, $countries, $customerTypes
 */
if (!isset($id, $isEdit, $row)) {
    return;
}
if (!function_exists('getInquiryPhoneMaxLength')) {
    require_once __DIR__ . '/../includes/settings.php';
}
$phoneMaxLen = getInquiryPhoneMaxLength();
$phoneVal = (string) ($row['phone'] ?? '');
$whatsappVal = (string) ($row['whatsapp'] ?? '');
$sameAsPhone = ($phoneVal !== '' && $whatsappVal !== '' && $phoneVal === $whatsappVal);
if (!isset($customerTypes) || !is_array($customerTypes)) {
    $customerTypes = function_exists('getCustomerTypeOptions') ? getCustomerTypeOptions() : [];
}
if (!isset($countries) || !is_array($countries)) {
    $countries = [];
}
?>
<?php if (!empty($formError)): ?>
    <div class="form-error-banner"><?php echo htmlspecialchars((string) $formError); ?></div>
<?php endif; ?>

<form id="customerForm" method="post" action="<?php echo app_url('customers/save.php'); ?>" autocomplete="off">
    <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
    <input type="hidden" name="inquiry_id" value="<?php echo (int) ($row['inquiry_id'] ?? 0); ?>">

    <div class="crm-row">
        <?php if ($isEdit): ?>
        <div class="col-md-4">
            <div class="form-group">
                <label for="customer_no">Customer No</label>
                <input type="text" id="customer_no" class="form-control" value="<?php echo htmlspecialchars((string) ($row['customer_no'] ?? '')); ?>" readonly>
            </div>
        </div>
        <?php endif; ?>

        <div class="col-md-4">
            <div class="form-group">
                <label for="customer_type_id">Customer Type</label>
                <select id="customer_type_id" name="customer_type_id" class="form-control js-select2">
                    <option value="0">Select</option>
                    <?php foreach ($customerTypes as $tid => $tlabel): ?>
                        <option value="<?php echo (int) $tid; ?>" <?php echo ((int) ($row['customer_type_id'] ?? 0) === (int) $tid) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string) $tlabel); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
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
                <label for="contact_person">Contact Person</label>
                <input type="text" id="contact_person" name="contact_person" class="form-control" maxlength="150"
                       value="<?php echo htmlspecialchars((string) ($row['contact_person'] ?? '')); ?>">
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" class="form-control js-cust-phone"
                       maxlength="<?php echo (int) $phoneMaxLen; ?>" inputmode="tel" autocomplete="tel"
                       placeholder="e.g. +91 98765-43210"
                       value="<?php echo htmlspecialchars($phoneVal); ?>">
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
                <input type="text" id="whatsapp" name="whatsapp" class="form-control js-cust-phone"
                       maxlength="<?php echo (int) $phoneMaxLen; ?>" inputmode="tel"
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
                <select id="country_id" name="country_id" class="form-control js-select2 js-cust-loc">
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
                <select id="state_id" name="state_id" class="form-control js-select2 js-cust-loc" data-parent="country_id"></select>
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label for="city_id">City</label>
                <select id="city_id" name="city_id" class="form-control js-select2 js-cust-loc" data-parent="state_id"></select>
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label for="area_id">Area</label>
                <select id="area_id" name="area_id" class="form-control js-select2 js-cust-loc" data-parent="city_id"></select>
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
            <div class="form-group">
                <label for="remarks">Remarks</label>
                <textarea id="remarks" name="remarks" class="form-control autosize" rows="2"
                          placeholder="Optional notes"><?php echo htmlspecialchars((string) ($row['remarks'] ?? '')); ?></textarea>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary">
            <i class="fa-solid fa-floppy-disk"></i> <?php echo $isEdit ? 'Update' : 'Save'; ?>
        </button>
        <a href="<?php echo app_url('customers/index.php'); ?>" class="btn-secondary">Cancel</a>
    </div>
</form>
<script>
    window.CUSTOMER_FORM_CASCADE = <?php echo json_encode($formCascade ?? ['states' => [], 'cities' => [], 'areas' => []]); ?>;
    window.CUSTOMER_FORM_SELECTED = <?php echo json_encode([
        'state_id' => (int) ($row['state_id'] ?? 0),
        'city_id' => (int) ($row['city_id'] ?? 0),
        'area_id' => (int) ($row['area_id'] ?? 0),
    ]); ?>;
</script>
