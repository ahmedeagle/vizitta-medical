<?php

Route::group(['middleware' => ['CheckPassword', 'ChangeLanguage', 'api']], function () {
    Route::group(['middleware' => ['CheckUserToken', 'CheckUserStatus']], function () {

        Route::group(['prefix' => 'services'], function () {
            Route::post('/get-service-available-times', 'GlobalVisitsController@getClinicServiceAvailableTimes');
            Route::post('/reserve-home-clinic-service', 'GlobalVisitsController@reserveHomeClinicService');
            Route::post('/get-rejected-reasons-to-cancel-service', 'GlobalVisitsController@getRejectedReasons');
            Route::post('/reject-service-reservation', 'GlobalVisitsController@rejectServiceReservation');

            Route::post('/get-all-services-reservations', 'GlobalVisitsController@getAllServicesReservations');
            Route::post('/get-service-reservation-details', 'GlobalVisitsController@getServiceReservationDetails');

            Route::post('pay/get_checkout_id', 'GlobalVisitsController@get_checkout_id');
            Route::post('pay/check_payment_status', 'GlobalVisitsController@checkPaymentStatus');

        });

        Route::group(['prefix' => 'consulting'], function () {
            Route::post('/get-consulting-categories', 'GlobalConsultingController@getConsultingCategories');
            Route::post('/get-consulting-doctor-details', 'GlobalConsultingController@getConsultingDoctorDetails');
            Route::post('/get-consulting-doctor-times', 'GlobalConsultingController@getConsultingDoctorTimes');
            Route::post('/reserve-consulting-doctor', 'GlobalConsultingController@reserveConsultingDoctor');
            Route::post('/rate-consulting-doctor', 'GlobalConsultingController@rateConsultingDoctor');
        });

        Route::group(['prefix' => 'medical-center'], function () {
            Route::post('/store', 'GlobalConsultingController@addMedicalCenter');
        });

        Route::group(['prefix' => 'offers'], function () {
            Route::post('/rate-offer-reservation', 'GlobalVisitsController@rateOfferReservation');
        });

        Route::group(['prefix' => 'reporting'], function () {
            Route::post('/service-rate', 'GlobalVisitsController@reportService');
        });

    });

});

