<?php

return [
    [
        'key'   => 'bagisto',
        'name'  => 'bagisto::app.components.layouts.sidebar.bagisto',
        'route' => 'admin.bagisto.credentials.index',
        'sort'  => 11,
    ], [
        'key'   => 'bagisto.credentials',
        'name'  => 'bagisto::app.components.layouts.sidebar.credentials',
        'route' => 'admin.bagisto.credentials.index',
        'sort'  => 1,
    ], [
        'key'   => 'bagisto.credentials.store',
        'name'  => 'bagisto::app.bagisto.acl.credential.create',
        'route' => 'admin.bagisto.credentials.store',
        'sort'  => 1,
    ], [
        'key'   => 'bagisto.credentials.edit',
        'name'  => 'bagisto::app.bagisto.acl.credential.edit',
        'route' => 'admin.bagisto.credentials.edit',
        'sort'  => 2,
    ], [
        'key'   => 'bagisto.credentials.destroy',
        'name'  => 'bagisto::app.bagisto.acl.credential.delete',
        'route' => 'admin.bagisto.credentials.destroy',
        'sort'  => 3,
    ], [
        'key'   => 'bagisto.attributes_mapping',
        'name'  => 'bagisto::app.components.layouts.sidebar.attributes-mapping',
        'route' => 'admin.bagisto.mappings.attributes.index',
        'sort'  => 2,
    ], [
        'key'   => 'bagisto.category_fields_mapping',
        'name'  => 'bagisto::app.components.layouts.sidebar.category-fields-mapping',
        'route' => 'admin.bagisto.mappings.category_fields.index',
        'sort'  => 3,
    ],
];
