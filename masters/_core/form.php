<?php
/**
 * Masters PHP Core — Add / Edit form
 */
require_once __DIR__ . '/bootstrap.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$row = null;
if ($id > 0) {
    requireAccess('masters', 'edit');
    $row = getMasterRow($master['table'], $id);
    // Soft-deleted rows cannot be edited — restore first
    if (!$row || (int) ($row['status'] ?? 1) !== 1) {
        header('Location: ' . $masterListUrl);
        exit;
    }
} else {
    requireAccess('masters', 'add');
}

$pageTitle = ($row ? 'Edit ' : 'Add ') . $master['singular'];
$extraCss = [
    'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css',
];
$useSidebar = true;
$sidebarMode = 'masters';
$sidebarActive = $master['key'];

require_once __DIR__ . '/../../includes/header.php';

if (!function_exists('masterFieldValue')) {
    function masterFieldValue($row, $field)
    {
        $name = $field['name'];
        if ($row && array_key_exists($name, $row) && $row[$name] !== null) {
            return $row[$name];
        }
        return $field['default'] ?? '';
    }
}
?>
<main class="dashboard-main">
    <div class="page-toolbar">
        <a href="<?php echo htmlspecialchars($masterListUrl); ?>" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Back to <?php echo htmlspecialchars($master['title']); ?>
        </a>
    </div>

    <div class="form-page-card master-form-card">
        <div class="form-page-header">
            <div class="master-list-title">
                <div class="master-list-icon" style="background: <?php echo htmlspecialchars($master['color']); ?>;">
                    <i class="fa-solid <?php echo htmlspecialchars($master['icon']); ?>"></i>
                </div>
                <div>
                    <h1><?php echo $row ? 'Edit' : 'Add'; ?> <?php echo htmlspecialchars($master['singular']); ?></h1>
                    <p><?php echo htmlspecialchars($master['title']); ?></p>
                </div>
            </div>
        </div>

        <form method="POST" action="<?php echo htmlspecialchars($masterSaveUrl); ?>" id="masterAjaxForm" class="employee-form master-form" autocomplete="off">
            <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
            <input type="hidden" name="ajax" value="1">

            <div class="form-section">
                <h3><i class="fa-solid fa-pen-to-square"></i> Details</h3>
                <div class="form-grid form-grid-3">
                    <?php foreach ($master['fields'] as $field): ?>
                        <?php
                        $span = (int) ($field['span'] ?? 1);
                        $spanClass = $span === 3 ? 'full' : ($span === 2 ? 'span-2' : '');
                        $val = masterFieldValue($row, $field);
                        $req = !empty($field['required']) ? 'required' : '';
                        ?>
                        <div class="form-group <?php echo $spanClass; ?>">
                            <label>
                                <?php echo htmlspecialchars($field['label']); ?>
                                <?php if (!empty($field['required'])): ?><span class="req">*</span><?php endif; ?>
                            </label>

                            <?php if ($field['type'] === 'textarea'): ?>
                                <textarea name="<?php echo htmlspecialchars($field['name']); ?>"
                                          class="form-control" rows="3" <?php echo $req; ?>><?php echo htmlspecialchars((string) $val); ?></textarea>

                            <?php elseif ($field['type'] === 'select'): ?>
                                <?php
                                $options = resolveMasterFieldOptions($field);
                                $parent = $field['parent'] ?? '';
                                $parentKey = $field['parent_key'] ?? '';
                                $hasParent = $parent !== '';
                                ?>
                                <select id="fld_<?php echo htmlspecialchars($field['name']); ?>"
                                        name="<?php echo htmlspecialchars($field['name']); ?>"
                                        class="form-control js-select2<?php echo $hasParent ? ' js-master-parent-child' : ''; ?>"
                                        <?php echo $req; ?>
                                        <?php if ($hasParent): ?>
                                            data-parent="<?php echo htmlspecialchars($parent); ?>"
                                            data-parent-key="<?php echo htmlspecialchars($parentKey ?: $parent); ?>"
                                        <?php endif; ?>>
                                    <option value="">Select <?php echo htmlspecialchars($field['label']); ?></option>
                                    <?php if (!$hasParent): ?>
                                        <?php foreach ($options as $optVal => $optLabel): ?>
                                            <option value="<?php echo htmlspecialchars((string) $optVal); ?>"
                                                <?php echo ((string) $val === (string) $optVal) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars((string) $optLabel); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>

                            <?php elseif ($field['type'] === 'date'): ?>
                                <input type="text"
                                       name="<?php echo htmlspecialchars($field['name']); ?>"
                                       class="form-control js-date"
                                       placeholder="DD-MM-YYYY"
                                       <?php echo $req; ?>
                                       value="<?php echo htmlspecialchars(dateInputValue($val)); ?>">

                            <?php elseif ($field['type'] === 'color'): ?>
                                <div class="color-input-row">
                                    <input type="color" class="form-control color-picker"
                                           value="<?php echo htmlspecialchars($val ?: '#e08a0a'); ?>"
                                           oninput="this.nextElementSibling.value=this.value">
                                    <input type="text" name="<?php echo htmlspecialchars($field['name']); ?>"
                                           class="form-control" <?php echo $req; ?>
                                           value="<?php echo htmlspecialchars((string) ($val ?: '#e08a0a')); ?>">
                                </div>

                            <?php elseif ($field['type'] === 'checkbox'): ?>
                                <label class="checkbox-inline" style="display:inline-flex;align-items:center;gap:8px;margin-top:8px;">
                                    <input type="hidden" name="<?php echo htmlspecialchars($field['name']); ?>" value="0">
                                    <input type="checkbox"
                                           name="<?php echo htmlspecialchars($field['name']); ?>"
                                           value="1"
                                           <?php echo ((int) $val === 1) ? 'checked' : ''; ?>>
                                    <span>Yes</span>
                                </label>

                            <?php else: ?>
                                <input type="<?php echo htmlspecialchars($field['type']); ?>"
                                       name="<?php echo htmlspecialchars($field['name']); ?>"
                                       class="form-control"
                                       <?php echo $req; ?>
                                       <?php if (!empty($field['step'])): ?>step="<?php echo htmlspecialchars($field['step']); ?>"<?php endif; ?>
                                       value="<?php echo htmlspecialchars((string) $val); ?>">
                            <?php endif; ?>

                            <?php if (!empty($field['help'])): ?>
                                <small class="form-help"><?php echo htmlspecialchars($field['help']); ?></small>
                            <?php elseif ($field['type'] === 'date'): ?>
                                <small class="form-help">Format: DD-MM-YYYY</small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="sticky-actions form-actions">
                <button type="submit" class="btn-primary" id="masterAjaxSubmit">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <?php echo $row ? 'Update' : 'Save'; ?>
                </button>
                <a href="<?php echo htmlspecialchars($masterListUrl); ?>" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</main>
<?php
$formCascade = ['product_subcategories' => []];
foreach (getActiveMasterRows('product_subcategories', 'name ASC') as $r) {
    $formCascade['product_subcategories'][] = [
        'id' => (int) $r['id'],
        'name' => (string) $r['name'],
        'product_category_id' => (int) ($r['product_category_id'] ?? 0),
    ];
}
$formSelected = [];
foreach ($master['fields'] as $field) {
    if (($field['type'] ?? '') === 'select') {
        $fname = $field['name'];
        $formSelected[$fname] = (int) masterFieldValue($row, $field);
    }
}
?>
<script>
window.MASTER_FORM_CASCADE = <?php echo json_encode($formCascade); ?>;
window.MASTER_FORM_SELECTED = <?php echo json_encode($formSelected); ?>;
</script>
<?php
$extraJs = [
    'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js',
    'assets/js/masters_form.js',
];
require_once __DIR__ . '/../../includes/footer.php';
