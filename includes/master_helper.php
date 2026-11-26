<?php
/**
 * CRM Master Helper — tables + CRUD
 * Uses shared getDBConnection() — do not close mid-request.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/dbsync.php';
require_once __DIR__ . '/masters_config.php';
require_once __DIR__ . '/date_helper.php';

function ensureMasterTables($conn = null)
{
    static $ready = false;
    if ($ready) {
        return;
    }
    $ready = true;

    if ($conn === null) {
        $conn = getDBConnection();
    }

    // All CREATE / ALTER live in includes/dbsync.php
    syncDatabase($conn, true);
}

/**
 * If a status/source master has rows but no is_default, mark the first active row.
 * Does not insert any static master data.
 */
function ensureStatusMasterDefaults($conn = null)
{
    if ($conn === null) {
        $conn = getDBConnection();
    }
    foreach (['inquiry_statuses', 'follow_up_statuses', 'inquiry_sources'] as $table) {
        if (!dbTableExists($conn, $table)) {
            continue;
        }
        $cols = dbTableColumns($conn, $table);
        if (!in_array('is_default', $cols, true)) {
            continue;
        }
        $cnt = $conn->query("SELECT COUNT(*) AS c FROM `{$table}` WHERE status = 1 AND is_default = 1");
        $has = $cnt ? (int) ($cnt->fetch_assoc()['c'] ?? 0) : 0;
        if ($has > 0) {
            continue;
        }
        $pick = $conn->query(
            "SELECT id FROM `{$table}` WHERE status = 1 ORDER BY sort_order ASC, id ASC LIMIT 1"
        );
        $pickId = ($pick && ($pr = $pick->fetch_assoc())) ? (int) $pr['id'] : 0;
        if ($pickId > 0) {
            $conn->query("UPDATE `{$table}` SET is_default = 1 WHERE id = {$pickId}");
        }
    }
}

/**
 * Keep only one is_default=1 per status master table
 */
function enforceSingleStatusDefault($table, $keepId)
{
    $table = preg_replace('/[^a-z0-9_]/', '', (string) $table);
    $keepId = (int) $keepId;
    if ($table === '' || $keepId <= 0) {
        return;
    }
    if (!in_array($table, ['inquiry_statuses', 'follow_up_statuses', 'inquiry_sources'], true)) {
        return;
    }
    $conn = getDBConnection();
    $cols = dbTableColumns($conn, $table);
    if (!in_array('is_default', $cols, true)) {
        return;
    }
    $conn->query("UPDATE `{$table}` SET is_default = 0 WHERE id <> {$keepId} AND is_default = 1");
}

/**
 * Map legacy inquiries.inquiry_status text → inquiry_status_id, then drop text column
 */
function migrateInquiryStatusIds($conn = null, array &$log = null)
{
    if ($conn === null) {
        $conn = getDBConnection();
    }
    if ($log === null) {
        $log = [];
    }
    if (!dbTableExists($conn, 'inquiries') || !dbTableExists($conn, 'inquiry_statuses')) {
        return;
    }

    $cols = dbTableColumns($conn, 'inquiries');
    if (!in_array('inquiry_status_id', $cols, true)) {
        return;
    }

    // Map by legacy text column if present
    if (in_array('inquiry_status', $cols, true)) {
        $conn->query(
            "UPDATE inquiries i
             INNER JOIN inquiry_statuses s ON s.name = i.inquiry_status AND s.status = 1
             SET i.inquiry_status_id = s.id
             WHERE (i.inquiry_status_id IS NULL OR i.inquiry_status_id = 0)
               AND i.inquiry_status IS NOT NULL AND i.inquiry_status <> ''"
        );
        $log[] = ['type' => 'ok', 'msg' => 'Migrated inquiry_status text → inquiry_status_id'];

        if (function_exists('dbDropColumnIfExists')) {
            dbDropColumnIfExists($conn, 'inquiries', 'inquiry_status', $log);
        }
    }

    // Default remaining blanks to master default / first active status
    $defaultId = 0;
    $res = $conn->query(
        'SELECT id FROM inquiry_statuses WHERE status = 1 ORDER BY is_default DESC, sort_order ASC, id ASC LIMIT 1'
    );
    if ($res && ($row = $res->fetch_assoc())) {
        $defaultId = (int) $row['id'];
    }
    if ($defaultId > 0) {
        $conn->query(
            'UPDATE inquiries SET inquiry_status_id = ' . (int) $defaultId
            . ' WHERE inquiry_status_id IS NULL OR inquiry_status_id = 0'
        );
    }
}

