<?php
/**
 * CRM stub — HRMS department-head helpers removed.
 */

function refreshHeadedDepartmentsSession()
{
    $_SESSION['headed_department_ids'] = [];
    $_SESSION['role_code'] = $_SESSION['role_code'] ?? '';
}

function isOfficeStaffRole()
{
    return false;
}

function isDeptHeadRole()
{
    return false;
}

function isHrHeadRole()
{
    return false;
}
