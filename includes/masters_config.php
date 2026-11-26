<?php
/**
 * CRM Masters Config — Org + Location masters
 */

function getMastersConfig()
{
    return [
        'departments' => [
            'key'          => 'departments',
            'title'        => 'Department Master',
            'singular'     => 'Department',
            'table'        => 'departments',
            'folder'       => 'departments',
            'icon'         => 'fa-sitemap',
            'color'        => '#0EA5E9',
            'name_field'   => 'name',
            'list_columns' => [
                ['key' => 'name', 'label' => 'Department'],
                ['key' => 'code', 'label' => 'Code'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Department Name', 'type' => 'text', 'required' => true, 'span' => 2],
                ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => false, 'span' => 1, 'help' => 'e.g. HR, SALES'],
            ],
            'filters' => [
                ['name' => 'q', 'label' => 'Search', 'type' => 'text', 'placeholder' => 'Department name / code'],
            ],
        ],
        'designations' => [
            'key'          => 'designations',
            'title'        => 'Designation Master',
            'singular'     => 'Designation',
            'table'        => 'designations',
            'folder'       => 'designations',
            'icon'         => 'fa-id-badge',
            'color'        => '#F59E0B',
            'name_field'   => 'name',
            'list_columns' => [
                ['key' => 'name', 'label' => 'Designation'],
                ['key' => 'department_id', 'label' => 'Department', 'fk_table' => 'departments'],
            ],
            'fields' => [
                ['name' => 'department_id', 'label' => 'Department', 'type' => 'select', 'required' => true, 'span' => 1,
                    'options_from' => 'departments', 'bind' => 'int'],
                ['name' => 'name', 'label' => 'Designation Name', 'type' => 'text', 'required' => true, 'span' => 2],
            ],
            'filters' => [
                ['name' => 'department_id', 'label' => 'Department', 'type' => 'select', 'options_from' => 'departments'],
                ['name' => 'q', 'label' => 'Search', 'type' => 'text', 'placeholder' => 'Designation name'],
            ],
        ],
        'countries' => [
            'key'          => 'countries',
            'title'        => 'Country Master',
            'singular'     => 'Country',
            'table'        => 'countries',
            'folder'       => 'countries',
            'icon'         => 'fa-globe',
            'color'        => '#0F766E',
            'name_field'   => 'name',
            'list_columns' => [
                ['key' => 'name', 'label' => 'Country'],
                ['key' => 'code', 'label' => 'Code'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Country Name', 'type' => 'text', 'required' => true, 'span' => 2],
                ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => false, 'span' => 1, 'help' => 'e.g. IN, US'],
            ],
            'filters' => [
                ['name' => 'q', 'label' => 'Search', 'type' => 'text', 'placeholder' => 'Country name / code'],
            ],
        ],
        'states' => [
            'key'          => 'states',
            'title'        => 'State Master',
            'singular'     => 'State',
            'table'        => 'states',
            'folder'       => 'states',
            'icon'         => 'fa-map',
            'color'        => '#2563EB',
            'name_field'   => 'name',
            'list_columns' => [
                ['key' => 'name', 'label' => 'State'],
                ['key' => 'country_id', 'label' => 'Country', 'fk_table' => 'countries'],
            ],
            'fields' => [
                ['name' => 'country_id', 'label' => 'Country', 'type' => 'select', 'required' => true, 'span' => 1,
                    'options_from' => 'countries', 'bind' => 'int'],
                ['name' => 'name', 'label' => 'State Name', 'type' => 'text', 'required' => true, 'span' => 2],
            ],
            'filters' => [
                ['name' => 'country_id', 'label' => 'Country', 'type' => 'select', 'options_from' => 'countries'],
                ['name' => 'q', 'label' => 'Search', 'type' => 'text', 'placeholder' => 'State name'],
            ],
        ],
        'cities' => [
            'key'          => 'cities',
            'title'        => 'City Master',
            'singular'     => 'City',
            'table'        => 'cities',
            'folder'       => 'cities',
            'icon'         => 'fa-city',
            'color'        => '#7C3AED',
            'name_field'   => 'name',
            'list_columns' => [
                ['key' => 'name', 'label' => 'City'],
                ['key' => 'state_id', 'label' => 'State', 'fk_table' => 'states'],
            ],
            'fields' => [
                ['name' => 'state_id', 'label' => 'State', 'type' => 'select', 'required' => true, 'span' => 1,
                    'options_from' => 'states', 'bind' => 'int'],
                ['name' => 'name', 'label' => 'City Name', 'type' => 'text', 'required' => true, 'span' => 2],
            ],
            'filters' => [
                ['name' => 'country_id', 'label' => 'Country', 'type' => 'select', 'options_from' => 'countries'],
                ['name' => 'state_id', 'label' => 'State', 'type' => 'select', 'options_from' => 'states', 'parent' => 'country_id', 'parent_key' => 'country_id'],
                ['name' => 'q', 'label' => 'Search', 'type' => 'text', 'placeholder' => 'City name'],
            ],
        ],
        'areas' => [
            'key'          => 'areas',
            'title'        => 'Area Master',
            'singular'     => 'Area',
            'table'        => 'areas',
            'folder'       => 'areas',
            'icon'         => 'fa-location-dot',
            'color'        => '#DB2777',
            'name_field'   => 'name',
            'list_columns' => [
                ['key' => 'name', 'label' => 'Area'],
                ['key' => 'city_id', 'label' => 'City', 'fk_table' => 'cities'],
            ],
            'fields' => [
                ['name' => 'city_id', 'label' => 'City', 'type' => 'select', 'required' => true, 'span' => 1,
                    'options_from' => 'cities', 'bind' => 'int'],
                ['name' => 'name', 'label' => 'Area Name', 'type' => 'text', 'required' => true, 'span' => 2],
            ],
            'filters' => [
                ['name' => 'country_id', 'label' => 'Country', 'type' => 'select', 'options_from' => 'countries'],
                ['name' => 'state_id', 'label' => 'State', 'type' => 'select', 'options_from' => 'states', 'parent' => 'country_id', 'parent_key' => 'country_id'],
                ['name' => 'city_id', 'label' => 'City', 'type' => 'select', 'options_from' => 'cities', 'parent' => 'state_id', 'parent_key' => 'state_id'],
                ['name' => 'q', 'label' => 'Search', 'type' => 'text', 'placeholder' => 'Area name'],
            ],
        ],
        'inquiry_statuses' => [
            'key'          => 'inquiry_statuses',
            'title'        => 'Inquiry Status Master',
            'singular'     => 'Inquiry Status',
            'table'        => 'inquiry_statuses',
            'folder'       => 'inquiry_statuses',
            'icon'         => 'fa-tags',
            'color'        => '#e08a0a',
            'name_field'   => 'name',
            'list_columns' => [
                ['key' => 'name', 'label' => 'Status'],
                ['key' => 'code', 'label' => 'Code'],
                ['key' => 'color', 'label' => 'Color'],
                ['key' => 'is_default', 'label' => 'Default'],
                ['key' => 'sort_order', 'label' => 'Sort'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Status Name', 'type' => 'text', 'required' => true, 'span' => 2],
                ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => false, 'span' => 1, 'help' => 'e.g. NEW, CONTACTED'],
                ['name' => 'color', 'label' => 'Color', 'type' => 'color', 'required' => false, 'span' => 1, 'help' => 'Badge color'],
                ['name' => 'is_default', 'label' => 'Default for new Inquiry', 'type' => 'checkbox', 'required' => false, 'span' => 1,
                    'help' => 'Checked status auto-selects on Add Inquiry'],
                ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number', 'required' => false, 'span' => 1],
            ],
            'filters' => [
                ['name' => 'q', 'label' => 'Search', 'type' => 'text', 'placeholder' => 'Status name / code'],
            ],
        ],
        'follow_up_statuses' => [
            'key'          => 'follow_up_statuses',
            'title'        => 'Follow Up Status Master',
            'singular'     => 'Follow Up Status',
            'table'        => 'follow_up_statuses',
            'folder'       => 'follow_up_statuses',
            'icon'         => 'fa-clock-rotate-left',
            'color'        => '#0EA5E9',
            'name_field'   => 'name',
            'list_columns' => [
                ['key' => 'name', 'label' => 'Status'],
                ['key' => 'code', 'label' => 'Code'],
                ['key' => 'color', 'label' => 'Color'],
                ['key' => 'is_default', 'label' => 'Default'],
                ['key' => 'sort_order', 'label' => 'Sort'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Status Name', 'type' => 'text', 'required' => true, 'span' => 2],
                ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => false, 'span' => 1, 'help' => 'e.g. PENDING, DONE'],
                ['name' => 'color', 'label' => 'Color', 'type' => 'color', 'required' => false, 'span' => 1, 'help' => 'Badge color'],
                ['name' => 'is_default', 'label' => 'Default for new Inquiry', 'type' => 'checkbox', 'required' => false, 'span' => 1,
                    'help' => 'Checked status auto-selects on Add Inquiry'],
                ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number', 'required' => false, 'span' => 1],
            ],
            'filters' => [
                ['name' => 'q', 'label' => 'Search', 'type' => 'text', 'placeholder' => 'Status name / code'],
            ],
        ],
        'inquiry_sources' => [
            'key'          => 'inquiry_sources',
            'title'        => 'Source of Inquiry Master',
            'singular'     => 'Source of Inquiry',
            'table'        => 'inquiry_sources',
            'folder'       => 'inquiry_sources',
            'icon'         => 'fa-bullhorn',
            'color'        => '#EA580C',
            'name_field'   => 'name',
            'list_columns' => [
                ['key' => 'name', 'label' => 'Source'],
                ['key' => 'code', 'label' => 'Code'],
                ['key' => 'color', 'label' => 'Color'],
                ['key' => 'is_default', 'label' => 'Default'],
                ['key' => 'sort_order', 'label' => 'Sort'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Source Name', 'type' => 'text', 'required' => true, 'span' => 2],
                ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => false, 'span' => 1, 'help' => 'e.g. WEB, REF'],
                ['name' => 'color', 'label' => 'Color', 'type' => 'color', 'required' => false, 'span' => 1, 'help' => 'Badge color'],
                ['name' => 'is_default', 'label' => 'Default for new Inquiry', 'type' => 'checkbox', 'required' => false, 'span' => 1,
                    'help' => 'Checked source auto-selects on Add Inquiry'],
                ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number', 'required' => false, 'span' => 1],
            ],
            'filters' => [
                ['name' => 'q', 'label' => 'Search', 'type' => 'text', 'placeholder' => 'Source name / code'],
            ],
        ],
        'customer_types' => [
            'key'          => 'customer_types',
            'title'        => 'Customer Type Master',
            'singular'     => 'Customer Type',
            'table'        => 'customer_types',
            'folder'       => 'customer_types',
            'icon'         => 'fa-user-tag',
            'color'        => '#0D9488',
            'name_field'   => 'name',
            'list_columns' => [
                ['key' => 'name', 'label' => 'Customer Type'],
                ['key' => 'code', 'label' => 'Code'],
                ['key' => 'sort_order', 'label' => 'Sort'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Customer Type Name', 'type' => 'text', 'required' => true, 'span' => 2],
                ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => false, 'span' => 1, 'help' => 'e.g. RETAIL, DEALER'],
                ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number', 'required' => false, 'span' => 1],
            ],
            'filters' => [
                ['name' => 'q', 'label' => 'Search', 'type' => 'text', 'placeholder' => 'Type name / code'],
            ],
        ],
        'product_categories' => [
            'key'          => 'product_categories',
            'title'        => 'Product Category Master',
            'singular'     => 'Product Category',
            'table'        => 'product_categories',
            'folder'       => 'product_categories',
            'icon'         => 'fa-boxes-stacked',
            'color'        => '#7C3AED',
            'name_field'   => 'name',
            'list_columns' => [
                ['key' => 'name', 'label' => 'Category'],
                ['key' => 'code', 'label' => 'Code'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Category Name', 'type' => 'text', 'required' => true, 'span' => 2],
                ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => false, 'span' => 1, 'help' => 'e.g. ELEC, FURN'],
            ],
            'filters' => [
                ['name' => 'q', 'label' => 'Search', 'type' => 'text', 'placeholder' => 'Category name / code'],
            ],
        ],
        'product_subcategories' => [
            'key'          => 'product_subcategories',
            'title'        => 'Product Subcategory Master',
            'singular'     => 'Product Subcategory',
            'table'        => 'product_subcategories',
            'folder'       => 'product_subcategories',
            'icon'         => 'fa-box',
            'color'        => '#DB2777',
            'name_field'   => 'name',
            'list_columns' => [
                ['key' => 'name', 'label' => 'Subcategory'],
                ['key' => 'product_category_id', 'label' => 'Category', 'fk_table' => 'product_categories'],
                ['key' => 'code', 'label' => 'Code'],
            ],
            'fields' => [
                ['name' => 'product_category_id', 'label' => 'Product Category', 'type' => 'select', 'required' => true, 'span' => 1,
                    'options_from' => 'product_categories', 'bind' => 'int'],
                ['name' => 'name', 'label' => 'Subcategory Name', 'type' => 'text', 'required' => true, 'span' => 2],
                ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => false, 'span' => 1],
            ],
            'filters' => [
                ['name' => 'product_category_id', 'label' => 'Category', 'type' => 'select', 'options_from' => 'product_categories'],
                ['name' => 'q', 'label' => 'Search', 'type' => 'text', 'placeholder' => 'Subcategory name / code'],
            ],
        ],
        'products' => [
            'key'          => 'products',
            'title'        => 'Product Master',
            'singular'     => 'Product',
            'table'        => 'products',
            'folder'       => 'products',
            'icon'         => 'fa-cube',
            'color'        => '#2563EB',
            'name_field'   => 'name',
            'list_columns' => [
                ['key' => 'name', 'label' => 'Product'],
                ['key' => 'code', 'label' => 'Code'],
                ['key' => 'product_category_id', 'label' => 'Category', 'fk_table' => 'product_categories'],
                ['key' => 'product_subcategory_id', 'label' => 'Subcategory', 'fk_table' => 'product_subcategories'],
                ['key' => 'unit', 'label' => 'Unit'],
                ['key' => 'price', 'label' => 'Price'],
            ],
            'fields' => [
                ['name' => 'product_category_id', 'label' => 'Product Category', 'type' => 'select', 'required' => true, 'span' => 1,
                    'options_from' => 'product_categories', 'bind' => 'int'],
                ['name' => 'product_subcategory_id', 'label' => 'Product Subcategory', 'type' => 'select', 'required' => false, 'span' => 1,
                    'options_from' => 'product_subcategories', 'bind' => 'int',
                    'parent' => 'product_category_id', 'parent_key' => 'product_category_id'],
                ['name' => 'name', 'label' => 'Product Name', 'type' => 'text', 'required' => true, 'span' => 2],
                ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => false, 'span' => 1],
                ['name' => 'unit', 'label' => 'Unit', 'type' => 'text', 'required' => false, 'span' => 1, 'help' => 'e.g. Pcs, Kg, Ltr'],
                ['name' => 'price', 'label' => 'Price', 'type' => 'number', 'required' => false, 'span' => 1, 'step' => '0.01', 'bind' => 'float'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false, 'span' => 3],
            ],
            'filters' => [
                ['name' => 'product_category_id', 'label' => 'Category', 'type' => 'select', 'options_from' => 'product_categories'],
                ['name' => 'product_subcategory_id', 'label' => 'Subcategory', 'type' => 'select', 'options_from' => 'product_subcategories',
                    'parent' => 'product_category_id', 'parent_key' => 'product_category_id'],
                ['name' => 'q', 'label' => 'Search', 'type' => 'text', 'placeholder' => 'Product name / code'],
            ],
        ],
    ];
}

function getMasterConfig($key)
{
    $all = getMastersConfig();
    return $all[$key] ?? null;
}