function countMasterRows($table)
{
    $conn = getDBConnection();
    ensureMasterTables($conn);
    $table = preg_replace('/[^a-z0-9_]/', '', $table);
    $res = $conn->query("SELECT COUNT(*) AS c FROM `{$table}` WHERE status = 1");
    return $res ? (int) $res->fetch_assoc()['c'] : 0;
}

function getMasterRow($table, $id)
{
    $conn = getDBConnection();
    ensureMasterTables($conn);
    $table = preg_replace('/[^a-z0-9_]/', '', $table);
    $id = (int) $id;
    $stmt = $conn->prepare("SELECT * FROM `{$table}` WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row;
}

function getActiveMasterRows($table, $orderBy = 'id ASC')
{
    $conn = getDBConnection();
    ensureMasterTables($conn);
    $table = preg_replace('/[^a-z0-9_]/', '', $table);
    $orderBy = preg_replace('/[^a-z0-9_, ]/', '', $orderBy);
    if ($orderBy === '') {
        $orderBy = 'id ASC';
    }
    $rows = [];
    $res = $conn->query("SELECT * FROM `{$table}` WHERE status = 1 ORDER BY {$orderBy}");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function softDeleteMasterRow($table, $id)
{
    $conn = getDBConnection();
    ensureMasterTables($conn);
    $table = preg_replace('/[^a-z0-9_]/', '', $table);
    $id = (int) $id;
    $stmt = $conn->prepare("UPDATE `{$table}` SET status = 0 WHERE id = ? AND status = 1");
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $ok && $affected > 0;
}

/**
 * Restore soft-deleted master row (status 0 → 1)
 */
function restoreMasterRow($table, $id)
{
    $conn = getDBConnection();
    ensureMasterTables($conn);
    $table = preg_replace('/[^a-z0-9_]/', '', $table);
    $id = (int) $id;
    $stmt = $conn->prepare("UPDATE `{$table}` SET status = 1 WHERE id = ? AND status = 0");
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $ok && $affected > 0;
}

function collectMasterPostData(array $config, array $post)
{
    $data = [];
    foreach ($config['fields'] as $field) {
        $name = $field['name'];
        $raw = $post[$name] ?? ($field['default'] ?? '');
        if (is_string($raw)) {
            $raw = trim($raw);
        }
        $type = $field['type'];
        $isFk = !empty($field['options_from']) || substr($name, -3) === '_id';
        if ($type === 'checkbox') {
            // Last posted value wins (hidden 0 + checkbox 1)
            if (is_array($post[$name] ?? null)) {
                $arr = $post[$name];
                $raw = end($arr);
            }
            $data[$name] = ((string) $raw === '1' || (int) $raw === 1) ? 1 : 0;
        } elseif ($type === 'number' || ($type === 'select' && $isFk) || (!empty($field['bind']) && $field['bind'] === 'int')) {
            if ($raw === '' || $raw === null) {
                $data[$name] = isset($field['default']) ? (int) $field['default'] : 0;
            } else {
                $data[$name] = (strpos((string) ($field['step'] ?? ''), '.') !== false)
                    || (!empty($field['bind']) && $field['bind'] === 'float')
                    ? (float) $raw
                    : (int) $raw;
            }
        } elseif ($type === 'date') {
            $data[$name] = ($raw === '' || $raw === null) ? null : parseDateInput($raw);
        } elseif ($type === 'time') {
            $data[$name] = ($raw === '') ? null : $raw;
        } else {
            $data[$name] = $raw;
        }
    }
    return $data;
}

function validateMasterData(array $config, array $data)
{
    foreach ($config['fields'] as $field) {
        if (!empty($field['required'])) {
            $v = $data[$field['name']] ?? '';
            if ($v === '' || $v === null || $v === 0 || $v === '0') {
                return $field['label'] . ' is required.';
            }
        }
    }
    return '';
}

/**
 * Active options for FK select fields: [id => name]
 */
function getMasterSelectOptions($table, $orderBy = 'name ASC')
{
    $table = preg_replace('/[^a-z0-9_]/', '', (string) $table);
    if ($table === '') {
        return [];
    }
    $rows = getActiveMasterRows($table, $orderBy);
    $opts = [];
    foreach ($rows as $r) {
        $opts[(int) $r['id']] = (string) ($r['name'] ?? ('#' . $r['id']));
    }
    return $opts;
}

/**
 * id => name map (cached per request)
 */
function getMasterNameMap($table)
{
    static $cache = [];
    $table = preg_replace('/[^a-z0-9_]/', '', (string) $table);
    if ($table === '') {
        return [];
    }
    if (!isset($cache[$table])) {
        $cache[$table] = getMasterSelectOptions($table);
    }
    return $cache[$table];
}

/**
 * Child IDs by parent column / parent ID list (no JOIN on main list query)
 * @param int|int[] $parentId
 * @return int[]
 */
function getMasterIdsByParent($table, $parentColumn, $parentId, $activeOnly = true)
{
    $table = preg_replace('/[^a-z0-9_]/', '', (string) $table);
    $parentColumn = preg_replace('/[^a-z0-9_]/', '', (string) $parentColumn);
    $parentIds = is_array($parentId) ? $parentId : [(int) $parentId];
    $parentIds = array_values(array_unique(array_filter(array_map('intval', $parentIds), static function ($v) {
        return $v > 0;
    })));
    if ($table === '' || $parentColumn === '' || $parentIds === []) {
        return [];
    }

    $conn = getDBConnection();
    ensureMasterTables($conn);
    $ph = implode(',', array_fill(0, count($parentIds), '?'));
    $sql = "SELECT id FROM `{$table}` WHERE `{$parentColumn}` IN ({$ph})" . ($activeOnly ? ' AND status = 1' : '');
    $stmt = $conn->prepare($sql);
    $types = str_repeat('i', count($parentIds));
    $stmt->bind_param($types, ...$parentIds);
    $stmt->execute();
    $res = $stmt->get_result();
    $ids = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $ids[] = (int) $row['id'];
        }
    }
    $stmt->close();
    return $ids;
}

