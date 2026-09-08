<?php

namespace Webkul\Bagisto\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'data_transfer.exports.update.after' => [
            'Webkul\Bagisto\Listeners\Export@afterUpdate',
        ],
        'catalog.category_field.create.after' => [
            'Webkul\Bagisto\Listeners\CategoryField@afterCreateOrUpdate',
        ],

        'catalog.category_field.update.after' => [
            'Webkul\Bagisto\Listeners\CategoryField@afterCreateOrUpdate',
        ],
    ];
}
