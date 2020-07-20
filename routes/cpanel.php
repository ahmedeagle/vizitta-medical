<?php

define('PAGINATION_COUNT', 10);

Route::get('clearPermissionCach', function () {
    app()['cache']->forget('spatie.permission.cache');
});

Route::prefix('{locale}')->middleware(['setAPILocale'])->where(['locale' => '[a-zA-Z]{2}'])->group(function () {
    Route::post('/login', 'AuthController@login');
});

#### Start Authenticated Routes
Route::group(['middleware' => ['CheckManagerToken:manager-api']], function () {

    Route::prefix('{locale}')->middleware(['setAPILocale'])->where(['locale' => '[a-zA-Z]{2}'])->group(function () {

        // Authentication Routes
        Route::post('/logout', 'AuthController@logout');
        Route::post('/refresh', 'AuthController@refresh');
        Route::post('/me', 'AuthController@me');

        Route::post('/getDashboardStatistics', 'HomeController@index');
        Route::post('/search', 'HomeController@search');

        ########### Start General Routes #################
        Route::post('/all-cities-list', 'GeneralController@getAllCitiesList');
        Route::post('/all-districts-list-by-city-id', 'GeneralController@getAllDistrictsListByCityId');
        Route::post('/all-provider-types-list', 'GeneralController@getAllProviderTypesList');
        Route::post('/all-doctors-nicknames-list', 'GeneralController@getAllDoctorsNicknamesList');
        Route::post('/all-insurance-companies-list', 'GeneralController@getAllInsuranceCompaniesList');
        Route::post('/all-providers-list', 'GeneralController@getAllProvidersList');
        Route::post('/all-branches-list', 'GeneralController@getAllBranchesList');
        Route::post('/all-provider-branches-list/{id}', 'GeneralController@getAllProviderBranchesList');
        Route::post('/all-specifications-list', 'GeneralController@getAllSpecificationsList');
        ########### End General Routes ###################

        ############## Start Insurance Company Routes ##############
        Route::prefix('insurance_company/')->group(function () {
            Route::post('/index', 'InsuranceCompanyController@index');
            Route::post('/show/{id}', 'InsuranceCompanyController@show');
            Route::post('/store', 'InsuranceCompanyController@store');
            Route::post('/update/{id}', 'InsuranceCompanyController@update');
            Route::post('/delete/{id}', 'InsuranceCompanyController@destroy');
            Route::post('/changeStatus', 'InsuranceCompanyController@changeStatus');
        });
        ############## End Insurance Company Routes ##############

        ############### Start Providers Routes ##############
        Route::prefix('providers/')->group(function () {
            Route::post('/index', 'ProviderController@index');
            Route::post('/show/{id}', 'ProviderController@show');
            Route::post('/create', 'ProviderController@create');
            Route::post('/store', 'ProviderController@store');
            Route::post('/edit/{id}', 'ProviderController@edit');
            Route::post('/update/{id}', 'ProviderController@update');
            Route::post('/delete/{id}', 'ProviderController@destroy');
            Route::post('/changeStatus', 'ProviderController@changeStatus');
            Route::post('/addLotteryBranch', 'ProviderController@addLotteryBranch');
            Route::post('/removeLotteryBranch', 'ProviderController@removeLotteryBranch');
            Route::post('/get-reservations-by-type', 'ProviderController@getProviderRservationByType');
            Route::post('/get-all-reservations', 'ProviderController@getAllProviderRservations');
            Route::post('/check-servicesTypes', 'ProviderController@checkProviderHomeService');
        });
        ############## End Providers Routes ##############

        ############### Start Branches Routes ##############
        Route::prefix('branches/')->group(function () {
            Route::post('/index', 'BranchController@index');
            Route::post('/show/{id}', 'BranchController@show');
            Route::post('/create', 'BranchController@create');
            Route::post('/store', 'BranchController@store');
            Route::post('/edit/{id}', 'BranchController@edit');
            Route::post('/update/{id}', 'BranchController@update');
            Route::post('/delete/{id}', 'BranchController@destroy');
            Route::post('/changeStatus', 'BranchController@changeStatus');
            Route::post('/addProviderToFeatured', 'BranchController@addProviderToFeatured');
            Route::post('/removeProviderFromFeatured', 'BranchController@removeProviderFromFeatured');
            Route::group(['prefix' => 'balances'], function () {
                Route::post('/', 'BalanceController@getBranchesBalances');
                Route::post('edit', 'BalanceController@editBranchBalance');
                Route::post('update', 'BalanceController@updateBranchBalance');
                Route::post('history', 'BalanceController@getBalanceHistory');
            });
        });
        ############## End Branches Routes ##############

        ############### Start Doctors Routes ##############
        Route::prefix('doctors/')->group(function () {
            Route::post('/index', 'DoctorController@index');
            Route::post('/show/{id}', 'DoctorController@show');
            Route::post('/create', 'DoctorController@create');
            Route::post('/store', 'DoctorController@store');
            Route::post('/edit/{id}', 'DoctorController@edit');
            Route::post('/update/{id}', 'DoctorController@update');
            Route::post('/delete/{id}', 'DoctorController@destroy');
            Route::post('/changeStatus', 'DoctorController@changeStatus');
            Route::post('/getDoctorDays', 'DoctorController@getDoctorDays');
            Route::post('/getDoctorAvailableTime', 'DoctorController@getDoctorAvailableTime');
            Route::post('/removeShiftTimes', 'DoctorController@removeShiftTimes');

            Route::group(['prefix' => 'balances'], function () {
                Route::post('/', 'BalanceController@getDoctorsBalances');
                Route::post('only-consulting-doctors-withoutBranch', 'BalanceController@consultingDoctors');
                Route::post('only-consulting-doctors-withoutBranch/history', 'BalanceController@consultingDoctorsHistory');
                Route::post('edit', 'BalanceController@editDoctorsBalance');
                Route::post('update', 'BalanceController@updateDoctorsBalance');
            });

        });
        ############## End Doctors Routes ##############

        ############### Start Providers Types Routes ##############
        Route::prefix('providers-types/')->group(function () {
            Route::post('/index', 'ProviderTypesController@index');
            Route::post('/store', 'ProviderTypesController@store');
            Route::post('/edit/{id}', 'ProviderTypesController@edit');
            Route::post('/update/{id}', 'ProviderTypesController@update');
            Route::post('/delete', 'ProviderTypesController@delete');
        });
        ############## End Providers Types Routes ##############

        ############### Start Doctors Specifications Routes ##############
        Route::prefix('doctors-specifications/')->group(function () {
            Route::post('/index', 'SpecificationController@index');
            Route::post('/store', 'SpecificationController@store');
            Route::post('/edit/{id}', 'SpecificationController@edit');
            Route::post('/update/{id}', 'SpecificationController@update');
            Route::post('/delete/{id}', 'SpecificationController@destroy');
        });
        ############## End Doctors Specifications Routes ##############

        ############### Start Doctors Nicknames Routes ##############
        Route::prefix('doctors-nicknames/')->group(function () {
            Route::post('/index', 'NicknameController@index');
            Route::post('/store', 'NicknameController@store');
            Route::post('/edit/{id}', 'NicknameController@edit');
            Route::post('/update/{id}', 'NicknameController@update');
            Route::post('/delete/{id}', 'NicknameController@destroy');
        });
        ############## End Doctors Nicknames Routes ##############

        ############### Start Users Routes ##############
        Route::prefix('users/')->group(function () {
            Route::post('/index', 'UserController@index');
            Route::post('/show/{id}', 'UserController@show');
            Route::post('/delete/{id}', 'UserController@destroy');
            Route::post('/changeStatus', 'UserController@changeStatus');
        });
        ############## End Users Routes ##############

        ############### Start CPanel Users Routes ##############
        Route::prefix('cpanel-users/')->group(function () {
            Route::post('/index', 'UserCPanelController@index');
            Route::post('/create', 'UserCPanelController@create');
            Route::post('/edit/{id}', 'UserCPanelController@edit');
            Route::post('/delete/{id}', 'UserCPanelController@destroy');
            Route::post('/store', 'UserCPanelController@store');
            Route::post('/update/{id}', 'UserCPanelController@update');
        });
        ############## End CPanel Users Routes ##############

        ############### Start Cities Routes ##############
        Route::prefix('cities/')->group(function () {
            Route::post('/index', 'CityController@index');
            Route::post('/store', 'CityController@store');
            Route::post('/edit/{id}', 'CityController@edit');
            Route::post('/update/{id}', 'CityController@update');
            Route::post('/delete/{id}', 'CityController@destroy');
        });
        ############## End Cities Routes ##############

        ############### Start Districts Routes ##############
        Route::prefix('districts/')->group(function () {
            Route::post('/index', 'DistrictController@index');
            Route::post('/store', 'DistrictController@store');
            Route::post('/create', 'DistrictController@create');
            Route::post('/edit/{id}', 'DistrictController@edit');
            Route::post('/update/{id}', 'DistrictController@update');
            Route::post('/delete/{id}', 'DistrictController@destroy');
        });
        ############## End Districts Routes ##############

        ############### Start Custom Pages Routes ##############
        Route::prefix('custom_pages/')->group(function () {
            Route::post('/index', 'CustomPageController@index');
            Route::post('/store', 'CustomPageController@store');
            Route::post('/edit/{id}', 'CustomPageController@edit');
            Route::post('/update/{id}', 'CustomPageController@update');
            Route::post('/delete/{id}', 'CustomPageController@destroy');
            Route::post('/changeStatus', 'CustomPageController@changeStatus');
        });
        ############## End Custom Pages Routes ##############

        ############### Start Nationalities Routes ##############
        Route::prefix('nationalities/')->group(function () {
            Route::post('/index', 'NationalityController@index');
            Route::post('/store', 'NationalityController@store');
            Route::post('/edit/{id}', 'NationalityController@edit');
            Route::post('/update/{id}', 'NationalityController@update');
            Route::post('/delete/{id}', 'NationalityController@destroy');
        });
        ############## End Nationalities Routes ##############

        ############### Start Providers Messages Routes ##############
        Route::prefix('providers_messages/')->group(function () {
            Route::post('/index', 'ProviderMessageController@index')->name('providers_messages');
            Route::post('/show/{id}', 'ProviderMessageController@show')->name('single_provider_messages');
            Route::post('/delete/{id}', 'ProviderMessageController@destroy');
            Route::post('/solvedMessage/{id}', 'ProviderMessageController@solvedMessage');
            Route::post('/reply', 'ProviderMessageController@reply');
        });
        ############## End Providers Messages Routes ##############

        ############### Start Users Messages Routes ##############
        Route::prefix('users_messages/')->group(function () {
            Route::post('/index', 'UserMessageController@index')->name('users_messages');
            Route::post('/show/{id}', 'UserMessageController@show')->name('single_user_messages');
            Route::post('/delete/{id}', 'UserMessageController@destroy');
            Route::post('/solvedMessage/{id}', 'ProviderMessageController@solvedMessage');
            Route::post('/reply', 'UserMessageController@reply');
        });
        ############## End Users Messages Routes ##############

        ############### Start Notifications Routes ##############
        Route::prefix('notifications/')->group(function () {
            Route::post('/index/{type}', 'NotificationsController@index');
            Route::post('/show/{notifyId}/{type}', 'NotificationsController@show');
            Route::post('/delete/{id}', 'NotificationsController@destroy');
            Route::post('/receivers/{notifyId}/{type}', "NotificationsController@getReceivers");
            Route::post("/create/{type}", "NotificationsController@create");
            Route::post("/store", "NotificationsController@store");
            Route::post("/getHeaderNotifications", "NotificationsController@getHeaderNotifications");
            Route::post("/readNotification", "NotificationsController@readNotification");
        });
        ############## End Notifications Routes ##############

        ############### Start Comments Routes ##############
        Route::prefix('comments/')->group(function () {
            Route::post('/index', 'CommentsController@index');
            Route::post('/delete/{id}', 'CommentsController@destroy');
            Route::post('/update', 'CommentsController@update');
        });
        ############## End Comments Routes ##############

        ############### Start Reports Routes ##############
        Route::prefix('reports/')->group(function () {
            Route::post('/index', "CommentsController@getReportsData");
            Route::post('/delete/{id}', "CommentsController@deleteReport");
        });
        ############## End Reports Routes ##############

        ############### Start Refusal Reasons Routes ##############
        Route::prefix('refusal_reasons/')->group(function () {
            Route::post('/index', "RefusalReasonsController@index");
            Route::post('/store', 'RefusalReasonsController@store');
            Route::post('/edit/{id}', 'RefusalReasonsController@edit');
            Route::post('/update/{id}', 'RefusalReasonsController@update');
            Route::post('/delete/{id}', "RefusalReasonsController@destroy");
            Route::post('/getRefusalReasonsList', "RefusalReasonsController@getRefusalReasonsList");
        });
        ############## End Refusal Reasons Routes ##############

        ############### Start Consulting Refusal Reasons Routes ##############
        Route::prefix('consulting/refusal_reasons/')->group(function () {
            Route::post('/index', "RefusalReasonsController@consultingIndex");
            Route::post('/store', 'RefusalReasonsController@consultingStore');
            Route::post('/edit/{id}', 'RefusalReasonsController@consultingEdit');
            Route::post('/update/{id}', 'RefusalReasonsController@consultingUpdate');
            Route::post('/delete/{id}', "RefusalReasonsController@consultingDestroy");
            Route::post('/getRefusalReasonsList', "RefusalReasonsController@getConsultingRefusalReasonsList");
        });
        ############## End Consulting Refusal Reasons Routes ##############

        ############### Start Agreement Routes ##############
        Route::prefix('agreement/')->group(function () {
            Route::post('/index', "AgreementController@index");
            Route::post('/edit', 'AgreementController@edit');
            Route::post('/update', 'AgreementController@update');
        });
        ############## End Agreement Routes ##############

        ############### Start Settings Routes ##############
        Route::prefix('settings/')->group(function () {
            Route::post('/index', "SettingsController@index");
            Route::post('/update', 'SettingsController@update');
        });
        ############## End Settings Routes ##############

        ############### Start Development Routes ##############
        Route::prefix('development/')->group(function () {
            Route::post('/index', "DevelopmentController@index");
            Route::post('/update', 'DevelopmentController@update');
        });
        ############## End Development Routes ##############

        ############### Start Bills Routes ##############
        Route::prefix('bills/')->group(function () {
            Route::post('/index', "BillController@index");
            Route::post('/show/{bill_id}', 'BillController@show');
            Route::post('/delete/{id}', "BillController@destroy");
            Route::post('/addPointToUser', 'BillController@addPointToUser');
        });
        ############## End Bills Routes ##############

        ############### Start Mailing List Routes ##############
        Route::prefix('mailing_list/')->group(function () {
            Route::post('/index', "MailingListController@index");
            Route::post('/delete/{id}', "MailingListController@destroy");
        });
        ############## End Mailing List Routes ##############

        ############### Start Brands Routes ##############
        Route::prefix('brands/')->group(function () {
            Route::post('/index', "BrandsController@index");
            Route::post('/store', 'BrandsController@store');
            Route::post('/delete/{id}', "BrandsController@destroy");
        });
        ############## End Brands Routes ##############

        ############### Start Winners Routes ##############
        Route::prefix('winners/')->group(function () {
            Route::post('/index', "WinnersController@index");
        });
        ############## End Winners Routes ##############

        ############### Start Drawing Routes ##############
        Route::prefix('drawing/')->group(function () {
            Route::post('/index', "LotteryController@index");
            Route::post('/loadGifts', 'LotteryController@loadBranchGifts');
            Route::post('/loadUsers', 'LotteryController@loadGiftUsers');
        });
        ############## End Drawing Routes ##############

        ############### Start Randomization Clinics Routes ##############
        Route::prefix('randomization_clinics/')->group(function () {
            Route::post('/lotteryBranches', "LotteryController@lotteryBranches");
            Route::post('/showBranchGifts/{branchId}', 'LotteryController@showBranchGifts');
            Route::post('/addGiftToBranch', 'LotteryController@addGiftToBranch');
            Route::post('/deleteGiftTo/{giftId}', 'LotteryController@deleteGiftTo');
        });
        ############## End Randomization Clinics Routes ##############

        ############### Start Profile Routes ##############
        Route::prefix('profile/')->group(function () {
            Route::post('edit', 'ProfileController@edit');
            Route::post('update', 'ProfileController@update');
        });
        ############## End Profile Clinics Routes ##############

        ############### Start Reservations Routes ##############
        Route::prefix('reservations/')->group(function () {
            Route::post('/index', "ReservationController@index");
            Route::post('/show/{id}', 'ReservationController@show');
            Route::post('/edit/{id}', 'ReservationController@edit');
            Route::post('/update', 'ReservationController@update');
            Route::post('/delete/{id}', "ReservationController@destroy");
            Route::post('/changeStatus', 'ReservationController@changeStatus');
            Route::post('/rejection', 'ReservationController@rejectReservation');
        });
        ############## End Reservations Routes ##############


        ############### Start Offers Reservations Routes ##############
        Route::prefix('offers/reservations/')->group(function () {
            Route::post('/index', "offersReservationController@index");
            Route::post('/show', 'offersReservationController@show');
            Route::post('/edit', 'offersReservationController@edit');
            Route::post('/update', 'offersReservationController@update');
            Route::post('/delete', "offersReservationController@destroy");
            Route::post('/changeStatus', 'offersReservationController@changeStatus');
            Route::post('/rejection', 'offersReservationController@rejectReservation');
            Route::post('available/times', 'offersReservationController@getAvailableTimes');
        });
        ############## End Offers Reservations Routes ##############

        ############### Start Services Reservations Routes ##############
        Route::prefix('services/reservations/')->group(function () {
            Route::post('/index', "ServicesReservationController@index");
            Route::post('/delete', "ServicesReservationController@destroy");
            Route::post('/changeStatus', 'ServicesReservationController@changeStatus');
            Route::post('/edit', 'ServicesReservationController@edit');
            Route::post('/available/times', 'ServicesReservationController@getClinicServiceAvailableTimes');
            Route::post('/update', 'ServicesReservationController@update');
            //Route::post('/rejection', 'ServicesReservationController@rejectReservation');
        });
        ############## End Services Reservations Routes ##############

        ############### Start approved reservation search Routes ##############
        Route::prefix('approved-reservations/')->group(function () {
            Route::post('/search', "ReservationController@getApprovedReservations");
        });
        ############## End approved reservation search Routes ##############


        Route::post('/get-consulting-categories', 'DoctorConsultingReservationController@getConsultingCategories');

        ############### Start Doctor Consulting Reservations Routes ##############
        Route::prefix('doctor-consulting/reservations/')->group(function () {
            Route::post('/index', "DoctorConsultingReservationController@index");
            Route::post('/delete', "DoctorConsultingReservationController@destroy");
            Route::post('/changeStatus', 'DoctorConsultingReservationController@changeStatus');
            Route::post('/getReservationDetails', 'DoctorConsultingReservationController@getReservationDetails');
        });
        ############## End Doctor Consulting Reservations Routes ##############

        ############### Start Offers Filters Routes ##############
        Route::prefix('offers-filters/')->group(function () {
            Route::post('/index', "OfferFilterController@index");
            Route::post("/store", "OfferFilterController@store");
            Route::post('/edit/{id}', 'OfferFilterController@edit');
            Route::post('/update/{id}', 'OfferFilterController@update');
            Route::post('/delete/{id}', "OfferFilterController@destroy");
        });
        ############## End Offers Filters Routes ##############


        ############### Start Offers Categories Routes ##############
        Route::prefix('offers-categories/')->group(function () {
            Route::post('/index', "OfferCategoriesController@index");
            Route::post("/create", "OfferCategoriesController@create");
            Route::post("/store", "OfferCategoriesController@store");
            Route::post('/edit/{id}', 'OfferCategoriesController@edit');
            Route::post('/update/{id}', 'OfferCategoriesController@update');
            Route::post('/delete/{id}', "OfferCategoriesController@destroy");
            Route::post('/getTime/{id}', "OfferCategoriesController@getTime");
            Route::post('/addToTimer', "OfferCategoriesController@addToTimer");
            Route::post('/reorderCategories', "OfferCategoriesController@reorderCategories");
            Route::post('/saveReorderCategories', "OfferCategoriesController@saveReorderCategories");
        });
        ############## End Offers Categories Routes ##############

        ############### Start Offers Routes ##############
        Route::prefix('offers/')->group(function () {
            Route::post('/index', "OfferController@index");
            Route::post("/branches/{id}", "OfferController@getOfferBranches");
            Route::post("/getChildCategoriesByParentId/{id}", "OfferController@getChildCategoriesByParentId");
            Route::post("/getProviderBranchesList/{id}", "OfferController@getProviderBranchesList");
            Route::post("/create", "OfferController@create");
            Route::post("/store", "OfferController@store");
            Route::post('/edit/{id}', 'OfferController@edit');
            Route::post('/delete/{id}', 'OfferController@destroy');
            Route::post('/update/{id}', 'OfferController@update');
            Route::post('/show/{id}', 'OfferController@show');
        });

        ############## End Offers Routes ##############

        Route::get('transaction-details/{id?}', 'GeneralController@getTransactionDetails');
        Route::post('change-status-by-type', 'GeneralController@changeStatusByType');
        Route::post('general-change-status', 'GeneralController@changeStatus');
        Route::post('general-reservation-counts', 'ReservationController@ReservationCounts');
        Route::post('get-provider-percentages', 'ProviderController@getProviderPercentages');

    });
});

//Route::post('/branch-times', 'HomeController@branchTimes');
