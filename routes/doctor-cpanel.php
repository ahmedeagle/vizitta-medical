<?php


Route::group(['prefix' => 'doctor', 'middleware' => ['CheckPassword', 'ChangeLanguage','api']], function () {
    Route::post('/login', 'DoctorController@login');
});

#### Start Authenticated Routes

Route::group(['prefix' => 'doctor', 'middleware' => ['CheckPassword', 'ChangeLanguage', 'CheckDoctorToken:doctor-api']], function () {

    // Authentication Routes
    Route::post('/logout', 'DoctorController@logout');
    Route::post('/refresh', 'DoctorController@refresh');

    Route::group(["namespace" => "DoctorArea", "prefix" => "doctor-area"], function () {
        Route::post('/reservations-index', 'DoctorReservationsController@index');
        Route::post('/get-rejected-reasons', 'DoctorReservationsController@getRejectedReasons');
        Route::post('/change-status', 'DoctorReservationsController@changeStatus');
        Route::post('/balance/history', 'BalanceController@getBalanceHistory');
    });
});


