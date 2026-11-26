<?php
/**
 * Company Settings Helper
 * Separate logos for Login page and Dashboard header.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/dbsync.php';

/**
 * Ensure company_settings table (schema managed in includes/dbsync.php)
 */
function ensureCompanySettingsSchema($conn)
{
    syncDatabase($conn, false);
}

/**
 * Get company settings
 */
function getCompanySettings()
{
    static $settings = null;

    if ($settings !== null) {
        return $settings;
    }

    $defaultLogo = 'assets/images/ocean-crm-logo.png';
    $settings = [
        'company_name'    => 'Ocean CRM',
        'login_logo'      => $defaultLogo,
        'dashboard_logo'  => $defaultLogo,
        'company_logo'    => $defaultLogo, // legacy
        'letterhead'         => '',
        'inquiry_no_prefix'  => 'INQ',
        'inquiry_no_mode'    => 'auto',
        'employee_code_prefix' => 'EMP',
        'employee_code_mode'   => 'auto',
        'phone_max_length'   => 15,
        'header_details'     => '',
        'footer_details'     => '',
    ];

    $conn = @getDBConnection();
    if (!$conn) {
        return $settings;
    }

    ensureCompanySettingsSchema($conn);

    $result = $conn->query(
        "SELECT company_name, company_logo, login_logo, dashboard_logo, letterhead,
                inquiry_no_prefix, inquiry_no_mode, employee_code_prefix, employee_code_mode,
                phone_max_length, header_details, footer_details
         FROM company_settings WHERE id = 1 LIMIT 1"
    );

    if ($result && $row = $result->fetch_assoc()) {
        if (!empty($row['company_name'])) {
            $settings['company_name'] = $row['company_name'];
        }

        $login = !empty($row['login_logo']) ? $row['login_logo'] : $row['company_logo'];
        $dash  = !empty($row['dashboard_logo']) ? $row['dashboard_logo'] : $row['company_logo'];

        if (!empty($login) && file_exists(__DIR__ . '/../' . $login)) {
            $settings['login_logo'] = $login;
            $settings['company_logo'] = $login;
        }
        if (!empty($dash) && file_exists(__DIR__ . '/../' . $dash)) {
            $settings['dashboard_logo'] = $dash;
        }
        if (!empty($row['letterhead']) && file_exists(__DIR__ . '/../' . $row['letterhead'])) {
            $settings['letterhead'] = $row['letterhead'];
        }
        $prefix = strtoupper(trim((string) ($row['inquiry_no_prefix'] ?? 'INQ')));
        $prefix = preg_replace('/[^A-Z0-9_-]/', '', $prefix);
        $settings['inquiry_no_prefix'] = $prefix !== '' ? $prefix : 'INQ';
        $mode = strtolower(trim((string) ($row['inquiry_no_mode'] ?? 'auto')));
        $settings['inquiry_no_mode'] = in_array($mode, ['auto', 'manual'], true) ? $mode : 'auto';
        $empPrefix = strtoupper(trim((string) ($row['employee_code_prefix'] ?? 'EMP')));
        $empPrefix = preg_replace('/[^A-Z0-9_-]/', '', $empPrefix);
        $settings['employee_code_prefix'] = $empPrefix !== '' ? $empPrefix : 'EMP';
        $empMode = strtolower(trim((string) ($row['employee_code_mode'] ?? 'auto')));
        $settings['employee_code_mode'] = in_array($empMode, ['auto', 'manual'], true) ? $empMode : 'auto';
        $phoneMax = (int) ($row['phone_max_length'] ?? 15);
        if ($phoneMax < 5) {
            $phoneMax = 5;
        }
        if ($phoneMax > 40) {
            $phoneMax = 40;
        }
        $settings['phone_max_length'] = $phoneMax;
        $settings['header_details'] = trim((string) ($row['header_details'] ?? ''));
        $settings['footer_details'] = trim((string) ($row['footer_details'] ?? ''));
    }

    return $settings;
}

/**
 * Login page logo
 */
function getLoginLogo()
{
    $settings = getCompanySettings();
    return $settings['login_logo'];
}

/**
 * Dashboard / header logo
 */
function getDashboardLogo()
{
    $settings = getCompanySettings();
    return $settings['dashboard_logo'];
}

