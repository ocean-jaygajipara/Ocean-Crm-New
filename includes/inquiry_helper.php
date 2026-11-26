<?php
/**
 * Inquiry module helpers
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/dbsync.php';
require_once __DIR__ . '/date_helper.php';

function ensureInquiryTables($conn = null)
{
    static $ready = false;
    if ($ready) {
        return;
    }
    $ready = true;
    if ($conn === null) {
        $conn = getDBConnection();
    }
    syncDatabase($conn, false);

    if (!function_exists('ensureStatusMasterDefaults') || !function_exists('migrateInquiryStatusIds')) {
        require_once __DIR__ . '/master_helper.php';
    }
    if (function_exists('ensureStatusMasterDefaults')) {
        ensureStatusMasterDefaults($conn);
    }
    $log = [];
    migrateInquiryStatusIds($conn, $log);
}

/**
 * Active inquiry statuses: id => name
 */
function getInquiryStatuses()
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $conn = getDBConnection();
    ensureInquiryTables($conn);
    $cache = [];
    $res = $conn->query(
        'SELECT id, name FROM inquiry_statuses WHERE status = 1 ORDER BY sort_order ASC, name ASC'
    );
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $cache[(int) $row['id']] = (string) $row['name'];
        }
    }
    return $cache;
}

/**
 * Full status rows (id, name, code, color) for badges
 */
function getInquiryStatusRows()
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $conn = getDBConnection();
    ensureInquiryTables($conn);
    $cache = [];
    $res = $conn->query(
        'SELECT id, name, code, color, is_default FROM inquiry_statuses WHERE status = 1 ORDER BY is_default DESC, sort_order ASC, name ASC'
    );
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $id = (int) $row['id'];
            $cache[$id] = [
                'id'         => $id,
                'name'       => (string) $row['name'],
                'code'       => (string) ($row['code'] ?? ''),
                'color'      => (string) ($row['color'] ?? ''),
                'is_default' => (int) ($row['is_default'] ?? 0),
            ];
        }
    }
    return $cache;
}

function getDefaultInquiryStatusId()
{
    $rows = getInquiryStatusRows();
    foreach ($rows as $row) {
        if (!empty($row['is_default'])) {
            return (int) $row['id'];
        }
    }
    $first = reset($rows);
    return $first ? (int) $first['id'] : 0;
}

/**
 * Active follow-up statuses: id => name
 */
function getFollowUpStatuses()
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $conn = getDBConnection();
    ensureInquiryTables($conn);
    $cache = [];
    $res = $conn->query(
        'SELECT id, name FROM follow_up_statuses WHERE status = 1 ORDER BY sort_order ASC, name ASC'
    );
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $cache[(int) $row['id']] = (string) $row['name'];
        }
    }
    return $cache;
}

function getFollowUpStatusRows()
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $conn = getDBConnection();
    ensureInquiryTables($conn);
    $cache = [];
    $res = $conn->query(
        'SELECT id, name, code, color, is_default FROM follow_up_statuses WHERE status = 1 ORDER BY is_default DESC, sort_order ASC, name ASC'
    );
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $id = (int) $row['id'];
            $cache[$id] = [
                'id'         => $id,
                'name'       => (string) $row['name'],
                'code'       => (string) ($row['code'] ?? ''),
                'color'      => (string) ($row['color'] ?? ''),
                'is_default' => (int) ($row['is_default'] ?? 0),
            ];
        }
    }
    return $cache;
}

function getDefaultFollowUpStatusId()
{
    $rows = getFollowUpStatusRows();
    foreach ($rows as $row) {
        if (!empty($row['is_default'])) {
            return (int) $row['id'];
        }
    }
    $first = reset($rows);
    return $first ? (int) $first['id'] : 0;
}

/**
 * Active inquiry sources: id => name
 */
function getInquirySources()
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $conn = getDBConnection();
    ensureInquiryTables($conn);
    $cache = [];
    $res = $conn->query(
        'SELECT id, name FROM inquiry_sources WHERE status = 1 ORDER BY sort_order ASC, name ASC'
    );
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $cache[(int) $row['id']] = (string) $row['name'];
        }
    }
    return $cache;
}

function getInquirySourceRows()
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $conn = getDBConnection();
    ensureInquiryTables($conn);
    $cache = [];
    $res = $conn->query(
        'SELECT id, name, code, color, is_default FROM inquiry_sources WHERE status = 1 ORDER BY is_default DESC, sort_order ASC, name ASC'
    );
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $id = (int) $row['id'];
            $cache[$id] = [
                'id'         => $id,
                'name'       => (string) $row['name'],
                'code'       => (string) ($row['code'] ?? ''),
                'color'      => (string) ($row['color'] ?? ''),
                'is_default' => (int) ($row['is_default'] ?? 0),
            ];
        }
    }
    return $cache;
}

function getDefaultInquirySourceId()
{
    $rows = getInquirySourceRows();
    foreach ($rows as $row) {
        if (!empty($row['is_default'])) {
            return (int) $row['id'];
        }
    }
    $first = reset($rows);
    return $first ? (int) $first['id'] : 0;
}

