<?php

Route::group(['middleware' => ['CheckPassword', 'ChangeLanguage', 'api']], function () {

    Route::group(['prefix' => 'services', 'middleware' => ['CheckUserToken', 'CheckUserStatus']], function () {
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
        Route::post('/reserve-consulting-doctor', 'GlobalConsultingController@reserveConsultingDoctor')->middleware(['CheckUserToken', 'CheckUserStatus']);
        Route::post('/rate-consulting-doctor', 'GlobalConsultingController@rateConsultingDoctor')->middleware(['CheckUserToken', 'CheckUserStatus']);
    });

    Route::group(['prefix' => 'medical-center'], function () {
        Route::post('/store', 'GlobalConsultingController@addMedicalCenter');
    });

    Route::group(['prefix' => 'offers', 'middleware' => ['CheckUserToken', 'CheckUserStatus']], function () {
        Route::post('/rate-offer-reservation', 'GlobalVisitsController@rateOfferReservation');
    });

    Route::group(['prefix' => 'reporting', 'middleware' => ['CheckUserToken', 'CheckUserStatus']], function () {
        Route::post('/service-rate', 'GlobalVisitsController@reportService');
    });


});

