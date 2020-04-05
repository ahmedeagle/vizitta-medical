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
        });
        ############## End Visits Routes ##############
    });
});

