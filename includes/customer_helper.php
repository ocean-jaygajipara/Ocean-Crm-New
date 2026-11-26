<?php
/**
 * Customer module helpers
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/dbsync.php';
require_once __DIR__ . '/date_helper.php';

function ensureCustomerTables($conn = null)
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
}

function getCustomerTypeOptions()
{
    if (!function_exists('getMasterSelectOptions')) {
        require_once __DIR__ . '/master_helper.php';
    }
    ensureCustomerTables();
    return getMasterSelectOptions('customer_types');
}

function generateCustomerNo($conn = null)
{
    if ($conn === null) {
        $conn = getDBConnection();
    }
    ensureCustomerTables($conn);
    $year = date('Y');
    $prefix = 'CUST-' . $year . '-';
    $like = $prefix . '%';
    $stmt = $conn->prepare('SELECT customer_no FROM customers WHERE customer_no LIKE ? ORDER BY id DESC LIMIT 1');
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $last = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $next = 1;
    if ($last && preg_match('/(\d+)$/', (string) ($last['customer_no'] ?? ''), $m)) {
        $next = ((int) $m[1]) + 1;
    }
    return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
}

function getCustomerRow($id)
{
    $conn = getDBConnection();
    ensureCustomerTables($conn);
    $id = (int) $id;
    if ($id <= 0) {
        return null;
    }
    $stmt = $conn->prepare('SELECT * FROM customers WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row;
}

function softDeleteCustomer($id)
{
    $conn = getDBConnection();
    ensureCustomerTables($conn);
    $id = (int) $id;
    $stmt = $conn->prepare('UPDATE customers SET status = 0 WHERE id = ? AND status = 1');
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $n = $stmt->affected_rows;
    $stmt->close();
    return $ok && $n > 0;
}

function restoreCustomer($id)
{
    $conn = getDBConnection();
    ensureCustomerTables($conn);
    $id = (int) $id;
    $stmt = $conn->prepare('UPDATE customers SET status = 1 WHERE id = ? AND status = 0');
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $n = $stmt->affected_rows;
    $stmt->close();
    return $ok && $n > 0;
}

function saveCustomer(array $data, $id = 0, $userId = 0)
{
    $conn = getDBConnection();
    ensureCustomerTables($conn);

    $id = (int) $id;
    $userId = (int) $userId;
    $customerTypeId = (int) ($data['customer_type_id'] ?? 0);
    $customerName = trim((string) ($data['customer_name'] ?? ''));
    $companyName = trim((string) ($data['company_name'] ?? ''));
    $contactPerson = trim((string) ($data['contact_person'] ?? ''));
    $email = trim((string) ($data['email'] ?? ''));
    $phone = trim((string) ($data['phone'] ?? ''));
    $whatsapp = trim((string) ($data['whatsapp'] ?? ''));
    $countryId = (int) ($data['country_id'] ?? 0);
    $stateId = (int) ($data['state_id'] ?? 0);
    $cityId = (int) ($data['city_id'] ?? 0);
    $areaId = (int) ($data['area_id'] ?? 0);
    $address = trim((string) ($data['address'] ?? ''));
    $remarks = trim((string) ($data['remarks'] ?? ''));
    $inquiryId = (int) ($data['inquiry_id'] ?? 0);

    if ($customerName === '') {
        return ['ok' => false, 'error' => 'Customer Name is required.', 'id' => $id];
    }

    if (!function_exists('getInquiryPhoneMaxLength')) {
        require_once __DIR__ . '/settings.php';
    }
    $phoneMaxLen = getInquiryPhoneMaxLength();
    foreach (['Phone' => $phone, 'WhatsApp' => $whatsapp] as $label => $val) {
        if ($val === '') {
            continue;
        }
        if (!isValidInquiryPhoneChars($val)) {
            return [
                'ok' => false,
                'error' => $label . ' may only contain digits and special characters (+ - ( ) . / space).',
                'id' => $id,
            ];
        }
        if (mb_strlen($val) > $phoneMaxLen) {
            return [
                'ok' => false,
                'error' => $label . ' cannot exceed ' . $phoneMaxLen . ' characters.',
                'id' => $id,
            ];
        }
    }

    if ($id > 0) {
        $stmt = $conn->prepare(
            'UPDATE customers SET customer_type_id=?, customer_name=?, company_name=?, contact_person=?,
             email=?, phone=?, whatsapp=?, country_id=?, state_id=?, city_id=?, area_id=?,
             address=?, remarks=?
             WHERE id=? AND status=1'
        );
        $stmt->bind_param(
            'issssssiiiissi',
            $customerTypeId,
            $customerName,
            $companyName,
            $contactPerson,
            $email,
            $phone,
            $whatsapp,
            $countryId,
            $stateId,
            $cityId,
            $areaId,
            $address,
            $remarks,
            $id
        );
        $ok = $stmt->execute();
        $err = $stmt->error;
        $stmt->close();
        return ['ok' => $ok, 'error' => $err, 'id' => $id];
    }

    $customerNo = generateCustomerNo($conn);
    $createdBy = $userId > 0 ? $userId : 0;
    $stmt = $conn->prepare(
        'INSERT INTO customers
         (customer_no, customer_type_id, customer_name, company_name, contact_person,
          email, phone, whatsapp, country_id, state_id, city_id, area_id, address, remarks,
          inquiry_id, created_by, status)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1)'
    );
    $stmt->bind_param(
        'sissssssiiiissii',
        $customerNo,
        $customerTypeId,
        $customerName,
        $companyName,
        $contactPerson,
        $email,
        $phone,
        $whatsapp,
        $countryId,
        $stateId,
        $cityId,
        $areaId,
        $address,
        $remarks,
        $inquiryId,
        $createdBy
    );
    $ok = $stmt->execute();
    $err = $stmt->error;
    $newId = (int) $conn->insert_id;
    $stmt->close();
    return ['ok' => $ok, 'error' => $err, 'id' => $newId];
}

function getConvertedInquiryStatusId()
{
    if (!function_exists('getInquiryStatusRows')) {
        require_once __DIR__ . '/inquiry_helper.php';
    }
    $rows = getInquiryStatusRows();
    foreach ($rows as $id => $row) {
        $n = strtolower((string) ($row['name'] ?? ''));
        if (strpos($n, 'convert') !== false || strpos($n, 'won') !== false) {
            return (int) $id;
        }
    }
    return 0;
}

/**
 * Convert inquiry → customer.
 */
