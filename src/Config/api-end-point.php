<?php

return [
    'getChannels' => [
        'endPoint'    => 'settings/channels',
        'contentType' => 'application/json',
    ],

    'category' => [
        'endPoint'    => 'catalog/categories',
        'contentType' => 'application/json',
    ],

    'attribute' => [
        'endPoint'    => 'catalog/attributes',
        'contentType' => 'application/json',
    ],

    'getAttribute' => [
        'endPoint'    => 'catalog/attributes/code',
        'contentType' => 'application/json',
    ],

    'attribute_family' => [
        'endPoint'    => 'catalog/attribute-families',
        'contentType' => 'application/json',
    ],

    'getAttributeFamily' => [
        'endPoint'    => 'catalog/attribute-families/code',
        'contentType' => 'application/json',
    ],

    'product' => [
        'endPoint'    => 'catalog/products',
        'contentType' => 'application/json',
    ],

    'bulk_product' => [
        'endPoint'    => 'catalog/bulk-products',
        'contentType' => 'application/json',
    ],
];