/**
 * Build "col IN (?,?,…)" bind bits; empty IDs → false filter (1=0)
 * @param int[] $ids
 * @return array{0:string,1:string,2:int[]} sqlFragment, types, params
 */
function masterInClause($column, array $ids)
{
    $column = preg_replace('/[^a-z0-9_.]/', '', (string) $column);
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static function ($v) {
        return $v > 0;
    })));
    if ($ids === []) {
        return ['1=0', '', []];
    }
    $ph = implode(',', array_fill(0, count($ids), '?'));
    return ["{$column} IN ({$ph})", str_repeat('i', count($ids)), $ids];
}

/**
 * Resolve select options for a field (static or options_from table)
 */
function resolveMasterFieldOptions(array $field)
{
    if (!empty($field['options']) && is_array($field['options'])) {
        return $field['options'];
    }
    if (!empty($field['options_from'])) {
        $from = $field['options_from'];
        $table = is_array($from) ? ($from['table'] ?? '') : $from;
        return getMasterSelectOptions($table);
    }
    return [];
}

function saveMasterRow(array $config, array $data, $id = 0)
{
    $conn = getDBConnection();
    ensureMasterTables($conn);

    $table = preg_replace('/[^a-z0-9_]/', '', $config['table']);
    $cols = array_keys($data);
    $id = (int) $id;

    foreach ($cols as $c) {
        if ($data[$c] === null) {
            $data[$c] = '';
        }
    }

    if ($id > 0) {
        $sets = [];
        $types = '';
        $values = [];
        foreach ($cols as $c) {
            if ($data[$c] === '' && isMasterDateTimeField($config, $c)) {
                $sets[] = "`{$c}` = NULL";
            } else {
                $sets[] = "`{$c}` = ?";
                $types .= masterBindType($data[$c]);
                $values[] = $data[$c];
            }
        }
        $sql = "UPDATE `{$table}` SET " . implode(', ', $sets) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $types .= 'i';
        $values[] = $id;
        if ($types !== 'i') {
            $stmt->bind_param($types, ...$values);
        } else {
            $stmt->bind_param('i', $id);
        }
        $ok = $stmt->execute();
        $error = $stmt->error;
        $stmt->close();
        if ($ok && !empty($data['is_default'])) {
            enforceSingleStatusDefault($table, $id);
        }
        return ['ok' => $ok, 'id' => $id, 'error' => $error];
    }

    $colParts = [];
    $phParts = [];
    $types = '';
    $values = [];
    foreach ($cols as $c) {
        $colParts[] = "`{$c}`";
        if ($data[$c] === '' && isMasterDateTimeField($config, $c)) {
            $phParts[] = 'NULL';
        } else {
            $phParts[] = '?';
            $types .= masterBindType($data[$c]);
            $values[] = $data[$c];
        }
    }
    $sql = "INSERT INTO `{$table}` (" . implode(', ', $colParts) . ", status) VALUES (" . implode(', ', $phParts) . ", 1)";
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$values);
    }
    $ok = $stmt->execute();
    $error = $stmt->error;
    $newId = (int) $conn->insert_id;
    $stmt->close();
    if ($ok && $newId > 0 && !empty($data['is_default'])) {
        enforceSingleStatusDefault($table, $newId);
    }
    return ['ok' => $ok, 'id' => $newId, 'error' => $error];
}