function convertInquiryToCustomer($inquiryId, $userId = 0, $customerTypeId = 0)
{
    if (!function_exists('getInquiryRow')) {
        require_once __DIR__ . '/inquiry_helper.php';
    }
    if (!function_exists('canAccessInquiryRecord')) {
        require_once __DIR__ . '/inquiry_helper.php';
    }

    $conn = getDBConnection();
    ensureCustomerTables($conn);
    if (function_exists('ensureInquiryTables')) {
        ensureInquiryTables($conn);
    }

    $inquiryId = (int) $inquiryId;
    $userId = (int) $userId;
    $customerTypeId = (int) $customerTypeId;
    $row = getInquiryRow($inquiryId);
    if (!$row || (int) ($row['status'] ?? 0) !== 1) {
        return ['ok' => false, 'error' => 'Inquiry not found.', 'customer_id' => 0, 'customer_no' => ''];
    }
    if (!canAccessInquiryRecord($row)) {
        return ['ok' => false, 'error' => 'You do not have access to this inquiry.', 'customer_id' => 0, 'customer_no' => ''];
    }

    $existingCustomerId = (int) ($row['customer_id'] ?? 0);
    if ($existingCustomerId > 0) {
        $cust = getCustomerRow($existingCustomerId);
        return [
            'ok' => true,
            'error' => '',
            'customer_id' => $existingCustomerId,
            'customer_no' => (string) ($cust['customer_no'] ?? ''),
            'already' => true,
        ];
    }

    $chk = $conn->prepare('SELECT id, customer_no FROM customers WHERE inquiry_id = ? AND status = 1 LIMIT 1');
    $chk->bind_param('i', $inquiryId);
    $chk->execute();
    $exist = $chk->get_result()->fetch_assoc();
    $chk->close();
    if ($exist) {
        $cid = (int) $exist['id'];
        $conn->query('UPDATE inquiries SET customer_id = ' . $cid . ' WHERE id = ' . $inquiryId);
        return [
            'ok' => true,
            'error' => '',
            'customer_id' => $cid,
            'customer_no' => (string) ($exist['customer_no'] ?? ''),
            'already' => true,
        ];
    }

    $result = saveCustomer([
        'customer_type_id' => $customerTypeId,
        'customer_name' => $row['customer_name'] ?? '',
        'company_name' => $row['company_name'] ?? '',
        'contact_person' => '',
        'email' => $row['email'] ?? '',
        'phone' => $row['phone'] ?? '',
        'whatsapp' => $row['whatsapp'] ?? '',
        'country_id' => $row['country_id'] ?? 0,
        'state_id' => $row['state_id'] ?? 0,
        'city_id' => $row['city_id'] ?? 0,
        'area_id' => $row['area_id'] ?? 0,
        'address' => $row['address'] ?? '',
        'remarks' => '',
        'inquiry_id' => $inquiryId,
    ], 0, $userId);

    if (!$result['ok'] || (int) $result['id'] <= 0) {
        return [
            'ok' => false,
            'error' => (string) ($result['error'] ?? 'Failed to create customer.'),
            'customer_id' => 0,
            'customer_no' => '',
        ];
    }

    $customerId = (int) $result['id'];
    $cust = getCustomerRow($customerId);
    $customerNo = (string) ($cust['customer_no'] ?? '');

    $convertedStatusId = getConvertedInquiryStatusId();
    if ($convertedStatusId > 0) {
        $upd = $conn->prepare('UPDATE inquiries SET customer_id = ?, inquiry_status_id = ? WHERE id = ?');
        $upd->bind_param('iii', $customerId, $convertedStatusId, $inquiryId);
    } else {
        $upd = $conn->prepare('UPDATE inquiries SET customer_id = ? WHERE id = ?');
        $upd->bind_param('ii', $customerId, $inquiryId);
    }
    $upd->execute();
    $upd->close();

    return [
        'ok' => true,
        'error' => '',
        'customer_id' => $customerId,
        'customer_no' => $customerNo,
        'already' => false,
    ];
}

