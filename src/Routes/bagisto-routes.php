<?php

use Illuminate\Support\Facades\Route;
use Webkul\Bagisto\Http\Controllers\CredentialController;
use Webkul\Bagisto\Http\Controllers\Mappings\AttributeController;
use Webkul\Bagisto\Http\Controllers\Mappings\CategoryFieldController;
use Webkul\Bagisto\Http\Controllers\OptionController;

/**
 * bagisto plugin routes.
 */
Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function () {
    Route::prefix('bagisto')->group(function () {
        Route::controller(CredentialController::class)->group(function () {
            Route::prefix('credentials')->group(function () {
                Route::get('', 'index')->name('admin.bagisto.credentials.index');

                Route::post('create', 'store')->name('admin.bagisto.credentials.store');

                Route::get('edit/{id}', 'edit')->name('admin.bagisto.credentials.edit');

                Route::put('update/{id}', 'update')->name('admin.bagisto.credentials.update');

                Route::delete('{id}', 'destroy')->name('admin.bagisto.credentials.destroy');
            });
        });

        /** Attribute Mapping */
        Route::controller(AttributeController::class)->group(function () {
            Route::prefix('attributes-mapping')->group(function () {
                Route::get('/{id}', 'index')->name('admin.bagisto.mappings.attributes.index');
                Route::post('storeOrUpdate', 'storeOrUpdate')->name('admin.bagisto.mappings.attributes.store');
                Route::post('add-attributes', 'addAdditionalAttributes')->name('admin.bagisto.attributes.add');
                Route::post('remove-attributes', 'removeAdditionalAttributes')->name('admin.bagisto.attributes.remove');
            });
        });

        /** Category Fields Mapping */
        Route::controller(CategoryFieldController::class)->group(function () {
            Route::prefix('category-fields-mapping')->group(function () {
                Route::get('/{id}', 'index')->name('admin.bagisto.mappings.category_fields.index');
                Route::post('storeOrUpdate', 'storeOrUpdate')->name('admin.bagisto.mappings.category_fields.store');
            });
        });

        /** Get option data */
        Route::controller(OptionController::class)->group(function () {
            Route::get('get-bagisto-credentials', 'listBagistoCredential')->name('bagisto.credential.fetch-all');

            Route::get('get-bagisto-channel', 'listChannel')->name('bagisto.channel.fetch-all');

            Route::get('get-bagisto-currency', 'listCurrency')->name('bagisto.currency.fetch-all');

            Route::get('get-bagisto-locale', 'listLocale')->name('bagisto.locale.fetch-all');

            Route::get('get-bagisto-family', 'listFamily')->name('bagisto.family.fetch-all');

            Route::get('get-bagisto-type', 'listType')->name('bagisto.type.fetch-all');

            Route::get('get-attributes', 'fetchAttribute')->name('admin.bagisto.attributes.fetch');
        });

    });
});