function isMasterDateTimeField(array $config, $fieldName)
{
    foreach ($config['fields'] as $f) {
        if ($f['name'] === $fieldName && in_array($f['type'], ['date', 'time'], true)) {
            return true;
        }
    }
    return false;
}

function masterBindType($value)
{
    if (is_int($value)) {
        return 'i';
    }
    if (is_float($value)) {
        return 'd';
    }
    return 's';
}

function formatMasterCell($key, $value, array $col = [])
{
    if (!empty($col['fk_table'])) {
        if ($value === null || $value === '' || (int) $value <= 0) {
            return '<span class="crm-cell-empty">—</span>';
        }
        $map = getMasterNameMap($col['fk_table']);
        $name = $map[(int) $value] ?? '';
        if ($name === '') {
            return '<span class="crm-cell-empty">—</span>';
        }
        return '<span class="crm-name-cell">' . htmlspecialchars($name) . '</span>';
    }

    if ($value === null || $value === '') {
        return '<span class="crm-cell-empty">—</span>';
    }

    if ($key === 'color' || $key === 'icon_color') {
        $c = htmlspecialchars((string) $value);
        return '<span class="crm-color-cell">'
            . '<span class="color-swatch" style="background:' . $c . '"></span>'
            . '<code class="crm-color-hex">' . $c . '</code>'
            . '</span>';
    }

    if ($key === 'price' || $key === 'default_value') {
        return '<span class="crm-price">₹&nbsp;' . number_format((float) $value, 2) . '</span>';
    }

    if ($key === 'type') {
        $t = strtolower(trim((string) $value));
        $cls = $t === 'product' ? 'is-product' : 'is-service';
        return '<span class="crm-type-badge ' . $cls . '">' . htmlspecialchars((string) $value) . '</span>';
    }

    if ($key === 'code') {
        return '<span class="code-badge">' . htmlspecialchars((string) $value) . '</span>';
    }

    if ($key === 'is_default') {
        if ((int) $value === 1) {
            return '<span class="inq-status-badge is-converted">Default</span>';
        }
        return '<span class="crm-cell-empty">—</span>';
    }

    if ($key === 'sort_order') {
        return '<span class="crm-sort-pill">' . (int) $value . '</span>';
    }

    if ($key === 'name' || $key === 'title') {
        return '<span class="crm-name-cell">' . htmlspecialchars((string) $value) . '</span>';
    }

    return htmlspecialchars((string) $value);
}