function customerAjaxListJson()
{
    $conn = getDBConnection();
    ensureCustomerTables($conn);

    $q = trim((string) ($_POST['q'] ?? ''));
    $customerTypeId = (int) ($_POST['customer_type_id'] ?? 0);
    $countryId = (int) ($_POST['country_id'] ?? 0);
    $stateId = (int) ($_POST['state_id'] ?? 0);
    $cityId = (int) ($_POST['city_id'] ?? 0);
    $recordStatus = strtolower(trim((string) ($_POST['record_status'] ?? 'active')));
    if (!in_array($recordStatus, ['active', 'deleted', 'all'], true)) {
        $recordStatus = 'active';
    }

    $canDelete = function_exists('canAccess') ? canAccess('customers', 'delete') : false;
    $canEdit = function_exists('canAccess') ? canAccess('customers', 'edit') : false;
    $canView = function_exists('canAccess') ? canAccess('customers', 'view') : false;
    if (!$canDelete && $recordStatus !== 'active') {
        $recordStatus = 'active';
    }

    $where = [];
    $types = '';
    $params = [];

    if ($recordStatus === 'active') {
        $where[] = 'c.status = 1';
    } elseif ($recordStatus === 'deleted') {
        $where[] = 'c.status = 0';
    }

    if ($customerTypeId > 0) {
        $where[] = 'c.customer_type_id = ?';
        $types .= 'i';
        $params[] = $customerTypeId;
    }
    if ($countryId > 0) {
        $where[] = 'c.country_id = ?';
        $types .= 'i';
        $params[] = $countryId;
    }
    if ($stateId > 0) {
        $where[] = 'c.state_id = ?';
        $types .= 'i';
        $params[] = $stateId;
    }
    if ($cityId > 0) {
        $where[] = 'c.city_id = ?';
        $types .= 'i';
        $params[] = $cityId;
    }
    if ($q !== '') {
        $where[] = '(c.customer_no LIKE ? OR c.customer_name LIKE ? OR c.company_name LIKE ? OR c.contact_person LIKE ? OR c.phone LIKE ? OR c.whatsapp LIKE ? OR c.email LIKE ? OR c.address LIKE ?)';
        $types .= 'ssssssss';
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like, $like, $like, $like, $like, $like);
    }
    if ($where === []) {
        $where[] = '1=1';
    }

    $sql = 'SELECT c.* FROM customers c WHERE ' . implode(' AND ', $where) . ' ORDER BY c.id DESC';
    $stmt = null;
    if ($types !== '') {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        $res = $conn->query($sql);
    }

    if (!function_exists('getMasterSelectOptions')) {
        require_once __DIR__ . '/master_helper.php';
    }
    $cities = getMasterSelectOptions('cities');
    $typesMap = getCustomerTypeOptions();

    $data = [];
    $sr = 1;
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $id = (int) $row['id'];
            $isActive = (int) ($row['status'] ?? 1) === 1;
            $label = $row['customer_name'] ?: ($row['customer_no'] ?? ('#' . $id));
            $viewUrl = app_url('customers/view.php?id=' . $id);
            $editUrl = app_url('customers/edit.php?id=' . $id);
            $delUrl = app_url('customers/delete.php?id=' . $id);
            $restoreUrl = app_url('customers/restore.php?id=' . $id);

            $typeName = $typesMap[(int) ($row['customer_type_id'] ?? 0)] ?? '';
            $cityName = $cities[(int) ($row['city_id'] ?? 0)] ?? '';
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
                    . '<i class="fa-solid fa-ellipsis-vertical"></i></button>'
                    . '<div class="crm-action-dropdown" role="menu" hidden>' . $menuItems . '</div></div>';
            }

            $data[] = [
                '<span class="crm-sr">' . ($sr++) . '</span>',
                '<span class="code-badge">' . htmlspecialchars((string) $row['customer_no']) . '</span>',
                htmlspecialchars((string) ($typeName !== '' ? $typeName : '—')),
                '<span class="crm-name-cell">' . htmlspecialchars((string) $row['customer_name']) . '</span>',
                htmlspecialchars((string) (($row['company_name'] ?? '') !== '' ? $row['company_name'] : '—')),
                htmlspecialchars((string) (($row['phone'] ?? '') !== '' ? $row['phone'] : '—')),
                htmlspecialchars((string) ($cityName !== '' ? $cityName : '—')),
                $actions,
            ];
        }
    }
    if ($stmt) {
        $stmt->close();
    }

    return ['data' => $data];
}
