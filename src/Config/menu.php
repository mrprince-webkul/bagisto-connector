<?php

return [
    /**
     * Bagisto menu
     */
    [
        'key'   => 'bagisto',
        'name'  => 'bagisto::app.components.layouts.sidebar.bagisto',
        'route' => 'admin.bagisto.credentials.index',
        'sort'  => 11,
        'icon'  => 'icon-bagisto',
    ], [
        'key'   => 'bagisto.credentials',
        'name'  => 'bagisto::app.components.layouts.sidebar.credentials',
        'route' => 'admin.bagisto.credentials.index',
        'sort'  => 1,
        'icon'  => '',
    ], [
        'key'    => 'bagisto.attributes_mapping',
        'name'   => 'bagisto::app.components.layouts.sidebar.attributes-mapping',
        'route'  => 'admin.bagisto.mappings.attributes.index',
        'params' => [1],
        'sort'   => 2,
        'icon'   => '',
    ], [
        'key'    => 'bagisto.category_fields_mapping',
        'name'   => 'bagisto::app.components.layouts.sidebar.category-fields-mapping',
        'route'  => 'admin.bagisto.mappings.category_fields.index',
        'params' => [1],
        'sort'   => 3,
        'icon'   => '',
    ],
];
