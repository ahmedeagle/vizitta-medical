<?php

use App\Models\Provider;

//test branch v2
Route::get('/optimize', function () {
    exec('optimize:clear');
    return '<h1>Composer dump-autoload</h1>';
});

Route::get('/test', 'UserController@test');

// Composer dump-autoload:
Route::get('/composer-dump-autoload', function () {
    exec('composer dump-autoload');
    return '<h1>Composer dump-autoload</h1>';
});
//});


//Route::post('AutoUpdateUserLocation','UserController@AutoUpdateUserLocation') -> middleware('api');

Route::group(['middleware' => ['CheckPassword', 'ChangeLanguage', 'api']], function () {

    Route::post('settings', 'GlobalController@settings')->name('settings');
    Route::post('development/company', 'GlobalController@getDevelopmentCompanyInfo')->name('settings');
    Route::post('brands', 'GlobalController@brands')->name('brands');
    Route::post('subscriptions', 'GlobalController@subscriptions')->name('subscriptions');
    Route::post('tickets', 'ProviderController@getTickets')->name('provider.tickets');
    Route::post('new/ticket ', 'ProviderController@newTicket')->name('provider.add.ticket');
    Route::post('AddMessage ', 'ProviderController@AddMessage')->name('provider.AddMessage');
    Route::post('GetTicketMessages', 'ProviderController@GetTicketMessages')->name('provider.GetTicketMessages');

    Route::post('reporting/types', 'GlobalController@getReportingTypes')->name('reportingTypes');
    Route::post('sensitivities', 'MedicalProfileController@showSensitivities')->name('sensitivities');
    Route::post('reservation/cancel/reasons', 'GlobalController@getReasons')->name('reasons');
    Route::post('categories', 'GlobalController@getCategories')->name('categories');
    Route::post('cities', 'CityController@index')->name('cities');
    Route::post('districts', 'CityController@getDistricts')->name('districts');
    Route::post('branches', 'GlobalController@getBranches')->name('branches');
    Route::post('insurance_companies', 'InsuranceCompanyController@index')->name('insurance.companies');
    Route::post('agreement', 'GlobalController@getAgreement')->name('agreement');
    Route::post('reservation/notes', 'GlobalController@getReservationNotes')->name('notes');
    Route::post('reservation/rules', 'GlobalController@getReservationRules')->name('reservation.rules');
    Route::post('providerRegisteration/rules', 'GlobalController@getProviderRegisterationRules')->name('Pregisteration.rules');
    Route::post('payment/methods', 'GlobalController@getPaymentMethods')->name('payment.methods');
    Route::post('specifications', 'GlobalController@getSpecifications')->name('specifications');
    Route::post('coupons/categories', 'GlobalController@getCouponsCategories')->name('couponsCategories');
    Route::post('offers/banners', 'OffersController@banners');

    Route::group(['prefix' => 'v2'], function () {
        Route::post('specifications', 'GlobalController@getSpecificationsV2')->name('specifications');
        Route::post('services/specifications', 'GlobalController@getServiceSpecificationsV2');
        Route::post('services/specifications-byType', 'GlobalController@getServiceSpecificationsV2ByType');
        Route::post('coupons/categories', 'GlobalController@getCouponsCategoriesV2');
        Route::post('coupons/filters', 'GlobalController@getCouponsFilters');
        Route::group(['prefix' => 'offers'], function () {
            Route::post('categories', 'GlobalController@getCouponsCategoriesV2');
            Route::post('subcategory', 'GlobalController@getOfferSubcategories');
            Route::post('filters', 'OffersController@getOfferFilters');
            Route::post('banners', 'OffersController@bannersV2');
        });
    });
    Route::post('nationalities', 'GlobalController@getNationalities')->name('nationalities');
    Route::post('app/data', 'GlobalController@getAppData')->name('app.data');
    Route::post('doctor/nicknames', 'GlobalController@getNicknames')->name('doctor.nicknames');
    // Route::post('logout', 'GlobalController@logout')->name('logout');

    // User routes
    Route::group(['prefix' => 'user'], function () {
        Route::post('register', 'UserController@store')->name('user.register');
        Route::post('login', 'UserController@login')->name('user.login');
        Route::post('records', 'UserController@getRecords')->name('user.records');
        Route::post('medical/profile', 'MedicalProfileController@show')->name('user.medical.profile');
        Route::post('medical/profile/update', 'MedicalProfileController@store')->name('update.medical.profile');
        Route::post('search', 'GlobalController@search')->name('search');
        Route::post('provider-details', 'GlobalController@getAllProviderDetails');
        Route::post('featured/providers', 'ProviderController@featuredProviders')->name('user.featured.providers');
        Route::post('offers/{featured?}', 'OffersController@index')->name('user.offers');
        Route::group(['prefix' => 'pay'], function () {
            Route::post('get_checkout_id', 'DoctorController@get_checkout_id');
            Route::post('check_payment_status', 'DoctorController@checkPaymentStatus');
            Route::post('apple_pay_get_checkout_id', 'DoctorController@get_checkout_id_apple_pay');
            Route::post('apple_pay_check_payment_status', 'DoctorController@checkPaymentStatus_apple_pay');

            Route::post('stc_checkout_id', 'DoctorController@get_checkout_id_stc_pay');
         });
        Route::group(['prefix' => 'services'], function () {
            Route::post('/', 'ServiceController@index');
            Route::group(['prefix' => 'v2'], function () {
                Route::post('/', 'ServiceController@indexV2');
            });

            Route::post('rates', 'ServiceController@getServiceRates');
        });
        Route::group(['prefix' => 'consulting'], function () {
            Route::post('doctors', 'ConsultingController@getConsultingDoctors');
            Route::post('info', 'ConsultingController@getConsultingIfo');
            Route::group(['middleware' => ['CheckUserToken', 'CheckUserStatus']], function () {
                Route::post('current/reservations', 'ConsultingController@getCurrentConsultingReserves');
                Route::post('finish/reservations', 'ConsultingController@getFinishedConsultingReserves');
                Route::post('reservations', 'ConsultingController@getConsultingReserves');
                Route::group(['prefix' => 'chatting'], function () {
                    Route::post('start', 'ChattingController@startChatting');
                    Route::post('send', 'ChattingController@sendMessage');
                });
            });
        });

        Route::group(['prefix' => 'v2'], function () {
            Route::group(['prefix' => 'offers'], function () {
                Route::post('/', 'OffersController@indexV2');
                Route::post('details', 'OffersController@showV2');
                Route::post('times', 'OffersController@getTimes');
                Route::post('times/dayCode', 'OffersController@getTimesAsArrayOfDayCodes');
                Route::post('available/times', 'OffersController@getAvailableTimes');
                Route::post('reserve', 'OffersController@reserveTime')->middleware(['CheckUserToken', 'CheckUserStatus']);
                Route::post('reservation-details', 'OffersController@getReservationDetails')->middleware(['CheckUserToken', 'CheckUserStatus']);
            });

            Route::post('register', 'UserController@storeV2');
            Route::post('verify/phone', 'UserController@verifyPhone');
            Route::post('records', 'UserController@getRecordsV2');
        });
        Route::post('offer/details', 'OffersController@show')->name('offer.show');
        Route::post('offer/doctors', 'OffersController@doctors')->name('offer.doctors');
        Route::post('custom/pages', 'CustomPagesController@getUserPages')->name('user.custom.pages');
        Route::post('custom/page', 'CustomPagesController@getUserPage')->name('user.custom.page');
        Route::post('activate/account', 'UserController@activateAccount')->name('user.activate.account');
        Route::post('resend/activation', 'UserController@resendActivation')->name('user.resend.activation');
        Route::post('check/id', 'UserController@checkID')->name('user.check.id');
        Route::post('check/mobile', 'UserController@checkMobil')->name('user.check.mobile');
        // user which authenticated
        Route::group(['middleware' => 'CheckUserToken'], function () {
            Route::post('logout', 'UserController@logout')->name('user.logout');
            Route::post('notifications', 'UserController@notifications');
            Route::post('notifications-mark-as-seen', 'UserController@MarknotificationsAsSeen');
        });
        // doctor routes
        Route::group(['prefix' => 'doctor', 'middleware' => ['CheckUserToken', 'CheckUserStatus']], function () {
            Route::post('reserve', 'DoctorController@reserveTime')->name('user.doctor.reserve');
            Route::post('reservation/update', 'DoctorController@UpdateReservationDateTime')->name('user.doctor.reservation.update');
            Route::post('reservation/update/time', 'UserController@UpdateReservationDateTime')->name('user.update.reservation');
            Route::group(['prefix' => 'v2'], function () {
                Route::post('reserve', 'DoctorController@reserveTimeV2');
            });
        });

        // services routes
        Route::group(['prefix' => 'services', 'middleware' => ['CheckUserToken', 'CheckUserStatus']], function () {
            //  Route::post('reserve', 'ServiceController@reserveTime');
            Route::post('reservation/update', 'ServiceController@UpdateReservationDateTime');
//            Route::post('pay/get_checkout_id', 'ServiceController@get_checkout_id');
            //          Route::post('pay/check_payment_status', 'ServiceController@checkPaymentStatus');
        });

        // user which activated and authenticated
        Route::group(['middleware' => ['CheckUserStatus', 'CheckUserToken']], function () {
            Route::post('report/comment', 'UserController@reportingComment')->name('reportingTypes');
            Route::post('data', 'UserController@getUserData')->name('user.data');
            Route::post('update', 'UserController@update')->name('user.update');
            Route::post('update/location', 'UserController@updateUserLocation')->name('user.update.location');
            Route::post('check/promocode', 'PromoCodeController@checkPromoCode')->name('user.check.promocode');
            Route::post('current/reservations', 'UserController@getCurrentReserves')->name('user.current.reservations');
            Route::post('finished/reservations', 'UserController@getFinishedReserves')->name('user.finished.reservations');
            Route::post('reservation/details', 'UserController@ReservationDetails');
            Route::post('offer/reservations', 'OffersController@getAllOffersReservations');
            Route::post('points', 'UserController@getPoints')->name('user.points');
            Route::post('rate', 'UserController@userRating')->name('user.rate');


            ################### services rate and update rate routes #################
            Route::group(['prefix' => 'rate'], function () {
                Route::post('service', 'UserController@userRatingService');
            });
            ################### End services rate routes #################

            Route::post('provider/rates', 'UserController@getProviderRate')->name('user.provider.rate');
            Route::post('favourite/doctors', 'UserController@getFavouriteDoctors')->name('user.favourite.doctors');
            Route::post('favourite/providers', 'UserController@getFavouriteProviders')->name('user.favourite.providers');
            Route::post('remove/favourite', 'UserController@removeFromFavourite')->name('user.remove.favourite');
            Route::post('add/favourite', 'UserController@addFavourite')->name('user.add.favourite');
            // Route::post('messages', 'UserController@getUserMessages')->name('user.messages');
            // Route::post('message/replies', 'UserController@getUserMessageReplies')->name('user.message.replies');
            //Route::post('send/message', 'UserController@addNewMessages')->name('user.add.message');
            //  Route::post('info', function(){
            //    return auth('user-api')->user();
            //});
            Route::post('savePayment', 'OffersController@saveOfferPaymentDetails')->name('savePaymentDetails');
            Route::post('coupon/send', 'OffersController@sendCouponToMobile')->name('savePaymentDetails1');
            Route::group(['prefix' => 'v2'], function () {
                Route::post('invitation_code', 'UserController@getInvitationCode');
                Route::post('reject/reservation', 'UserController@RejectReservation');
                Route::post('update', 'UserController@updateV2');
            });
        });
    });

    Route::group(['prefix' => 'provider'], function () {

        Route::post('activate/account', 'ProviderController@activateAccount')->name('provider.activate.account');
        Route::post('reset/password', 'ProviderController@resetPassword')->name('provider.password.reset');
        Route::post('report/comment', 'ProviderController@reportingComment')->name('reportingTypes');
        Route::post('register', 'ProviderController@store')->name('provider.register');
        Route::post('login', 'ProviderController@login')->name('provider.login');
        Route::post('/forgetPassword', "ProviderController@forgetPassword");
        Route::post('rates', 'UserController@getProviderRate')->name('user.provider.rate');
        Route::group(['middleware' => 'CheckProviderToken'], function () {
            Route::post('resend/activation', 'ProviderController@resendActivation')->name('provider.resend.activation');
        });
        Route::post('view', 'ProviderController@show')->name('provider.view'); // get minimum data for provider
        Route::post('doctors', 'ProviderController@getProviderDoctors')->name('provider.doctors'); // get provider doctors

        Route::group(['prefix' => 'v2'], function () {
            Route::post('doctors', 'ProviderController@getProviderDoctorsV2'); // get provider doctors
            Route::post('rates', 'UserController@getProviderRateV2'); // get provider doctors
        });

        Route::post('types', 'ProviderController@getProviderTypes')->name('provider.types');
        // doctor routes
        Route::group(['prefix' => 'doctor'], function () {
            Route::post('view', 'DoctorController@show')->name('provider.doctor.view');
            Route::post('times', 'DoctorController@getTimes')->name('provider.doctor.times');
            Route::post('times/dayCode', 'DoctorController@getTimesAsArrayOfDayCodes')->name('provider.doctor.times.codes');
            Route::post('available/times', 'DoctorController@getAvailableTimes')->name('provider.doctor.available.times');
        });

        // provider which has token
        Route::group(['middleware' => ['CheckProviderToken', 'CheckProviderStatus']], function () {
            Route::group(['prefix' => 'branch'], function () {
                Route::post('doctors', 'ProviderBranchController@branchDoctors')->name('provider.branch.doctors');
                Route::post('add/reservation', 'ProviderBranchController@addReservation')->name('provider.branch.add.reservation');
                Route::post('update/reservation', 'ProviderBranchController@UpdateReservationDateTime')->name('provider.update.reservation');
                Route::post('reservations', 'ProviderBranchController@branchesFixedReservations')->name('provider.branch.reservations');
            });
            // services routes
            Route::group(['prefix' => 'services'], function () {
                Route::post('index', 'GlobalProviderController@getProviderServices');
                Route::post('getAllProviderBranchesList', 'GlobalProviderController@getAllProviderBranchesList');
                Route::post('store', 'GlobalProviderController@storeService');
                Route::post('edit', 'GlobalProviderController@editService');
                Route::post('update', 'GlobalProviderController@updateService');
                Route::post('destroy', 'GlobalProviderController@destroyService');
                Route::post('toggle', 'GlobalProviderController@toggleService');
                Route::post('check-has-home-visits', 'GlobalProviderController@checkHomeVisits');
            });
            // consulting routes
            Route::group(['prefix' => 'consulting'], function () {
                Route::post('index-current', 'GlobalProviderController@getProviderCurrentConsultingReservations');
                Route::post('index-finished', 'GlobalProviderController@getProviderFinishedConsultingReservations');
            });

            Route::group(['prefix' => 'notifications'], function () {
                Route::post('/', 'ProviderController@notifications');
                Route::post('mark-as-seen', 'ProviderController@MarknotificationsAsSeen');
            });

            // reservations-record routes
            Route::group(['prefix' => 'reservations-record'], function () {
                Route::post('index', 'GlobalProviderController@getAllReservationsRecord');
            });

            Route::post('logout', 'ProviderController@logout')->name('provider.logout');
            Route::post('custom/pages', 'CustomPagesController@getProviderPages')->name('provider.custom.pages');
            Route::post('custom/page', 'CustomPagesController@getProviderPage')->name('provider.custom.page');
            Route::post('current/reservations', 'ProviderController@getCurrentReservations')->name('provider.current.reservations');
            Route::post('new/reservations', 'ProviderController@getNewReservations')->name('provider.new.reservations');
            Route::post('accept/reservation', 'ProviderController@AcceptReservation')->name('provider.accept.reservation');
            Route::post('reject/reservation', 'ProviderController@RejectReservation')->name('provider.reject.reservation');
            Route::post('complete/reservation', 'ProviderController@completeReservation')->name('provider.complete.reservation');
            Route::post('reservation/details', 'ProviderController@ReservationDetails')->name('provider.reservation.details'); // for mobile application allow only for branches
            Route::post('reservation/details/front', 'ProviderController@ReservationDetailsFront')->name('provider.reservation.details.front'); // for front end allow for main provider only

            Route::post('branches', 'ProviderBranchController@index')->name('provider.branches');
            Route::post('hide/branch', 'ProviderBranchController@hide')->name('provider.hide.branch');
            Route::post('delete/branch', 'ProviderBranchController@destroy')->name('provider.delete.branch');
            Route::post('add/branch', 'ProviderBranchController@store')->name('provider.add.branch');
            Route::post('update/branch', 'ProviderBranchController@update')->name('provider.update.branch');
            Route::post('hide/doctor', 'DoctorController@hide')->name('provider.hide.doctor');
            Route::post('delete/doctor', 'DoctorController@destroy')->name('provider.delete.doctor');
            Route::post('add/doctor', 'DoctorController@store')->name('provider.add.doctor');
            Route::post('edit/doctor', 'DoctorController@edit');
            Route::post('update/doctor', 'DoctorController@update')->name('provider.update.doctor');
            Route::post('reservations', 'ProviderBranchController@AllReservations')->name('provider.reservations');
            Route::post('PrepareUpdateProfile', 'ProviderController@prepare_update_provider_profile')->name('provider.edit.profile');
            Route::post('profile/update', 'ProviderController@update_provider_profile')->name('provider.update.profile');

            // profile for front end angular api
            Route::post('profile/update/general', 'ProviderController@update_provider_profile_general')->name('provider.update.profile.general');
            Route::post('profile/update/mobile', 'ProviderController@update_provider_profile_mobile')->name('provider.update.profile.mobile');
            Route::post('profile/update/password', 'ProviderController@update_provider_profile_password')->name('provider.update.profile.password');
            Route::post('PrepareUpdateProfile', 'ProviderController@prepare_update_provider_profile')->name('provider.edit.profile');
            Route::post('profile/update', 'ProviderController@update_provider_profile')->name('provider.update.profile');
            //Route::post('update', 'ProviderController@update')->name('provider.update');
            Route::post('delete/reservation', 'ProviderBranchController@deleteReservation')->name('provider.delete.reservation');
            Route::post('add/user/record', 'ProviderController@addUserRecord')->name('provider.add.user.record');

            Route::group(['prefix' => 'balance'], function () {
                Route::post('/', 'ProviderController@getBalance')->name('provider.balance');
                Route::post('history', 'BalanceController@getBalanceHistory');
            });

            // Route::post('info', function(){
            //   return auth('provider-api')->user();
            //});

            ################# provider web apis ##############
            Route::group(['prefix' => 'services'], function () {
                Route::post('change-status', 'ServiceController@ChangeReservationStatus');
                Route::post('reservations/details', 'ServiceController@getReservationDetails');
            });

            Route::group(['prefix' => 'offers'], function () {
                Route::post('change-status', 'OffersController@ChangeOfferStatus');
                Route::post('reservations/details', 'OffersController@getProviderReservationDetails');
            });
        });
        //api to get all reservation doctor ,services,consulting and offers reservation
        Route::post('new-reservations', 'ProviderController@getNewReservationsBytype')->middleware(['CheckProviderToken', 'CheckProviderStatus']);
        Route::post('current-reservations', 'ProviderController@getCurrentReservationsBytype')->middleware(['CheckProviderToken', 'CheckProviderStatus']);

        // reservation  records
        Route::post('reservations-records', 'ProviderController@getReservationsRecodes')->middleware(['CheckProviderToken', 'CheckProviderStatus']);
    });
    Route::post('consulting-reasons', 'CPanel\RefusalReasonsController@getConsultingRefusalReasonsList');
    Route::post('app_price_note', 'GlobalController@getAppPriceNote');

    Route::post('get-provider-percentages', 'ProviderController@getProviderPercentages');
});

