<?php


#### Start Authenticated Routes
Route::group(['middleware' => ['CheckManagerToken:manager-api']], function () {
    Route::prefix('{locale}')->middleware(['setAPILocale'])->where(['locale' => '[a-zA-Z]{2}'])->group(function () {
        ############### Start Visits Routes ##############
        Route::prefix('services')->group(function () {
            Route::post('/', 'ServiceController@index');
            Route::post('store', 'ServiceController@store');
            Route::post('edit', 'ServiceController@edit');
            Route::post('update', 'ServiceController@update');
            Route::post('delete', 'ServiceController@destroy');
            Route::post('get-payments-methods', 'ServiceController@getAllPaymentMethodWithSelectedListServices');
        });
        ############## End Visits Routes ##############

        ############### Banners Routes ##############
        Route::prefix('banners')->group(function () {
            Route::post('/', 'BannerController@index');
            Route::post('create', 'BannerController@create');
            Route::post('store', 'BannerController@store');
            Route::post('edit', 'BannerController@edit');
            Route::post('delete', 'BannerController@destroy');
            Route::post('getOfferSubcategories', 'BannerController@getOfferSubCategoriesByCatId');
            Route::post('/get-reorders', "BannerController@getReorders");
            Route::post('/reorder', "BannerController@saveReorderBanners");
        });
        ############### End Banners Routes ##############
    });
});