function inquirySourceBadge($statusIdOrName)
{
    return crmStatusBadgeHtml($statusIdOrName, getInquirySourceRows(), '—');
}

function generateInquiryNo($conn = null)
{
    if ($conn === null) {
        $conn = getDBConnection();
    }

    if (!function_exists('getInquiryNoPrefix')) {
        require_once __DIR__ . '/settings.php';
    }
    $code = getInquiryNoPrefix();
    $year = date('Y');
    $prefix = $code . '-' . $year . '-';

    $stmt = $conn->prepare("SELECT inquiry_no FROM inquiries WHERE inquiry_no LIKE ? ORDER BY id DESC LIMIT 1");
    $like = $prefix . '%';
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $next = 1;
    if ($row && preg_match('/(\d+)$/', (string) $row['inquiry_no'], $m)) {
        $next = ((int) $m[1]) + 1;
    }
    return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
}

function getInquiryRow($id)
{
    $conn = getDBConnection();
    ensureInquiryTables($conn);
    $id = (int) $id;
    $stmt = $conn->prepare('SELECT * FROM inquiries WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row;
}

/**
 * Normalize phone for duplicate compare (digits only; keep leading +)
 */
function normalizeInquiryPhone($phone)
{
    $phone = trim((string) $phone);
    if ($phone === '') {
        return '';
    }
    $hasPlus = strpos($phone, '+') === 0;
    $digits = preg_replace('/\D+/', '', $phone);
    if ($digits === '') {
        return '';
    }
    return $hasPlus ? ('+' . $digits) : $digits;
}

/**
 * Find inquiry with same inquiry_no (exclude $excludeId on edit)
 */
function findInquiryByNo($inquiryNo, $excludeId = 0)
{
    $inquiryNo = trim((string) $inquiryNo);
    if ($inquiryNo === '') {
        return null;
    }
    $conn = getDBConnection();
    ensureInquiryTables($conn);
    $excludeId = (int) $excludeId;
    if ($excludeId > 0) {
        $stmt = $conn->prepare(
            'SELECT id, inquiry_no, customer_name FROM inquiries WHERE inquiry_no = ? AND id <> ? LIMIT 1'
        );
        $stmt->bind_param('si', $inquiryNo, $excludeId);
    } else {
        $stmt = $conn->prepare(
            'SELECT id, inquiry_no, customer_name FROM inquiries WHERE inquiry_no = ? LIMIT 1'
        );
        $stmt->bind_param('s', $inquiryNo);
    }
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/**
 * Find active inquiry with same phone (exclude $excludeId on edit)
 */
function findInquiryByPhone($phone, $excludeId = 0)
{
    $conn = getDBConnection();
    ensureInquiryTables($conn);
    $normalized = normalizeInquiryPhone($phone);
    if ($normalized === '') {
        return null;
    }

    $excludeId = (int) $excludeId;
    $sql = 'SELECT id, inquiry_no, customer_name, phone
            FROM inquiries
            WHERE status = 1';
    if ($excludeId > 0) {
        $sql .= ' AND id <> ' . $excludeId;
    }
    $sql .= ' ORDER BY id DESC';

    $res = $conn->query($sql);
    if (!$res) {
        return null;
    }
    while ($row = $res->fetch_assoc()) {
        if (normalizeInquiryPhone($row['phone'] ?? '') === $normalized) {
            return $row;
        }
    }
    return null;
}

function softDeleteInquiry($id)
{
    $conn = getDBConnection();
    ensureInquiryTables($conn);
    $id = (int) $id;
    $stmt = $conn->prepare('UPDATE inquiries SET status = 0 WHERE id = ? AND status = 1');
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $n = $stmt->affected_rows;
    $stmt->close();
    return $ok && $n > 0;
}

function restoreInquiry($id)
{
    $conn = getDBConnection();
    ensureInquiryTables($conn);
    $id = (int) $id;
    $stmt = $conn->prepare('UPDATE inquiries SET status = 1 WHERE id = ? AND status = 0');
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $n = $stmt->affected_rows;
    $stmt->close();
    return $ok && $n > 0;
}

function saveInquiry(array $data, $id = 0, $userId = 0)
{
    $conn = getDBConnection();
    ensureInquiryTables($conn);

    $id = (int) $id;
    $userId = (int) $userId;
    $inquiryDate = normalizeDatePost($data['inquiry_date'] ?? '', false);
    if ($inquiryDate === null || $inquiryDate === '') {
        $inquiryDate = date('Y-m-d');
    }
    $customerName = trim((string) ($data['customer_name'] ?? ''));
    $companyName = trim((string) ($data['company_name'] ?? ''));
    $email = trim((string) ($data['email'] ?? ''));
    $phone = trim((string) ($data['phone'] ?? ''));
    $whatsapp = trim((string) ($data['whatsapp'] ?? ''));
    $countryId = (int) ($data['country_id'] ?? 0);
    $stateId = (int) ($data['state_id'] ?? 0);
    $cityId = (int) ($data['city_id'] ?? 0);
    $areaId = (int) ($data['area_id'] ?? 0);
    $address = trim((string) ($data['address'] ?? ''));
    $requirementDetails = trim((string) ($data['requirement_details'] ?? ''));

    $statusId = (int) ($data['inquiry_status_id'] ?? 0);
    $statuses = getInquiryStatuses();
    if ($statusId <= 0 || !isset($statuses[$statusId])) {
        $statusId = getDefaultInquiryStatusId();
    }

    $fuStatusId = (int) ($data['follow_up_status_id'] ?? 0);
    $fuStatuses = getFollowUpStatuses();
    if ($fuStatusId < 0 || ($fuStatusId > 0 && !isset($fuStatuses[$fuStatusId]))) {
        $fuStatusId = getDefaultFollowUpStatusId();
    }

    $sourceId = (int) ($data['inquiry_source_id'] ?? 0);
    $sources = getInquirySources();
    if ($sourceId <= 0 || !isset($sources[$sourceId])) {
        $sourceId = getDefaultInquirySourceId();
    }

    $nextFuDate = normalizeDatePost($data['follow_up_date'] ?? '');
    $assignTo = (int) ($data['assign_to'] ?? 0);
    if ($assignTo < 0) {
        $assignTo = 0;
    }
    // Admin can set Created By; sales/other users stay locked to themselves
    $canEditCreatedBy = function_exists('isAdmin') ? isAdmin() : false;
    if ($canEditCreatedBy) {
        $createdBy = (int) ($data['created_by'] ?? 0);
        if ($createdBy <= 0) {
            $createdBy = $userId;
        }
    } else {
        $createdBy = $userId > 0 ? $userId : 0;
    }

    if ($customerName === '') {
        return ['ok' => false, 'error' => 'Customer Name is required.', 'id' => 0];
    }

    if (!function_exists('getInquiryPhoneMaxLength')) {
        require_once __DIR__ . '/settings.php';
    }
    $phoneMaxLen = getInquiryPhoneMaxLength();
    if ($phone !== '') {
        if (!isValidInquiryPhoneChars($phone)) {
            return [
                'ok'    => false,
                'error' => 'Phone may only contain digits and special characters (+ - ( ) . / space).',
                'id'    => $id,
            ];
        }
        if (mb_strlen($phone) > $phoneMaxLen) {
            return [
                'ok'    => false,
                'error' => 'Phone cannot exceed ' . $phoneMaxLen . ' characters.',
                'id'    => $id,
            ];
        }
        $dup = findInquiryByPhone($phone, $id);
        if ($dup) {
            $dupNo = (string) ($dup['inquiry_no'] ?? ('#' . (int) $dup['id']));
            $dupName = (string) ($dup['customer_name'] ?? '');
            $msg = 'Phone number already exists in inquiry ' . $dupNo;
            if ($dupName !== '') {
                $msg .= ' (' . $dupName . ')';
            }
            $msg .= '. Duplicate phone is not allowed.';
            return ['ok' => false, 'error' => $msg, 'id' => $id];
        }
    }
    if ($whatsapp !== '') {
        if (!isValidInquiryPhoneChars($whatsapp)) {
            return [
                'ok'    => false,
                'error' => 'WhatsApp may only contain digits and special characters (+ - ( ) . / space).',
                'id'    => $id,
            ];
        }
        if (mb_strlen($whatsapp) > $phoneMaxLen) {
            return [
                'ok'    => false,
                'error' => 'WhatsApp cannot exceed ' . $phoneMaxLen . ' characters.',
                'id'    => $id,
            ];
        }
    }

    if (!function_exists('isInquiryNoAuto')) {
        require_once __DIR__ . '/settings.php';
    }
    $inquiryNoAuto = isInquiryNoAuto();
    $inquiryNoManual = trim((string) ($data['inquiry_no'] ?? ''));

    if ($id > 0) {
        $existing = getInquiryRow($id);
        $inquiryNo = $existing ? (string) ($existing['inquiry_no'] ?? '') : '';
        if (!$inquiryNoAuto) {
            if ($inquiryNoManual === '') {
                return ['ok' => false, 'error' => 'Inquiry No is required.', 'id' => $id];
            }
            $dupNo = findInquiryByNo($inquiryNoManual, $id);
            if ($dupNo) {
                return ['ok' => false, 'error' => 'Inquiry No already exists.', 'id' => $id];
            }
            $inquiryNo = $inquiryNoManual;
        }

        if ($canEditCreatedBy) {
            $stmt = $conn->prepare(
                'UPDATE inquiries SET inquiry_no=?, inquiry_date=?, customer_name=?, company_name=?, email=?, phone=?, whatsapp=?,
                 country_id=?, state_id=?, city_id=?, area_id=?, address=?, requirement_details=?,
                 inquiry_status_id=?, follow_up_status_id=?, inquiry_source_id=?, follow_up_date=NULLIF(?, \'\' ),
                 created_by=?, assign_to=?
                 WHERE id=? AND status=1'
            );
            $stmt->bind_param(
                'sssssssiiiissiiisiii',
                $inquiryNo,
                $inquiryDate,
                $customerName,
                $companyName,
                $email,
                $phone,
                $whatsapp,
                $countryId,
                $stateId,
                $cityId,
                $areaId,
                $address,
                $requirementDetails,
                $statusId,
                $fuStatusId,
                $sourceId,
                $nextFuDate,
                $createdBy,
                $assignTo,
                $id
            );
        } else {
            $stmt = $conn->prepare(
                'UPDATE inquiries SET inquiry_no=?, inquiry_date=?, customer_name=?, company_name=?, email=?, phone=?, whatsapp=?,
                 country_id=?, state_id=?, city_id=?, area_id=?, address=?, requirement_details=?,
                 inquiry_status_id=?, follow_up_status_id=?, inquiry_source_id=?, follow_up_date=NULLIF(?, \'\' ),
                 assign_to=?
                 WHERE id=? AND status=1'
            );
            $stmt->bind_param(
                'sssssssiiiissiiisii',
                $inquiryNo,
                $inquiryDate,
                $customerName,
                $companyName,
                $email,
                $phone,
                $whatsapp,
                $countryId,
                $stateId,
                $cityId,
                $areaId,
                $address,
                $requirementDetails,
                $statusId,
                $fuStatusId,
                $sourceId,
                $nextFuDate,
                $assignTo,
                $id
            );
        }
    $ok = $stmt->execute();
    $err = $stmt->error;
    $stmt->close();
    if ($ok) {
        saveInquiryProducts($id, $data);
    }
    return ['ok' => $ok, 'error' => $err, 'id' => $id];
    }

    if ($inquiryNoAuto) {
        $inquiryNo = generateInquiryNo($conn);
    } else {
        if ($inquiryNoManual === '') {
            return ['ok' => false, 'error' => 'Inquiry No is required.', 'id' => 0];
        }
        if (findInquiryByNo($inquiryNoManual, 0)) {
            return ['ok' => false, 'error' => 'Inquiry No already exists.', 'id' => 0];
        }
        $inquiryNo = $inquiryNoManual;
    }

    $stmt = $conn->prepare(
        'INSERT INTO inquiries
         (inquiry_no, inquiry_date, customer_name, company_name, email, phone, whatsapp,
          country_id, state_id, city_id, area_id, address, requirement_details,
          inquiry_status_id, follow_up_status_id, inquiry_source_id, follow_up_date,
          created_by, assign_to, status)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NULLIF(?, \'\' ),?,?,1)'
    );
    $stmt->bind_param(
        'sssssssiiiissiiisii',
        $inquiryNo,
        $inquiryDate,
        $customerName,
        $companyName,
        $email,
        $phone,
        $whatsapp,
        $countryId,
        $stateId,
        $cityId,
        $areaId,
        $address,
        $requirementDetails,
        $statusId,
        $fuStatusId,
        $sourceId,
        $nextFuDate,
        $createdBy,
        $assignTo
    );
    $ok = $stmt->execute();
    $err = $stmt->error;
    $newId = (int) $conn->insert_id;
    $stmt->close();
    if ($ok && $newId > 0) {
        saveInquiryProducts($newId, $data);
        if (!function_exists('notifyAdminsNewInquiry')) {
            require_once __DIR__ . '/notification_helper.php';
        }
        notifyAdminsNewInquiry($newId, $inquiryNo, $customerName, $createdBy);
    }
    return ['ok' => $ok, 'error' => $err, 'id' => $newId, 'inquiry_no' => $inquiryNo];
}

/**
 * Staff users for Created By / Assign To dropdowns: id => "Full Name (USERNAME)"
 */
function getInquiryUserOptions()
{
    $conn = getDBConnection();
    ensureInquiryTables($conn);
    $out = [];
    $res = $conn->query(
        "SELECT id, full_name, username FROM users
         WHERE status = 1
         ORDER BY full_name ASC, username ASC"
    );
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $name = trim((string) ($row['full_name'] ?? ''));
            $user = trim((string) ($row['username'] ?? ''));
            $label = $name !== '' ? $name : $user;
            if ($user !== '' && strcasecmp($name, $user) !== 0) {
                $label .= ' (' . $user . ')';
            }
            $out[(int) $row['id']] = $label;
        }
    }
    return $out;
}

function getInquiryUserName($userId)
{
    $userId = (int) $userId;
    if ($userId <= 0) {
        return '';
    }
    $opts = getInquiryUserOptions();
    if (isset($opts[$userId])) {
        return $opts[$userId];
    }
    $conn = getDBConnection();
    $stmt = $conn->prepare('SELECT full_name, username FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        return '';
    }
    $name = trim((string) ($row['full_name'] ?? ''));
    $user = trim((string) ($row['username'] ?? ''));
    if ($name !== '') {
        return $user !== '' ? $name . ' (' . $user . ')' : $name;
    }
    return $user;
}

/**
 * Active products for inquiry form dropdown: id => "Name (Code)"
 */
function getInquiryProductOptions()
{
    $conn = getDBConnection();
    ensureInquiryTables($conn);
    $out = [];
    $res = $conn->query(
        'SELECT id, name, code, unit FROM products WHERE status = 1 ORDER BY name ASC'
    );
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $label = (string) $row['name'];
            $code = trim((string) ($row['code'] ?? ''));
            if ($code !== '') {
                $label .= ' (' . $code . ')';
            }
            $out[(int) $row['id']] = $label;
        }
    }
    return $out;
}

/**
 * Product lines for one inquiry
 * @return array<int, array{product_id:int,qty:float}>
 */
function getInquiryProducts($inquiryId)
{
    $conn = getDBConnection();
    ensureInquiryTables($conn);
    $inquiryId = (int) $inquiryId;
    $rows = [];
    if ($inquiryId <= 0) {
        return $rows;
    }
    $stmt = $conn->prepare(
        'SELECT product_id, qty, requirements FROM inquiry_products WHERE inquiry_id = ? ORDER BY sort_order ASC, id ASC'
    );
    $stmt->bind_param('i', $inquiryId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $rows[] = [
            'product_id'    => (int) ($row['product_id'] ?? 0),
            'qty'           => (float) ($row['qty'] ?? 1),
            'requirements'  => (string) ($row['requirements'] ?? ''),
        ];
    }
    $stmt->close();
    return $rows;
}

/**
 * Replace inquiry product lines from POST-like data (product_id[] + qty[])
 */
function saveInquiryProducts($inquiryId, array $data)
{
    $conn = getDBConnection();
    ensureInquiryTables($conn);
    $inquiryId = (int) $inquiryId;
    if ($inquiryId <= 0) {
        return;
    }

    $del = $conn->prepare('DELETE FROM inquiry_products WHERE inquiry_id = ?');
    $del->bind_param('i', $inquiryId);
    $del->execute();
    $del->close();

    $productIds = $data['product_id'] ?? [];
    $qtys = $data['qty'] ?? [];
    $reqs = $data['line_requirements'] ?? [];
    if (!is_array($productIds)) {
        $productIds = [];
    }
    if (!is_array($qtys)) {
        $qtys = [];
    }
    if (!is_array($reqs)) {
        $reqs = [];
    }

    $stmt = $conn->prepare(
        'INSERT INTO inquiry_products (inquiry_id, product_id, qty, requirements, sort_order) VALUES (?, ?, ?, ?, ?)'
    );
    $sort = 1;
    $count = max(count($productIds), count($qtys));
    for ($i = 0; $i < $count; $i++) {
        $pid = (int) ($productIds[$i] ?? 0);
        $qty = (float) ($qtys[$i] ?? 0);
        $reqText = trim((string) ($reqs[$i] ?? ''));
        if ($pid <= 0) {
            continue;
        }
        if ($qty <= 0) {
            $qty = 1;
        }
        $stmt->bind_param('iidsi', $inquiryId, $pid, $qty, $reqText, $sort);
        $stmt->execute();
        $sort++;
    }
    $stmt->close();
}

function crmStatusBadgeHtml($statusIdOrName, array $rows, $emptyLabel = '—')
{
    $name = '';
    $color = '';

    if ($statusIdOrName === null || $statusIdOrName === '' || (int) $statusIdOrName === 0) {
        return '<span class="crm-cell-empty">' . htmlspecialchars($emptyLabel) . '</span>';
    }

    if (is_numeric($statusIdOrName) && isset($rows[(int) $statusIdOrName])) {
        $name = $rows[(int) $statusIdOrName]['name'];
        $color = $rows[(int) $statusIdOrName]['color'];
    } else {
        $name = (string) $statusIdOrName;
        foreach ($rows as $row) {
            if (strcasecmp($row['name'], $name) === 0) {
                $color = $row['color'];
                $name = $row['name'];
                break;
            }
        }
    }

    if ($name === '') {
        return '<span class="crm-cell-empty">' . htmlspecialchars($emptyLabel) . '</span>';
    }

    $style = '';
    if ($color !== '') {
        $hex = preg_replace('/[^#0-9a-fA-F]/', '', $color);
        if ($hex !== '') {
            if ($hex[0] !== '#') {
                $hex = '#' . $hex;
            }
            $style = ' style="background:' . htmlspecialchars($hex) . '22;color:' . htmlspecialchars($hex) . ';"';
        }
    }

    $fallbackClass = 'is-new';
    $lower = strtolower($name);
    if (strpos($lower, 'contact') !== false || strpos($lower, 'schedule') !== false) {
        $fallbackClass = 'is-contacted';
    } elseif (strpos($lower, 'progress') !== false || strpos($lower, 'pending') !== false) {
        $fallbackClass = 'is-progress';
    } elseif (strpos($lower, 'convert') !== false || strpos($lower, 'complete') !== false) {
        $fallbackClass = 'is-converted';
    } elseif (strpos($lower, 'close') !== false || strpos($lower, 'cancel') !== false) {
        $fallbackClass = 'is-closed';
    } elseif (strpos($lower, 'lost') !== false || strpos($lower, 'no response') !== false) {
        $fallbackClass = 'is-lost';
    }

    return '<span class="inq-status-badge ' . $fallbackClass . '"' . $style . '>'
        . htmlspecialchars($name)
        . '</span>';
}

function inquiryStatusBadge($statusIdOrName)
{
    return crmStatusBadgeHtml($statusIdOrName, getInquiryStatusRows(), 'New');
}

function followUpStatusBadge($statusIdOrName)
{
    return crmStatusBadgeHtml($statusIdOrName, getFollowUpStatusRows(), '—');
}

/**
 * Compact status <select> for inquiry list grid (AJAX update).
 *
 * @param array<int,array{id:int,name:string,color?:string}> $rows
 */
function inquiryGridStatusSelectHtml($inquiryId, $field, $selectedId, array $rows, $allowEmpty = false)
{
    $inquiryId = (int) $inquiryId;
    $selectedId = (int) $selectedId;
    $field = $field === 'follow_up_status_id' ? 'follow_up_status_id' : 'inquiry_status_id';
    $cls = 'inq-grid-status-select js-inq-grid-status'
        . ($field === 'follow_up_status_id' ? ' is-follow-up' : ' is-inquiry');

    $html = '<select class="' . $cls . '"'
        . ' data-id="' . $inquiryId . '"'
        . ' data-field="' . htmlspecialchars($field) . '"'
        . ' data-prev="' . $selectedId . '"'
        . ' onclick="event.stopPropagation();"'
        . ' title="Change status">';

    if ($allowEmpty) {
        $html .= '<option value="0"' . ($selectedId === 0 ? ' selected' : '') . '>— None —</option>';
    }

    foreach ($rows as $row) {
        $oid = (int) ($row['id'] ?? 0);
        if ($oid <= 0) {
            continue;
        }
        $name = (string) ($row['name'] ?? '');
        $color = trim((string) ($row['color'] ?? ''));
        $html .= '<option value="' . $oid . '"'
            . ($oid === $selectedId ? ' selected' : '')
            . ($color !== '' ? ' data-color="' . htmlspecialchars($color) . '"' : '')
            . '>' . htmlspecialchars($name) . '</option>';
    }
    $html .= '</select>';
    return $html;
}

/**
 * Quick update inquiry_status_id / follow_up_status_id from list grid.
 *
 * @return array{ok:bool,error?:string,badge?:string,status_id?:int}
 */
function updateInquiryStatusField($id, $field, $statusId)
{
    $id = (int) $id;
    $statusId = (int) $statusId;
    $field = $field === 'follow_up_status_id' ? 'follow_up_status_id' : 'inquiry_status_id';

    if ($id <= 0) {
        return ['ok' => false, 'error' => 'Invalid inquiry.'];
    }

    $row = getInquiryRow($id);
    if (!$row || (int) ($row['status'] ?? 0) !== 1) {
        return ['ok' => false, 'error' => 'Inquiry not found or deleted.'];
    }
    if (!canAccessInquiryRecord($row)) {
        return ['ok' => false, 'error' => 'Access denied.'];
    }

    if ($field === 'inquiry_status_id') {
        if ($statusId <= 0 || !isset(getInquiryStatusRows()[$statusId])) {
            return ['ok' => false, 'error' => 'Invalid inquiry status.'];
        }
    } else {
        if ($statusId > 0 && !isset(getFollowUpStatusRows()[$statusId])) {
            return ['ok' => false, 'error' => 'Invalid follow up status.'];
        }
    }

    $conn = getDBConnection();
    ensureInquiryTables($conn);
    $sql = 'UPDATE inquiries SET `' . $field . '` = ? WHERE id = ? AND status = 1';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ['ok' => false, 'error' => 'Could not update status.'];
    }
    $stmt->bind_param('ii', $statusId, $id);
    $ok = $stmt->execute();
    $stmt->close();
    if (!$ok) {
        return ['ok' => false, 'error' => 'Could not update status.'];
    }

    $badge = $field === 'follow_up_status_id'
        ? followUpStatusBadge($statusId)
        : inquiryStatusBadge($statusId);

    return [
        'ok'        => true,
        'status_id' => $statusId,
        'badge'     => $badge,
        'field'     => $field,
    ];
}

