<?php

Route::group(['middleware' => ['CheckPassword', 'ChangeLanguage', 'api']], function () {
    Route::group(['middleware' => ['CheckUserToken', 'CheckUserStatus']], function () {

        Route::group(['prefix' => 'services'], function () {
            Route::post('/get-service-available-times', 'GlobalVisitsController@getClinicServiceAvailableTimes');
            Route::post('/reserve-home-clinic-service', 'GlobalVisitsController@reserveHomeClinicService');
            Route::post('/get-rejected-reasons-to-cancel-service', 'GlobalVisitsController@getRejectedReasons');
            Route::post('/reject-service-reservation', 'GlobalVisitsController@rejectServiceReservation');

//            Route::post('/get-all-services-reservations', 'GlobalVisitsController@getAllServicesReservations');

            Route::post('pay/get_checkout_id', 'GlobalVisitsController@get_checkout_id');
            Route::post('pay/check_payment_status', 'GlobalVisitsController@checkPaymentStatus');

        });

    });
    Route::post('/services/get-all-services-reservations', 'GlobalVisitsController@getAllServicesReservations');

});

