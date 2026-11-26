<?php
/**
 * Ocean CRM — Database Sync
 * All CREATE / ALTER / rename table SQL managed here.
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Full schema definitions (used tables only)
 */
function getDbSchemaSql()
{
    return [
        'company_settings' =>
            "CREATE TABLE IF NOT EXISTS company_settings (
                id INT PRIMARY KEY DEFAULT 1,
                company_name VARCHAR(150) NOT NULL DEFAULT 'Ocean CRM',
                company_logo VARCHAR(255) DEFAULT NULL,
                login_logo VARCHAR(255) DEFAULT NULL,
                dashboard_logo VARCHAR(255) DEFAULT NULL,
                letterhead VARCHAR(255) DEFAULT NULL,
                inquiry_no_prefix VARCHAR(40) NOT NULL DEFAULT 'INQ',
                inquiry_no_mode VARCHAR(20) NOT NULL DEFAULT 'auto',
                employee_code_prefix VARCHAR(40) NOT NULL DEFAULT 'EMP',
                employee_code_mode VARCHAR(20) NOT NULL DEFAULT 'auto',
                phone_max_length INT NOT NULL DEFAULT 15,
                header_details TEXT DEFAULT NULL,
                footer_details TEXT DEFAULT NULL,
                email VARCHAR(150) DEFAULT NULL,
                phone VARCHAR(30) DEFAULT NULL,
                address VARCHAR(255) DEFAULT NULL,
                city VARCHAR(100) DEFAULT NULL,
                state VARCHAR(100) DEFAULT NULL,
                pincode VARCHAR(20) DEFAULT NULL,
                gstin VARCHAR(30) DEFAULT NULL,
                website VARCHAR(150) DEFAULT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'users' =>
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                employee_code VARCHAR(50) DEFAULT NULL,
                department_id INT NULL,
                role_id INT NULL,
                employee_id INT NULL,
                reports_to INT NOT NULL DEFAULT 0,
                status TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_users_role (role_id),
                INDEX idx_users_employee (employee_id),
                INDEX idx_users_reports_to (reports_to)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'roles' =>
            "CREATE TABLE IF NOT EXISTS roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                code VARCHAR(50) DEFAULT NULL,
                description TEXT DEFAULT NULL,
                status TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_roles_name (name),
                INDEX idx_roles_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'role_permissions' =>
            "CREATE TABLE IF NOT EXISTS role_permissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                role_id INT NOT NULL,
                module_key VARCHAR(50) NOT NULL,
                department_id INT NOT NULL DEFAULT 0 COMMENT '0 = All Departments',
                can_view TINYINT(1) NOT NULL DEFAULT 0,
                can_add TINYINT(1) NOT NULL DEFAULT 0,
                can_edit TINYINT(1) NOT NULL DEFAULT 0,
                can_delete TINYINT(1) NOT NULL DEFAULT 0,
                UNIQUE KEY uq_role_mod_dept (role_id, module_key, department_id),
                INDEX idx_rp_role (role_id),
                INDEX idx_rp_module (module_key),
                INDEX idx_rp_dept (department_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'countries' =>
            "CREATE TABLE IF NOT EXISTS countries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                code VARCHAR(10) DEFAULT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                status TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_countries_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'states' =>
            "CREATE TABLE IF NOT EXISTS states (
                id INT AUTO_INCREMENT PRIMARY KEY,
                country_id INT NOT NULL,
                name VARCHAR(150) NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                status TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_states_status (status),
                INDEX idx_states_country (country_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'cities' =>
            "CREATE TABLE IF NOT EXISTS cities (
                id INT AUTO_INCREMENT PRIMARY KEY,
                state_id INT NOT NULL,
                name VARCHAR(150) NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                status TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_cities_status (status),
                INDEX idx_cities_state (state_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'areas' =>
            "CREATE TABLE IF NOT EXISTS areas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                city_id INT NOT NULL,
                name VARCHAR(150) NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                status TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_areas_status (status),
                INDEX idx_areas_city (city_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'departments' =>
            "CREATE TABLE IF NOT EXISTS departments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                code VARCHAR(40) DEFAULT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                status TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_departments_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'designations' =>
            "CREATE TABLE IF NOT EXISTS designations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                department_id INT NOT NULL DEFAULT 0,
                name VARCHAR(150) NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                status TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_designations_status (status),
                INDEX idx_designations_dept (department_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'product_categories' =>
            "CREATE TABLE IF NOT EXISTS product_categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                code VARCHAR(40) DEFAULT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                status TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_product_categories_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'product_subcategories' =>
            "CREATE TABLE IF NOT EXISTS product_subcategories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_category_id INT NOT NULL DEFAULT 0,
                name VARCHAR(150) NOT NULL,
                code VARCHAR(40) DEFAULT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                status TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_product_subcategories_status (status),
                INDEX idx_product_subcategories_cat (product_category_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'products' =>
            "CREATE TABLE IF NOT EXISTS products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_category_id INT NOT NULL DEFAULT 0,
                product_subcategory_id INT NOT NULL DEFAULT 0,
                name VARCHAR(150) NOT NULL,
                code VARCHAR(40) DEFAULT NULL,
                unit VARCHAR(40) DEFAULT NULL,
                price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                description TEXT DEFAULT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                status TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_products_status (status),
                INDEX idx_products_cat (product_category_id),
                INDEX idx_products_sub (product_subcategory_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'inquiry_statuses' =>
            "CREATE TABLE IF NOT EXISTS inquiry_statuses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                code VARCHAR(40) DEFAULT NULL,
                color VARCHAR(20) DEFAULT NULL,
                is_default TINYINT(1) NOT NULL DEFAULT 0,
                sort_order INT NOT NULL DEFAULT 0,
                status TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_inquiry_statuses_status (status),
                INDEX idx_inquiry_statuses_sort (sort_order),
                INDEX idx_inquiry_statuses_default (is_default)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'follow_up_statuses' =>
            "CREATE TABLE IF NOT EXISTS follow_up_statuses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                code VARCHAR(40) DEFAULT NULL,
                color VARCHAR(20) DEFAULT NULL,
                is_default TINYINT(1) NOT NULL DEFAULT 0,
                sort_order INT NOT NULL DEFAULT 0,
                status TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_follow_up_statuses_status (status),
                INDEX idx_follow_up_statuses_sort (sort_order),
                INDEX idx_follow_up_statuses_default (is_default)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'inquiry_sources' =>
            "CREATE TABLE IF NOT EXISTS inquiry_sources (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                code VARCHAR(40) DEFAULT NULL,
                color VARCHAR(20) DEFAULT NULL,
                is_default TINYINT(1) NOT NULL DEFAULT 0,
                sort_order INT NOT NULL DEFAULT 0,
                status TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_inquiry_sources_status (status),
                INDEX idx_inquiry_sources_sort (sort_order),
                INDEX idx_inquiry_sources_default (is_default)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'customer_types' =>
            "CREATE TABLE IF NOT EXISTS customer_types (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                code VARCHAR(40) DEFAULT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                status TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_customer_types_status (status),
                INDEX idx_customer_types_sort (sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'inquiries' =>
            "CREATE TABLE IF NOT EXISTS inquiries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                inquiry_no VARCHAR(40) NOT NULL,
                inquiry_date DATE NOT NULL,
                customer_name VARCHAR(150) NOT NULL,
                company_name VARCHAR(150) DEFAULT NULL,
                email VARCHAR(150) DEFAULT NULL,
                phone VARCHAR(40) DEFAULT NULL,
                whatsapp VARCHAR(40) DEFAULT NULL,
                country_id INT NOT NULL DEFAULT 0,
                state_id INT NOT NULL DEFAULT 0,
                city_id INT NOT NULL DEFAULT 0,
                area_id INT NOT NULL DEFAULT 0,
                address VARCHAR(255) DEFAULT NULL,
                requirement_details TEXT DEFAULT NULL,
                inquiry_status_id INT NOT NULL DEFAULT 0,
                follow_up_status_id INT NOT NULL DEFAULT 0,
                inquiry_source_id INT NOT NULL DEFAULT 0,
                follow_up_date DATE DEFAULT NULL,
                created_by INT NOT NULL DEFAULT 0,
                assign_to INT NOT NULL DEFAULT 0,
                customer_id INT NOT NULL DEFAULT 0,
                status TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_inquiries_no (inquiry_no),
                INDEX idx_inquiries_status (status),
                INDEX idx_inquiries_inq_status (inquiry_status_id),
                INDEX idx_inquiries_fu_status (follow_up_status_id),
                INDEX idx_inquiries_source (inquiry_source_id),
                INDEX idx_inquiries_date (inquiry_date),
                INDEX idx_inquiries_fu_date (follow_up_date),
                INDEX idx_inquiries_created_by (created_by),
                INDEX idx_inquiries_assign_to (assign_to),
                INDEX idx_inquiries_customer (customer_id),
                INDEX idx_inquiries_country (country_id),
                INDEX idx_inquiries_state (state_id),
                INDEX idx_inquiries_city (city_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'customers' =>
            "CREATE TABLE IF NOT EXISTS customers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_no VARCHAR(40) NOT NULL,
                customer_type_id INT NOT NULL DEFAULT 0,
                customer_name VARCHAR(150) NOT NULL,
                company_name VARCHAR(150) DEFAULT NULL,
                contact_person VARCHAR(150) DEFAULT NULL,
                email VARCHAR(150) DEFAULT NULL,
                phone VARCHAR(40) DEFAULT NULL,
                whatsapp VARCHAR(40) DEFAULT NULL,
                country_id INT NOT NULL DEFAULT 0,
                state_id INT NOT NULL DEFAULT 0,
                city_id INT NOT NULL DEFAULT 0,
                area_id INT NOT NULL DEFAULT 0,
                address VARCHAR(255) DEFAULT NULL,
                remarks TEXT DEFAULT NULL,
                inquiry_id INT NOT NULL DEFAULT 0,
                created_by INT NOT NULL DEFAULT 0,
                status TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_customers_no (customer_no),
                INDEX idx_customers_status (status),
                INDEX idx_customers_type (customer_type_id),
                INDEX idx_customers_inquiry (inquiry_id),
                INDEX idx_customers_phone (phone),
                INDEX idx_customers_name (customer_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'inquiry_products' =>
            "CREATE TABLE IF NOT EXISTS inquiry_products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                inquiry_id INT NOT NULL,
                product_id INT NOT NULL DEFAULT 0,
                qty DECIMAL(12,2) NOT NULL DEFAULT 1.00,
                requirements VARCHAR(500) DEFAULT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_inquiry_products_inq (inquiry_id),
                INDEX idx_inquiry_products_product (product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'notifications' =>
            "CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                type VARCHAR(50) NOT NULL DEFAULT 'general',
                title VARCHAR(200) NOT NULL,
                message VARCHAR(500) DEFAULT NULL,
                link VARCHAR(255) DEFAULT NULL,
                ref_type VARCHAR(50) DEFAULT NULL,
                ref_id INT NOT NULL DEFAULT 0,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                created_by INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_notifications_user_read (user_id, is_read),
                INDEX idx_notifications_created (created_at),
                INDEX idx_notifications_ref (ref_type, ref_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'api_tokens' =>
            "CREATE TABLE IF NOT EXISTS api_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(80) NOT NULL,
                api_role VARCHAR(30) NOT NULL DEFAULT '',
                device_info VARCHAR(255) DEFAULT NULL,
                expires_at DATETIME DEFAULT NULL,
                last_used_at DATETIME DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_api_tokens_token (token),
                INDEX idx_api_tokens_user (user_id),
                INDEX idx_api_tokens_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];
}

/**
 * Expected tables list
 */
function getDbExpectedTables()
{
    return array_keys(getDbSchemaSql());
}

/**
 * Legacy rename map (old → new)
 */
function getDbLegacyRenameMap()
{
    return [
        'crm_countries'    => 'countries',
        'crm_states'       => 'states',
        'crm_cities'       => 'cities',
        'crm_areas'        => 'areas',
        'crm_departments'  => 'departments',
        'crm_designations' => 'designations',
    ];
}

function dbTableExists(mysqli $conn, $table)
{
    $table = preg_replace('/[^a-z0-9_]/', '', (string) $table);
    if ($table === '') {
        return false;
    }
    $res = $conn->query("SHOW TABLES LIKE '{$table}'");
    return $res && $res->num_rows > 0;
}

function dbTableColumns(mysqli $conn, $table)
{
    $cols = [];
    if (!dbTableExists($conn, $table)) {
        return $cols;
    }
    $res = $conn->query('SHOW COLUMNS FROM `' . preg_replace('/[^a-z0-9_]/', '', $table) . '`');
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $cols[] = $row['Field'];
        }
    }
    return $cols;
}

function dbAddColumnIfMissing(mysqli $conn, $table, $column, $definition, array &$log)
{
    $cols = dbTableColumns($conn, $table);
    if (!in_array($column, $cols, true)) {
        $ok = $conn->query("ALTER TABLE `{$table}` ADD COLUMN {$column} {$definition}");
        $log[] = [
            'type' => $ok ? 'ok' : 'error',
            'msg'  => ($ok ? 'Added column' : 'Failed column') . " {$table}.{$column}" . ($ok ? '' : (': ' . $conn->error)),
        ];
        return (bool) $ok;
    }
    return true;
}

function dbDropColumnIfExists(mysqli $conn, $table, $column, array &$log)
{
    $table = preg_replace('/[^a-z0-9_]/', '', (string) $table);
    $column = preg_replace('/[^a-z0-9_]/', '', (string) $column);
    if ($table === '' || $column === '') {
        return false;
    }
    $cols = dbTableColumns($conn, $table);
    if (!in_array($column, $cols, true)) {
        return true;
    }
    $ok = $conn->query("ALTER TABLE `{$table}` DROP COLUMN `{$column}`");
    $log[] = [
        'type' => $ok ? 'ok' : 'error',
        'msg'  => ($ok ? 'Dropped column' : 'Failed drop') . " {$table}.{$column}" . ($ok ? '' : (': ' . $conn->error)),
    ];
    return (bool) $ok;
}

/**
 * Run full DB sync: rename → create → alter → optional seed
 *
 * @return array{ok:bool,log:array,tables:array}
 */
function syncDatabase($conn = null, $withSeed = true)
{
    if ($conn === null) {
        $conn = getDBConnection();
    }

    $log = [];
    $okAll = true;

    // 1) Legacy renames
    foreach (getDbLegacyRenameMap() as $old => $new) {
        $oldExists = dbTableExists($conn, $old);
        $newExists = dbTableExists($conn, $new);
        if ($oldExists && !$newExists) {
            $ok = $conn->query("RENAME TABLE `{$old}` TO `{$new}`");
            $log[] = [
                'type' => $ok ? 'ok' : 'error',
                'msg'  => ($ok ? 'Renamed' : 'Rename failed') . " {$old} → {$new}" . ($ok ? '' : (': ' . $conn->error)),
            ];
            $okAll = $okAll && $ok;
        }
    }

    // 2) CREATE TABLE IF NOT EXISTS
    foreach (getDbSchemaSql() as $table => $sql) {
        $existed = dbTableExists($conn, $table);
        $ok = $conn->query($sql);
        if (!$ok) {
            $okAll = false;
            $log[] = ['type' => 'error', 'msg' => "Create {$table} failed: " . $conn->error];
        } else {
            $log[] = [
                'type' => 'ok',
                'msg'  => $existed ? "Table OK: {$table}" : "Created table: {$table}",
            ];
        }
    }

    // 3) Column upgrades — company_settings
    $companyCols = [
        'login_logo'      => 'VARCHAR(255) DEFAULT NULL',
        'dashboard_logo'  => 'VARCHAR(255) DEFAULT NULL',
        'letterhead'         => 'VARCHAR(255) DEFAULT NULL',
        'inquiry_no_prefix'  => "VARCHAR(40) NOT NULL DEFAULT 'INQ'",
        'inquiry_no_mode'    => "VARCHAR(20) NOT NULL DEFAULT 'auto'",
        'employee_code_prefix' => "VARCHAR(40) NOT NULL DEFAULT 'EMP'",
        'employee_code_mode'   => "VARCHAR(20) NOT NULL DEFAULT 'auto'",
        'phone_max_length'   => 'INT NOT NULL DEFAULT 15',
        'header_details'     => 'TEXT DEFAULT NULL',
        'footer_details'     => 'TEXT DEFAULT NULL',
        'email'           => 'VARCHAR(150) DEFAULT NULL',
        'phone'           => 'VARCHAR(30) DEFAULT NULL',
        'address'         => 'VARCHAR(255) DEFAULT NULL',
        'city'            => 'VARCHAR(100) DEFAULT NULL',
        'state'           => 'VARCHAR(100) DEFAULT NULL',
        'pincode'         => 'VARCHAR(20) DEFAULT NULL',
        'gstin'           => 'VARCHAR(30) DEFAULT NULL',
        'website'         => 'VARCHAR(150) DEFAULT NULL',
    ];
    foreach ($companyCols as $col => $def) {
        dbAddColumnIfMissing($conn, 'company_settings', $col, $def, $log);
    }

    // users upgrades
    dbAddColumnIfMissing($conn, 'users', 'employee_code', 'VARCHAR(50) DEFAULT NULL AFTER full_name', $log);
    dbAddColumnIfMissing($conn, 'users', 'role_id', 'INT NULL DEFAULT NULL AFTER department_id', $log);
    // migrate legacy custom_role_id → role_id then drop
    if (dbTableExists($conn, 'users')) {
        $userCols = dbTableColumns($conn, 'users');
        if (in_array('custom_role_id', $userCols, true) && in_array('role_id', $userCols, true)) {
            $conn->query(
                'UPDATE users
                 SET role_id = custom_role_id
                 WHERE (role_id IS NULL OR role_id = 0)
                   AND custom_role_id IS NOT NULL AND custom_role_id <> 0'
            );
            dbDropColumnIfExists($conn, 'users', 'custom_role_id', $log);
            $userCols = dbTableColumns($conn, 'users');
        }
        if (!in_array('employee_id', $userCols, true)) {
            dbAddColumnIfMissing($conn, 'users', 'employee_id', 'INT NULL DEFAULT NULL AFTER role_id', $log);
        }
    }
    dbAddColumnIfMissing($conn, 'users', 'employee_id', 'INT NULL DEFAULT NULL AFTER role_id', $log);
    dbAddColumnIfMissing($conn, 'users', 'reports_to', 'INT NOT NULL DEFAULT 0 AFTER employee_id', $log);
    // Drop legacy users.role ENUM — access now via role_id only
    if (dbTableExists($conn, 'users')) {
        $userColsDrop = dbTableColumns($conn, 'users');
        if (in_array('role', $userColsDrop, true)) {
            dbDropColumnIfExists($conn, 'users', 'role', $log);
        }
    }

    // inquiries: address + requirement_details + status columns
    dbAddColumnIfMissing($conn, 'inquiries', 'whatsapp', 'VARCHAR(40) DEFAULT NULL AFTER phone', $log);
    dbAddColumnIfMissing($conn, 'inquiries', 'address', 'VARCHAR(255) DEFAULT NULL AFTER area_id', $log);
    dbAddColumnIfMissing($conn, 'inquiries', 'requirement_details', 'TEXT DEFAULT NULL AFTER address', $log);
    // migrate legacy message → requirement_details then drop message
    if (dbTableExists($conn, 'inquiries')) {
        $inqCols = dbTableColumns($conn, 'inquiries');
        if (in_array('message', $inqCols, true) && in_array('requirement_details', $inqCols, true)) {
            $conn->query(
                "UPDATE inquiries
                 SET requirement_details = message
                 WHERE (requirement_details IS NULL OR requirement_details = '')
                   AND message IS NOT NULL AND message <> ''"
            );
            if (function_exists('dbDropColumnIfExists')) {
                dbDropColumnIfExists($conn, 'inquiries', 'message', $log);
            }
        }
    }
    dbAddColumnIfMissing($conn, 'inquiries', 'inquiry_status_id', 'INT NOT NULL DEFAULT 0 AFTER requirement_details', $log);
    dbAddColumnIfMissing($conn, 'inquiries', 'follow_up_status_id', 'INT NOT NULL DEFAULT 0 AFTER inquiry_status_id', $log);
    dbAddColumnIfMissing($conn, 'inquiries', 'inquiry_source_id', 'INT NOT NULL DEFAULT 0 AFTER follow_up_status_id', $log);
    // Rename legacy next_follow_up_date → follow_up_date
    if (dbTableExists($conn, 'inquiries')) {
        $inqCols = dbTableColumns($conn, 'inquiries');
        if (in_array('next_follow_up_date', $inqCols, true) && !in_array('follow_up_date', $inqCols, true)) {
            $okRename = $conn->query(
                'ALTER TABLE `inquiries` CHANGE COLUMN `next_follow_up_date` `follow_up_date` DATE DEFAULT NULL'
            );
            $log[] = [
                'type' => $okRename ? 'ok' : 'error',
                'msg'  => ($okRename ? 'Renamed' : 'Failed rename') . ' inquiries.next_follow_up_date → follow_up_date'
                    . ($okRename ? '' : (': ' . $conn->error)),
            ];
        }
    }
    dbAddColumnIfMissing($conn, 'inquiries', 'follow_up_date', 'DATE DEFAULT NULL AFTER inquiry_source_id', $log);
    dbAddColumnIfMissing($conn, 'inquiries', 'assign_to', 'INT NOT NULL DEFAULT 0 AFTER created_by', $log);
    dbAddColumnIfMissing($conn, 'inquiries', 'customer_id', 'INT NOT NULL DEFAULT 0 AFTER assign_to', $log);
    dbAddColumnIfMissing($conn, 'customers', 'customer_type_id', 'INT NOT NULL DEFAULT 0 AFTER customer_no', $log);
    dbAddColumnIfMissing($conn, 'customers', 'contact_person', 'VARCHAR(150) DEFAULT NULL AFTER company_name', $log);
    dbAddColumnIfMissing($conn, 'customers', 'remarks', 'TEXT DEFAULT NULL AFTER address', $log);
    if (function_exists('dbDropColumnIfExists')) {
        dbDropColumnIfExists($conn, 'inquiries', 'subject', $log);
    }
    dbAddColumnIfMissing($conn, 'inquiry_products', 'requirements', 'VARCHAR(500) DEFAULT NULL AFTER qty', $log);
    dbAddColumnIfMissing($conn, 'inquiry_statuses', 'color', 'VARCHAR(20) DEFAULT NULL AFTER code', $log);
    dbAddColumnIfMissing($conn, 'inquiry_statuses', 'is_default', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER color', $log);
    dbAddColumnIfMissing($conn, 'follow_up_statuses', 'color', 'VARCHAR(20) DEFAULT NULL AFTER code', $log);
    dbAddColumnIfMissing($conn, 'follow_up_statuses', 'is_default', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER color', $log);
    dbAddColumnIfMissing($conn, 'inquiry_sources', 'color', 'VARCHAR(20) DEFAULT NULL AFTER code', $log);
    dbAddColumnIfMissing($conn, 'inquiry_sources', 'is_default', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER color', $log);

    // Ensure company_settings row
    $conn->query(
        "INSERT INTO company_settings (id, company_name)
         VALUES (1, 'Ocean CRM')
         ON DUPLICATE KEY UPDATE id = id"
    );
    $log[] = ['type' => 'ok', 'msg' => 'company_settings default row ensured'];

    // Brand logo fill if empty / placeholder
    $brandLogo = 'assets/images/ocean-crm-logo.png';
    $conn->query(
        "UPDATE company_settings
         SET login_logo = IF(login_logo IS NULL OR login_logo = '' OR login_logo LIKE '%logo-placeholder%', '{$brandLogo}', login_logo),
             dashboard_logo = IF(dashboard_logo IS NULL OR dashboard_logo = '' OR dashboard_logo LIKE '%logo-placeholder%', '{$brandLogo}', dashboard_logo),
             company_logo = IF(company_logo IS NULL OR company_logo = '' OR company_logo LIKE '%logo-placeholder%', '{$brandLogo}', company_logo)
         WHERE id = 1"
    );

    // 4) Optional seeds (masters)
    if ($withSeed) {
        if (
            !function_exists('ensureStatusMasterDefaults')
            || !function_exists('migrateInquiryStatusIds')
        ) {
            require_once __DIR__ . '/master_helper.php';
        }
        if (function_exists('ensureStatusMasterDefaults')) {
            ensureStatusMasterDefaults($conn);
            $log[] = ['type' => 'ok', 'msg' => 'Status master defaults ensured'];
        }
        if (function_exists('migrateInquiryStatusIds')) {
            migrateInquiryStatusIds($conn, $log);
        }
    }

    return [
        'ok'     => $okAll,
        'log'    => $log,
        'tables' => getDbSyncStatus($conn),
    ];
}

/**
 * Status of expected + extra tables
 */
function getDbSyncStatus($conn = null)
{
    if ($conn === null) {
        $conn = getDBConnection();
    }

    $expected = getDbExpectedTables();
    $existing = [];
    $res = $conn->query('SHOW TABLES');
    if ($res) {
        while ($row = $res->fetch_array()) {
            $existing[] = $row[0];
        }
    }

    $rows = [];
    foreach ($expected as $table) {
        $exists = in_array($table, $existing, true);
        $count = null;
        if ($exists) {
            $cRes = $conn->query('SELECT COUNT(*) AS c FROM `' . $table . '`');
            $count = $cRes ? (int) $cRes->fetch_assoc()['c'] : 0;
        }
        $rows[] = [
            'table'  => $table,
            'exists' => $exists,
            'rows'   => $count,
            'extra'  => false,
        ];
    }

    foreach ($existing as $table) {
        if (!in_array($table, $expected, true)) {
            $cRes = $conn->query('SELECT COUNT(*) AS c FROM `' . $table . '`');
            $count = $cRes ? (int) $cRes->fetch_assoc()['c'] : 0;
            $rows[] = [
                'table'  => $table,
                'exists' => true,
                'rows'   => $count,
                'extra'  => true,
            ];
        }
    }

    return $rows;
}

/**
 * Drop tables that are NOT in expected schema (careful)
 */
function dropExtraDbTables($conn = null)
{
    if ($conn === null) {
        $conn = getDBConnection();
    }
    $expected = getDbExpectedTables();
    $log = [];
    $status = getDbSyncStatus($conn);
    foreach ($status as $row) {
        if (empty($row['extra'])) {
            continue;
        }
        $t = preg_replace('/[^a-z0-9_]/', '', $row['table']);
        if ($t === '' || in_array($t, $expected, true)) {
            continue;
        }
        $ok = $conn->query('DROP TABLE IF EXISTS `' . $t . '`');
        $log[] = [
            'type' => $ok ? 'ok' : 'error',
            'msg'  => ($ok ? 'Dropped extra table' : 'Drop failed') . ": {$t}",
        ];
    }
    return $log;
}