/**
 * Non-admin sees own / assigned / team (reports_to hierarchy) inquiries
 */
function canAccessInquiryRecord(array $row)
{
    if (!function_exists('isAdmin')) {
        require_once __DIR__ . '/auth.php';
    }
    if (isAdmin()) {
        return true;
    }
    $uid = (int) ($_SESSION['user_id'] ?? 0);
    if ($uid <= 0) {
        return false;
    }
    if (!function_exists('getInquiryScopeUserIds')) {
        require_once __DIR__ . '/permission_helper.php';
    }
    $scope = getInquiryScopeUserIds($uid);
    $createdBy = (int) ($row['created_by'] ?? 0);
    $assignTo = (int) ($row['assign_to'] ?? 0);
    return in_array($createdBy, $scope, true) || in_array($assignTo, $scope, true);
}

function inquiryAjaxListJson()
{
    $conn = getDBConnection();
    ensureInquiryTables($conn);

    $q = trim((string) ($_POST['q'] ?? ''));
    $inquiryStatusId = (int) ($_POST['inquiry_status_id'] ?? 0);
    $followUpStatusId = (int) ($_POST['follow_up_status_id'] ?? 0);
    $inquirySourceId = (int) ($_POST['inquiry_source_id'] ?? 0);
    $countryId = (int) ($_POST['country_id'] ?? 0);
    $stateId = (int) ($_POST['state_id'] ?? 0);
    $cityId = (int) ($_POST['city_id'] ?? 0);
    $quickFilter = strtolower(trim((string) ($_POST['filter'] ?? '')));
    $recordStatus = strtolower(trim((string) ($_POST['record_status'] ?? 'active')));
    if (!in_array($recordStatus, ['active', 'deleted', 'all'], true)) {
        $recordStatus = 'active';
    }

    $canDelete = function_exists('canAccess') ? canAccess('inquiry', 'delete') : false;
    $canEdit = function_exists('canAccess') ? canAccess('inquiry', 'edit') : false;
    $canView = function_exists('canAccess') ? canAccess('inquiry', 'view') : false;
    $canConvert = function_exists('canAccess')
        ? (canAccess('customers', 'add') || canAccess('inquiry', 'edit'))
        : false;
    if (!$canDelete && $recordStatus !== 'active') {
        $recordStatus = 'active';
    }
    $inqStatusRows = getInquiryStatusRows();
    $fuStatusRows = getFollowUpStatusRows();

    $where = [];
    $types = '';
    $params = [];

    if ($recordStatus === 'active') {
        $where[] = 'i.status = 1';
    } elseif ($recordStatus === 'deleted') {
        $where[] = 'i.status = 0';
    }

    if ($quickFilter === 'today') {
        $where[] = 'i.inquiry_date = ?';
        $types .= 's';
        $params[] = date('Y-m-d');
    } elseif ($quickFilter === 'due_follow_up') {
        $where[] = 'i.follow_up_date IS NOT NULL AND i.follow_up_date <= ?';
        $types .= 's';
        $params[] = date('Y-m-d');
    }

    // Sales / role users: own + team (reports_to) created_by OR assign_to
    if (!function_exists('isAdmin')) {
        require_once __DIR__ . '/auth.php';
    }
    if (!isAdmin()) {
        if (!function_exists('getInquiryScopeUserIds')) {
            require_once __DIR__ . '/permission_helper.php';
        }
        $uid = (int) ($_SESSION['user_id'] ?? 0);
        $scopeIds = getInquiryScopeUserIds($uid);
        if (empty($scopeIds)) {
            $scopeIds = [$uid > 0 ? $uid : 0];
        }
        $ph = implode(',', array_fill(0, count($scopeIds), '?'));
        $where[] = "(i.created_by IN ({$ph}) OR i.assign_to IN ({$ph}))";
        $types .= str_repeat('i', count($scopeIds) * 2);
        foreach ($scopeIds as $sid) {
            $params[] = (int) $sid;
        }
        foreach ($scopeIds as $sid) {
            $params[] = (int) $sid;
        }
    }

    if ($inquiryStatusId > 0) {
        $where[] = 'i.inquiry_status_id = ?';
        $types .= 'i';
        $params[] = $inquiryStatusId;
    }
    if ($followUpStatusId > 0) {
        $where[] = 'i.follow_up_status_id = ?';
        $types .= 'i';
        $params[] = $followUpStatusId;
    }
    if ($inquirySourceId > 0) {
        $where[] = 'i.inquiry_source_id = ?';
        $types .= 'i';
        $params[] = $inquirySourceId;
    }
    if ($countryId > 0) {
        $where[] = 'i.country_id = ?';
        $types .= 'i';
        $params[] = $countryId;
    }
    if ($stateId > 0) {
        $where[] = 'i.state_id = ?';
        $types .= 'i';
        $params[] = $stateId;
    }
    if ($cityId > 0) {
        $where[] = 'i.city_id = ?';
        $types .= 'i';
        $params[] = $cityId;
    }
    if ($q !== '') {
        $where[] = '(i.inquiry_no LIKE ? OR i.customer_name LIKE ? OR i.company_name LIKE ? OR i.phone LIKE ? OR i.whatsapp LIKE ? OR i.email LIKE ? OR i.address LIKE ?)';
        $types .= 'sssssss';
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like, $like, $like, $like, $like);
    }
    if ($where === []) {
        $where[] = '1=1';
    }

    $sql = 'SELECT i.* FROM inquiries i WHERE ' . implode(' AND ', $where) . ' ORDER BY i.inquiry_date DESC, i.id DESC';
    if ($types !== '') {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
        $stmt = null;
    }

    if (!function_exists('getMasterNameMap')) {
        require_once __DIR__ . '/master_helper.php';
    }
    $countries = getMasterNameMap('countries');
    $states = getMasterNameMap('states');
    $cities = getMasterNameMap('cities');

    $data = [];
    $sr = 1;
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $id = (int) $row['id'];
            $isActive = (int) ($row['status'] ?? 1) === 1;
            $label = $row['customer_name'] ?: ($row['inquiry_no'] ?? ('#' . $id));
            $editUrl = app_url('inquiry/edit.php?id=' . $id);
            $delUrl = app_url('inquiry/delete.php?id=' . $id);
            $restoreUrl = app_url('inquiry/restore.php?id=' . $id);

            $cityName = $cities[(int) ($row['city_id'] ?? 0)] ?? '';
            $stateName = $states[(int) ($row['state_id'] ?? 0)] ?? '';
            $loc = trim($cityName . ($cityName && $stateName ? ', ' : '') . $stateName);
            if ($loc === '') {
                $loc = '<span class="crm-cell-empty">—</span>';
            } else {
                $loc = htmlspecialchars($loc);
            }

            $viewUrl = app_url('inquiry/view.php?id=' . $id);
            $convertUrl = app_url('inquiry/convert_customer.php?id=' . $id);
            $linkedCustomerId = (int) ($row['customer_id'] ?? 0);
            $escLabel = htmlspecialchars((string) $label);
            $menuItems = '';
            if ($canView) {
                $menuItems .= '<a href="' . htmlspecialchars($viewUrl) . '" class="crm-action-item" role="menuitem">'
                    . '<i class="fa-solid fa-eye"></i><span>View</span></a>';
            }
            if ($isActive) {
                if ($canEdit) {
                    $menuItems .= '<a href="' . htmlspecialchars($editUrl) . '" class="crm-action-item" role="menuitem">'
                        . '<i class="fa-solid fa-pen"></i><span>Edit</span></a>';
                }
                if ($linkedCustomerId > 0 && (canAccess('customers', 'view') || $canView)) {
                    $menuItems .= '<a href="' . htmlspecialchars(app_url('customers/view.php?id=' . $linkedCustomerId)) . '" class="crm-action-item is-success" role="menuitem">'
                        . '<i class="fa-solid fa-user-check"></i><span>View Customer</span></a>';
                } elseif ($canConvert && $linkedCustomerId <= 0) {
                    $menuItems .= '<a href="' . htmlspecialchars($convertUrl) . '" class="crm-action-item js-convert-customer" role="menuitem">'
                        . '<i class="fa-solid fa-user-plus"></i><span>Convert to Customer</span></a>';
                }
                if ($canDelete) {
                    $menuItems .= '<a href="' . htmlspecialchars($delUrl) . '" class="crm-action-item is-danger btn-delete" data-ajax="1" data-name="' . $escLabel . '" role="menuitem">'
                        . '<i class="fa-solid fa-trash"></i><span>Delete</span></a>';
                }
            } else {
                $menuItems .= '<span class="crm-action-item is-muted" role="menuitem">'
                    . '<i class="fa-solid fa-ban"></i><span>Deleted</span></span>';
                if ($canDelete) {
                    $menuItems .= '<a href="' . htmlspecialchars($restoreUrl) . '" class="crm-action-item is-success btn-restore" data-ajax="1" data-name="' . $escLabel . '" role="menuitem">'
                        . '<i class="fa-solid fa-rotate-left"></i><span>Restore</span></a>';
                }
            }

            if ($menuItems === '') {
                $actions = '<span class="crm-cell-empty">—</span>';
            } else {
                $actions = '<div class="crm-action-menu" onclick="event.stopPropagation();">'
                    . '<button type="button" class="crm-action-dots" title="Actions" aria-label="Actions" aria-expanded="false" aria-haspopup="true">'
                    . '<i class="fa-solid fa-ellipsis-vertical"></i>'
                    . '</button>'
                    . '<div class="crm-action-dropdown" role="menu" hidden>' . $menuItems . '</div>'
                    . '</div>';
            }

            $dateShow = formatDateDisplay($row['inquiry_date'] ?? '') ?: '—';
            $statusKey = (int) ($row['inquiry_status_id'] ?? 0);
            $fuKey = (int) ($row['follow_up_status_id'] ?? 0);
            $sourceKey = (int) ($row['inquiry_source_id'] ?? 0);

            $companyHtml = $row['company_name'] !== null && $row['company_name'] !== ''
                ? '<span class="crm-company-cell">' . htmlspecialchars((string) $row['company_name']) . '</span>'
                : '<span class="crm-cell-empty">—</span>';
            $phoneHtml = $row['phone'] !== null && $row['phone'] !== ''
                ? '<span class="crm-phone-cell"><i class="fa-solid fa-phone"></i>' . htmlspecialchars((string) $row['phone']) . '</span>'
                : '<span class="crm-cell-empty">—</span>';
            $dateHtml = '<span class="crm-date-cell">' . htmlspecialchars($dateShow) . '</span>';

            $data[] = [
                '<span class="crm-sr">' . ($sr++) . '</span>',
                '<span class="code-badge crm-inq-no">' . htmlspecialchars((string) $row['inquiry_no']) . '</span>',
                $dateHtml,
                '<span class="crm-name-cell">' . htmlspecialchars((string) $row['customer_name']) . '</span>',
                $companyHtml,
                $phoneHtml,
                $loc === '<span class="crm-cell-empty">—</span>'
                    ? $loc
                    : '<span class="crm-loc-cell"><i class="fa-solid fa-location-dot"></i>' . $loc . '</span>',
                inquirySourceBadge($sourceKey),
                ($canEdit && $isActive)
                    ? inquiryGridStatusSelectHtml($id, 'inquiry_status_id', $statusKey, $inqStatusRows, false)
                    : inquiryStatusBadge($statusKey),
                ($canEdit && $isActive)
                    ? inquiryGridStatusSelectHtml($id, 'follow_up_status_id', $fuKey, $fuStatusRows, true)
                    : followUpStatusBadge($fuKey),
                $actions,
            ];
        }
    }
    if ($stmt) {
        $stmt->close();
    }

    return ['data' => $data];
}
