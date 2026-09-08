<?php

namespace Webkul\Bagisto\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\Bagisto\Console\Commands\BagistoInstaller;
use Webkul\Bagisto\Console\Commands\InstallSampleData;
use Webkul\DataTransfer\Helpers\Export;

class BagistoServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__.'/../Routes/bagisto-routes.php');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'bagisto');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'bagisto');

        Event::listen('unopim.admin.layout.head', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('bagisto::style');
        });

        /**
         * The create screen is Vue-reactive and the edit screen renders server
         * side, so each needs its own template.
         */
        foreach ([
            'unopim.admin.settings.data_transfer.exports.create.card.scope.after' => 'bagisto::exports.filters',
            'unopim.admin.settings.data_transfer.exports.edit.card.general.after' => 'bagisto::exports.filters-edit',
        ] as $exportFilterHook => $template) {
            Event::listen($exportFilterHook, function ($viewRenderEventManager) use ($template) {
                $viewRenderEventManager->addTemplate($template);
            });
        }

        $this->publishes([
            __DIR__.'/../../publishable' => public_path('themes'),
        ], 'unopim-bagisto-connector');

        $this->app->register(ModuleServiceProvider::class);

        $this->app->register(EventServiceProvider::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                BagistoInstaller::class,
                InstallSampleData::class,
            ]);
        }
    }

    /**
     * Register any application services
     */
    public function register(): void
    {
        $this->registerConfig();

        $this->app->bind(
            Export::class,
            \Webkul\Bagisto\Helpers\Export::class
        );
    }

    /**
     * Register package configurations
     */
    public function registerConfig(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/menu.php', 'menu.admin');

        /** API EndPoint Config */
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/api-end-point.php', 'bagisto-api-end-point');

        /** Bagisto Attributes Config */
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/bagisto-attributes.php', 'bagisto-attributes');

        /** Bagisto Category Fields Config */
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/bagisto-category-fields.php', 'bagisto-category-fields');

        /** Bagisto export Config */
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/exporters.php', 'exporters');

        /** ACL Config */
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/acl.php', 'acl');

        /** Bagisto Unopim Vite Config */
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/unopim-vite.php', 'unopim-vite.viters');
    }
}
