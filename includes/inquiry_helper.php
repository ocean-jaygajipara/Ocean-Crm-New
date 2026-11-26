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
    $inquiryDate = $data['inquiry_date'] ?? date('Y-m-d');
    $customerName = trim((string) ($data['customer_name'] ?? ''));
    $companyName = trim((string) ($data['company_name'] ?? ''));
    $email = trim((string) ($data['email'] ?? ''));
    $phone = trim((string) ($data['phone'] ?? ''));
    $countryId = (int) ($data['country_id'] ?? 0);
    $stateId = (int) ($data['state_id'] ?? 0);
    $cityId = (int) ($data['city_id'] ?? 0);
    $areaId = (int) ($data['area_id'] ?? 0);
    $subject = trim((string) ($data['subject'] ?? ''));
    $message = trim((string) ($data['message'] ?? ''));

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

    $nextFuDate = trim((string) ($data['next_follow_up_date'] ?? ''));

    if ($customerName === '') {
        return ['ok' => false, 'error' => 'Customer Name is required.', 'id' => 0];
    }

    if ($phone !== '') {
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

    if ($id > 0) {
        $stmt = $conn->prepare(
            'UPDATE inquiries SET inquiry_date=?, customer_name=?, company_name=?, email=?, phone=?,
             country_id=?, state_id=?, city_id=?, area_id=?, subject=?, message=?,
             inquiry_status_id=?, follow_up_status_id=?, inquiry_source_id=?, next_follow_up_date=NULLIF(?, \'\' )
             WHERE id=? AND status=1'
        );
        $stmt->bind_param(
            'sssssiiiissiiisi',
            $inquiryDate,
            $customerName,
            $companyName,
            $email,
            $phone,
            $countryId,
            $stateId,
            $cityId,
            $areaId,
            $subject,
            $message,
            $statusId,
            $fuStatusId,
            $sourceId,
            $nextFuDate,
            $id
        );
        $ok = $stmt->execute();
        $err = $stmt->error;
        $stmt->close();
        return ['ok' => $ok, 'error' => $err, 'id' => $id];
    }

    $inquiryNo = generateInquiryNo($conn);
    $stmt = $conn->prepare(
        'INSERT INTO inquiries
         (inquiry_no, inquiry_date, customer_name, company_name, email, phone,
          country_id, state_id, city_id, area_id, subject, message,
          inquiry_status_id, follow_up_status_id, inquiry_source_id, next_follow_up_date, created_by, status)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NULLIF(?, \'\' ),?,1)'
    );
    $stmt->bind_param(
        'ssssssiiiissiiisi',
        $inquiryNo,
        $inquiryDate,
        $customerName,
        $companyName,
        $email,
        $phone,
        $countryId,
        $stateId,
        $cityId,
        $areaId,
        $subject,
        $message,
        $statusId,
        $fuStatusId,
        $sourceId,
        $nextFuDate,
        $userId
    );
    $ok = $stmt->execute();
    $err = $stmt->error;
    $newId = (int) $conn->insert_id;
    $stmt->close();
    return ['ok' => $ok, 'error' => $err, 'id' => $newId, 'inquiry_no' => $inquiryNo];
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
    $recordStatus = strtolower(trim((string) ($_POST['record_status'] ?? 'active')));
    if (!in_array($recordStatus, ['active', 'deleted', 'all'], true)) {
        $recordStatus = 'active';
    }

    $canDelete = function_exists('canAccess') ? canAccess('inquiry', 'delete') : false;
    if (!$canDelete && $recordStatus !== 'active') {
        $recordStatus = 'active';
    }

    $where = [];
    $types = '';
    $params = [];

    if ($recordStatus === 'active') {
        $where[] = 'i.status = 1';
    } elseif ($recordStatus === 'deleted') {
        $where[] = 'i.status = 0';
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
        $where[] = '(i.inquiry_no LIKE ? OR i.customer_name LIKE ? OR i.company_name LIKE ? OR i.phone LIKE ? OR i.email LIKE ? OR i.subject LIKE ?)';
        $types .= 'ssssss';
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like, $like, $like, $like);
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

            $dateShow = !empty($row['inquiry_date']) ? date('d-m-Y', strtotime($row['inquiry_date'])) : '—';
            $statusKey = (int) ($row['inquiry_status_id'] ?? 0);
            $fuKey = (int) ($row['follow_up_status_id'] ?? 0);
            $sourceKey = (int) ($row['inquiry_source_id'] ?? 0);
            $nextFu = !empty($row['next_follow_up_date'])
                ? htmlspecialchars(date('d-m-Y', strtotime($row['next_follow_up_date'])))
                : '<span class="crm-cell-empty">—</span>';

            $data[] = [
                '<span class="crm-sr">' . ($sr++) . '</span>',
                '<span class="code-badge">' . htmlspecialchars((string) $row['inquiry_no']) . '</span>',
                htmlspecialchars($dateShow),
                '<span class="crm-name-cell">' . htmlspecialchars((string) $row['customer_name']) . '</span>',
                $row['company_name'] !== null && $row['company_name'] !== ''
                    ? htmlspecialchars((string) $row['company_name'])
                    : '<span class="crm-cell-empty">—</span>',
                $row['phone'] !== null && $row['phone'] !== ''
                    ? htmlspecialchars((string) $row['phone'])
                    : '<span class="crm-cell-empty">—</span>',
                $loc,
                inquirySourceBadge($sourceKey),
                inquiryStatusBadge($statusKey),
                followUpStatusBadge($fuKey),
                $nextFu,
                $actions,
            ];
        }
    }
    if ($stmt) {
        $stmt->close();
    }

    return ['data' => $data];
}
