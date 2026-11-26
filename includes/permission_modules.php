<?php
/**
 * CRM permission modules (Role matrix).
 * Company Settings stays Admin-only.
 */

function getPermissionModules()
{
    return [
        'inquiry' => [
            'key'   => 'inquiry',
            'label' => 'Inquiry',
            'icon'  => 'fa-clipboard-question',
        ],
        'masters' => [
            'key'   => 'masters',
            'label' => 'Masters',
            'icon'  => 'fa-database',
        ],
        'users' => [
            'key'   => 'users',
            'label' => 'Users',
            'icon'  => 'fa-users-gear',
        ],
        'roles' => [
            'key'   => 'roles',
            'label' => 'Roles & Access',
            'icon'  => 'fa-user-shield',
        ],
    ];
}

function getPermissionActions()
{
    return [
        'view'   => 'View',
        'add'    => 'Add',
        'edit'   => 'Edit',
        'delete' => 'Delete',
    ];
}

function isValidPermissionModule($module)
{
    $modules = getPermissionModules();
    return isset($modules[$module]);
}

function isValidPermissionAction($action)
{
    return in_array($action, ['view', 'add', 'edit', 'delete'], true);
}