/**
 * Legacy helper (returns dashboard logo)
 */
function getCompanyLogo()
{
    return getDashboardLogo();
}

/**
 * Company display name
 */
function getCompanyName()
{
    $settings = getCompanySettings();
    return $settings['company_name'];
}

/**
 * Letterhead image path (for PDF / print documents)
 */
function getCompanyLetterhead()
{
    $settings = getCompanySettings();
    return (string) ($settings['letterhead'] ?? '');
}

/**
 * Inquiry number prefix (e.g. INQ → INQ-2026-0001)
 */
function getInquiryNoPrefix()
{
    $settings = getCompanySettings();
    $prefix = strtoupper(trim((string) ($settings['inquiry_no_prefix'] ?? 'INQ')));
    $prefix = preg_replace('/[^A-Z0-9_-]/', '', $prefix);
    return $prefix !== '' ? $prefix : 'INQ';
}

/**
 * Inquiry number mode: auto | manual
 */
function getInquiryNoMode()
{
    $settings = getCompanySettings();
    $mode = strtolower(trim((string) ($settings['inquiry_no_mode'] ?? 'auto')));
    return in_array($mode, ['auto', 'manual'], true) ? $mode : 'auto';
}

function isInquiryNoAuto()
{
    return getInquiryNoMode() === 'auto';
}

/**
 * Staff user employee code prefix (e.g. EMP)
 */
function getEmployeeCodePrefix()
{
    $settings = getCompanySettings();
    $prefix = strtoupper(trim((string) ($settings['employee_code_prefix'] ?? 'EMP')));
    $prefix = preg_replace('/[^A-Z0-9_-]/', '', $prefix);
    return $prefix !== '' ? $prefix : 'EMP';
}

/**
 * Employee code mode: auto | manual
 */
function getEmployeeCodeMode()
{
    $settings = getCompanySettings();
    $mode = strtolower(trim((string) ($settings['employee_code_mode'] ?? 'auto')));
    return in_array($mode, ['auto', 'manual'], true) ? $mode : 'auto';
}

function isEmployeeCodeAuto()
{
    return getEmployeeCodeMode() === 'auto';
}

/**
 * Next staff employee code: PREFIX0001
 */
function generateStaffEmployeeCode($conn = null)
{
    if ($conn === null) {
        $conn = getDBConnection();
    }
    $prefix = getEmployeeCodePrefix();
    $like = $prefix . '%';
    $stmt = $conn->prepare(
        'SELECT employee_code FROM users
         WHERE employee_code IS NOT NULL AND employee_code LIKE ?
         ORDER BY id DESC LIMIT 1'
    );
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $next = 1;
    if ($row && preg_match('/(\d+)$/', (string) $row['employee_code'], $m)) {
        $next = ((int) $m[1]) + 1;
    }
    return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
}

/**
 * Max phone characters allowed on inquiry form (5–40).
 */
function getInquiryPhoneMaxLength()
{
    $settings = getCompanySettings();
    $n = (int) ($settings['phone_max_length'] ?? 15);
    if ($n < 5) {
        $n = 5;
    }
    if ($n > 40) {
        $n = 40;
    }
    return $n;
}

/**
 * Phone may contain digits + special chars only: + - ( ) . / space
 */
function isValidInquiryPhoneChars($phone)
{
    $phone = trim((string) $phone);
    if ($phone === '') {
        return true;
    }
    return (bool) preg_match('/^[0-9+\-\(\)\.\/ ]+$/', $phone);
}

/**
 * Document / PDF header details (address, phone, GST etc.)
 */
function getCompanyHeaderDetails()
{
    $settings = getCompanySettings();
    return (string) ($settings['header_details'] ?? '');
}

/**
 * Document / PDF footer details
 */
function getCompanyFooterDetails()
{
    $settings = getCompanySettings();
    return (string) ($settings['footer_details'] ?? '');
}

/**
 * Check if path is a custom uploaded logo (not Ocean CRM brand default)
 */
function isCustomLogo($path)
{
    $defaults = [
        'assets/images/ocean-crm-logo.png',
        'assets/images/logo-placeholder.svg',
    ];
    return !empty($path)
        && !in_array($path, $defaults, true)
        && file_exists(__DIR__ . '/../' . $path);
}