function masterAjaxListJson(array $config)
{
    $table = preg_replace('/[^a-z0-9_]/', '', $config['table']);
    $nameField = $config['name_field'];
    $listCols = $config['list_columns'];
    $folder = $config['folder'];
    $key = $config['key'] ?? $folder;

    $conn = getDBConnection();
    ensureMasterTables($conn);

    $countryId    = (int) ($_POST['country_id'] ?? 0);
    $stateId      = (int) ($_POST['state_id'] ?? 0);
    $cityId       = (int) ($_POST['city_id'] ?? 0);
    $departmentId = (int) ($_POST['department_id'] ?? 0);
    $q            = trim((string) ($_POST['q'] ?? ''));
    $recordStatus = strtolower(trim((string) ($_POST['record_status'] ?? 'active')));
    if (!in_array($recordStatus, ['active', 'deleted', 'all'], true)) {
        $recordStatus = 'active';
    }

    // Only users with delete right can browse deleted / restore
    $canDelete = function_exists('canAccess') ? canAccess('masters', 'delete') : false;
    if (!$canDelete && $recordStatus !== 'active') {
        $recordStatus = 'active';
    }

    $where = [];
    if ($recordStatus === 'active') {
        $where[] = 't.status = 1';
    } elseif ($recordStatus === 'deleted') {
        $where[] = 't.status = 0';
    }
    // 'all' → no status filter

    $types = '';
    $params = [];
    $productCategoryId = (int) ($_POST['product_category_id'] ?? 0);

    if ($key === 'states') {
        if ($countryId > 0) {
            $where[] = 't.country_id = ?';
            $types .= 'i';
            $params[] = $countryId;
        }
        if ($q !== '') {
            $where[] = 't.name LIKE ?';
            $types .= 's';
            $params[] = '%' . $q . '%';
        }
    } elseif ($key === 'cities') {
        // No JOIN: filter by state_id, or resolve state IDs for country
        if ($stateId > 0) {
            $where[] = 't.state_id = ?';
            $types .= 'i';
            $params[] = $stateId;
        } elseif ($countryId > 0) {
            [$frag, $inTypes, $inParams] = masterInClause('t.state_id', getMasterIdsByParent('states', 'country_id', $countryId));
            $where[] = $frag;
            $types .= $inTypes;
            foreach ($inParams as $p) {
                $params[] = $p;
            }
        }
        if ($q !== '') {
            $where[] = 't.name LIKE ?';
            $types .= 's';
            $params[] = '%' . $q . '%';
        }
    } elseif ($key === 'areas') {
        // No JOIN: city_id direct, or resolve city IDs from state/country
        if ($cityId > 0) {
            $where[] = 't.city_id = ?';
            $types .= 'i';
            $params[] = $cityId;
        } elseif ($stateId > 0) {
            [$frag, $inTypes, $inParams] = masterInClause('t.city_id', getMasterIdsByParent('cities', 'state_id', $stateId));
            $where[] = $frag;
            $types .= $inTypes;
            foreach ($inParams as $p) {
                $params[] = $p;
            }
        } elseif ($countryId > 0) {
            $stateIds = getMasterIdsByParent('states', 'country_id', $countryId);
            $cityIds = getMasterIdsByParent('cities', 'state_id', $stateIds);
            [$frag, $inTypes, $inParams] = masterInClause('t.city_id', $cityIds);
            $where[] = $frag;
            $types .= $inTypes;
            foreach ($inParams as $p) {
                $params[] = $p;
            }
        }
        if ($q !== '') {
            $where[] = 't.name LIKE ?';
            $types .= 's';
            $params[] = '%' . $q . '%';
        }
    } elseif ($key === 'designations') {
        if ($departmentId > 0) {
            $where[] = 't.department_id = ?';
            $types .= 'i';
            $params[] = $departmentId;
        }
        if ($q !== '') {
            $where[] = 't.name LIKE ?';
            $types .= 's';
            $params[] = '%' . $q . '%';
        }
    } elseif ($key === 'product_subcategories') {
        if ($productCategoryId > 0) {
            $where[] = 't.product_category_id = ?';
            $types .= 'i';
            $params[] = $productCategoryId;
        }
        if ($q !== '') {
            $where[] = '(t.name LIKE ? OR IFNULL(t.code, \'\') LIKE ?)';
            $types .= 'ss';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }
    } elseif ($key === 'products') {
        $productSubcategoryId = (int) ($_POST['product_subcategory_id'] ?? 0);
        if ($productSubcategoryId > 0) {
            $where[] = 't.product_subcategory_id = ?';
            $types .= 'i';
            $params[] = $productSubcategoryId;
        } elseif ($productCategoryId > 0) {
            $where[] = 't.product_category_id = ?';
            $types .= 'i';
            $params[] = $productCategoryId;
        }
        if ($q !== '') {
            $where[] = '(t.name LIKE ? OR IFNULL(t.code, \'\') LIKE ?)';
            $types .= 'ss';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }
    } elseif ($key === 'departments' || $key === 'countries' || $key === 'product_categories') {
        if ($q !== '') {
            $where[] = '(t.name LIKE ? OR IFNULL(t.code, \'\') LIKE ?)';
            $types .= 'ss';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }
    } else {
        if ($q !== '') {
            $where[] = '(t.name LIKE ? OR IFNULL(t.code, \'\') LIKE ?)';
            $types .= 'ss';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }
    }

    $orderCol = preg_replace('/[^a-z0-9_]/', '', (string) ($listCols[0]['key'] ?? 'id'));
    if ($orderCol === '' || substr($orderCol, -3) === '_id') {
        $orderCol = 'name';
    }

    if ($where === []) {
        $where[] = '1=1';
    }
    // Single-table SELECT — FK labels resolved via getMasterNameMap (no JOIN)
    $sql = "SELECT t.* FROM `{$table}` t WHERE " . implode(' AND ', $where)
         . " ORDER BY t.`{$orderCol}` ASC, t.id ASC";

    if ($types !== '') {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
        $stmt = null;
    }

    $data = [];
    $sr = 1;
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $id = (int) $row['id'];
            $editUrl = app_url("masters/{$folder}/edit.php?id={$id}");
            $delUrl  = app_url("masters/{$folder}/delete.php?id={$id}");
            $label   = $row[$nameField] ?? ('#' . $id);

            $isActive = (int) ($row['status'] ?? 1) === 1;
            $restoreUrl = app_url("masters/{$folder}/restore.php?id={$id}");

            $item = ['<span class="crm-sr">' . ($sr++) . '</span>'];
            foreach ($listCols as $c) {
                $item[] = formatMasterCell($c['key'], $row[$c['key']] ?? null, $c);
            }

            $actions = '<div class="action-links" onclick="event.stopPropagation();">';
            if ($isActive) {
                $actions .= '<a href="' . htmlspecialchars($editUrl) . '" class="action-btn edit" title="Edit"><i class="fa-solid fa-pen"></i></a>';
                if ($canDelete) {
                    $actions .= '<a href="' . htmlspecialchars($delUrl) . '" class="action-btn delete btn-delete" data-ajax="1" data-name="' . htmlspecialchars((string) $label) . '" title="Delete"><i class="fa-solid fa-trash"></i></a>';
                }
            } else {
                $actions .= '<span class="crm-status-badge is-deleted">Deleted</span>';
                if ($canDelete) {
                    $actions .= '<a href="' . htmlspecialchars($restoreUrl) . '" class="action-btn restore btn-restore" data-ajax="1" data-name="' . htmlspecialchars((string) $label) . '" title="Restore"><i class="fa-solid fa-rotate-left"></i></a>';
                }
            }
            $actions .= '</div>';
            $item[] = $actions;

            $data[] = $item;
        }
    }

    if ($stmt) {
        $stmt->close();
    }

    return ['data' => $data];
}
