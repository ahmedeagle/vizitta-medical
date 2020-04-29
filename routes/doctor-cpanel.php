<?php


Route::prefix('{locale}/doctor')->middleware(['setAPILocale'])->where(['locale' => '[a-zA-Z]{2}'])->group(function () {
    Route::post('/login', 'DoctorController@login');
});

#### Start Authenticated Routes
Route::group(['middleware' => ['CheckDoctorToken:doctor-api']], function () {

    Route::prefix('{locale}/doctor')->middleware(['setAPILocale'])->where(['locale' => '[a-zA-Z]{2}'])->group(function () {

        // Authentication Routes
        Route::post('/logout', 'DoctorController@logout');
        Route::post('/refresh', 'DoctorController@refresh');

        Route::group(["namespace" => "DoctorArea"], function () {
            Route::post('/reservations/index', 'DoctorReservationsController@index');

        });
    });

});

