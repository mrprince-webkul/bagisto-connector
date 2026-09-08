<?php

return [
    [
        'code'       => 'name',
        'name'       => 'bagisto::app.bagisto.bagisto-category-fields.field-name.name',
        'type'       => 'text',
        'required'   => true,
        'fixedValue' => '',
        'title'      => 'bagisto::app.bagisto.bagisto-category-fields.title.name',
    ], [
        'code'       => 'description',
        'name'       => 'bagisto::app.bagisto.bagisto-category-fields.field-name.description',
        'type'       => 'textarea',
        'required'   => true,
        'fixedValue' => "'",
        'title'      => 'bagisto::app.bagisto.bagisto-category-fields.title.description',
    ], [
        'code'       => 'display_mode',
        'name'       => 'bagisto::app.bagisto.bagisto-category-fields.field-name.display_mode',
        'type'       => 'select',
        'required'   => true,
        'fixedValue' => 'products_and_description',
        'title'      => 'bagisto::app.bagisto.bagisto-category-fields.title.display_mode',
    ], [
        'code'       => 'position',
        'name'       => 'bagisto::app.bagisto.bagisto-category-fields.field-name.position',
        'type'       => 'text',
        'required'   => true,
        'fixedValue' => '1',
        'title'      => 'bagisto::app.bagisto.bagisto-category-fields.title.position',
    ], [
        'code'       => 'status',
        'name'       => 'bagisto::app.bagisto.bagisto-category-fields.field-name.status',
        'type'       => 'boolean',
        'required'   => false,
        'fixedValue' => '1',
        'title'      => 'bagisto::app.bagisto.bagisto-category-fields.title.status',
    ], [
        'code'       => 'slug',
        'name'       => 'bagisto::app.bagisto.bagisto-category-fields.field-name.slug',
        'type'       => 'text',
        'unique'     => true,
        'required'   => false,
        'fixedValue' => '',
        'title'      => 'bagisto::app.bagisto.bagisto-category-fields.title.slug',
    ], [
        'code'     => 'meta_title',
        'name'     => 'bagisto::app.bagisto.bagisto-category-fields.field-name.meta_title',
        'type'     => 'textarea',
        'required' => false,
        'title'    => 'bagisto::app.bagisto.bagisto-category-fields.title.meta_title',
    ], [
        'code'     => 'meta_keywords',
        'name'     => 'bagisto::app.bagisto.bagisto-category-fields.field-name.meta_keywords',
        'type'     => 'textarea',
        'required' => false,
        'title'    => 'bagisto::app.bagisto.bagisto-category-fields.title.meta_keywords',
    ], [
        'code'     => 'meta_description',
        'name'     => 'bagisto::app.bagisto.bagisto-category-fields.field-name.meta_description',
        'type'     => 'textarea',
        'required' => false,
        'title'    => 'bagisto::app.bagisto.bagisto-category-fields.title.meta_description',
    ], [
        'code'     => 'logo_path',
        'name'     => 'bagisto::app.bagisto.bagisto-category-fields.field-name.logo_path',
        'type'     => 'image',
        'required' => false,
        'title'    => 'bagisto::app.bagisto.bagisto-category-fields.title.logo_path',
    ], [
        'code'     => 'banner_path',
        'name'     => 'bagisto::app.bagisto.bagisto-category-fields.field-name.banner_path',
        'type'     => 'image',
        'required' => false,
        'title'    => 'bagisto::app.bagisto.bagisto-category-fields.title.banner_path',
    ],
];
