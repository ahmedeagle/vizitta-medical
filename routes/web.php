<?php


/*Route::get('getmonth',function () {

    $months=[];
    $current_month = date("F");
    $months[] =  $current_month;
     for ($i = 1; $i < 12; $i++) {
        $month =  date('F', strtotime("+$i month"));
         $months[] = $month;
    }
      $months;
    $days=array();
    $current_month = 10
    ;
   $current_day = date('d');
    for($d= $current_day; $d<=31; $d++)
    {
        $time=mktime(12, 0, 0, $current_month, $d);
        if (date('m', $time)==$current_month)
            $days[]=date('d-l', $time);
    }
    echo "<pre>";
    print_r($days);
    echo "</pre>";


}) -> name('doctorDays');*/


use App\Models\Provider;
use App\Models\User;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;
use Vinkla\Hashids\Facades\Hashids;


route::get('sendSms', function () {
    $accountSid = env('TWILIO_ACCOUNT_SID');
    $authToken = env('TWILIO_AUTH_TOKEN');
    $twilioNumber = env('TWILIO_NUMBER');
    try {
        $client = new Client($accountSid, $authToken);

        $client->messages->create(
            "+201032878227", [
                "body" => 'Hello',
                "from" => $twilioNumber,
            ]
        );
        Log::info('Message sent to ' . $twilioNumber);
    } catch (TwilioException $e) {
        dd($e);
        Log::error(
            'Could not send SMS notification.' .
            ' Twilio replied with: ' . $e
        );
    }
});

Route::get('/crons', 'Crons@cron_job');

Route::get('/', 'Site\HomeController@index');

Route::group(['prefix' => 'mc33', 'middleware' => ['web', 'ChangeLanguage']], function () {


    Route::get('test', function () {
        $mobile = '0512345678';
        if (!preg_match("~^0\d+$~", $mobile)) {
            $phone = '0' . $mobile;
        } else {
            return $phone = $mobile;
        }

    });

    Route::get('clearPermissionCach', function () {
        app()['cache']->forget('spatie.permission.cache');
    });


    Route::get('dd', function () {
        $v = getDiffBetweenTwoDateIMinute(date('Y-m-d H:i:s'), '2020-07-02 03:00:00');

        return response()->json($v);
    });


    Auth::routes();
});

//فصلناها عن الادمن كرابط ولكن لها نفس الاوسنتيكيشن
Route::group(['prefix' => 'drawing', 'namespace' => 'Dashboard', 'middleware' => ['web', 'auth']], function () {
    Route::get('/', 'LotteryController@getDrawing')->name('admin.lotteries.drawing')->middleware('permission:random_drawing');
});

Route::group(['prefix' => 'mc33', 'namespace' => 'Dashboard', 'middleware' => ['web', 'auth', 'ChangeLanguage']], function () {

    Route::get('/', 'HomeController@index')->name('home');
    Route::get('notifications-center', 'NotificationsController@notificationCenter')->name('notification.center');

    // Insurance Company
    Route::group(['prefix' => 'insurance_company'], function () {
        Route::get('/data', 'InsuranceCompanyController@getDataTable')->name('admin.insurance.company.data');
        Route::get('/', 'InsuranceCompanyController@index')->name('admin.insurance.company')->middleware('permission:show_insurance_company');
        Route::get('/edit/{id}', 'InsuranceCompanyController@edit')->name('admin.insurance.company.edit')->middleware('permission:edit_insurance_company');
        Route::put('/update/{id}', 'InsuranceCompanyController@update')->name('admin.insurance.company.update')->middleware('permission:edit_insurance_company');
        Route::get('/add', 'InsuranceCompanyController@add')->name('admin.insurance.company.add')->middleware('permission:add_insurance_company');
        Route::post('/store', 'InsuranceCompanyController@store')->name('admin.insurance.company.store')->middleware('permission:add_insurance_company');
        Route::get('/delete/{id}', 'InsuranceCompanyController@destroy')->name('admin.insurance.company.delete')->middleware('permission:delete_insurance_company');
        Route::get('/status/{id}/{status}', 'InsuranceCompanyController@changeStatus')->name('admin.insurance.company.status')->middleware('permission:edit_insurance_company');
    });
    // Provider
    Route::group(['prefix' => 'provider'], function () {
        Route::get('/data', 'ProviderController@getDataTable')->name('admin.provider.data');
        Route::get('/', 'ProviderController@index')->name('admin.provider')->middleware('permission:show_providers');
        Route::get('/show/{id}', 'ProviderController@view')->name('admin.provider.view')->middleware('permission:show_providers');
        Route::get('/create', 'ProviderController@create')->name('admin.provider.add')->middleware('permission:add_providers');
        Route::post('/store', 'ProviderController@store')->name('admin.provider.store')->middleware('permission:add_providers');
        Route::get('/edit/{id}', 'ProviderController@edit')->name('admin.provider.edit')->middleware('permission:edit_providers');
        Route::put('/update/{id}', 'ProviderController@update')->name('admin.provider.update')->middleware('permission:edit_providers');
        Route::get('/delete/{id}', 'ProviderController@destroy')->name('admin.provider.delete')->middleware('permission:delete_providers');
        Route::get('/status/{id}/{status}', 'ProviderController@changeStatus')->name('admin.provider.status')->middleware('permission:edit_providers');
    });

    // Doctor
    Route::group(['prefix' => 'doctor'], function () {
        Route::get('/data', 'DoctorController@getDataTable')->name('admin.doctor.data');
        Route::get('/', 'DoctorController@index')->name('admin.doctor')->middleware('permission:show_doctors');
        Route::get('/show/{id}', 'DoctorController@view')->name('admin.doctor.view')->middleware('permission:show_doctors');
        Route::get('/create', 'DoctorController@create')->name('admin.doctor.add')->middleware('permission:add_doctors');
        Route::post('/store', 'DoctorController@store')->name('admin.doctor.store')->middleware('permission:add_doctors');
        Route::get('/edit/{id}', 'DoctorController@edit')->name('admin.doctor.edit')->middleware('permission:edit_doctors');
        Route::put('/update/{id}', 'DoctorController@update')->name('admin.doctor.update')->middleware('permission:edit_doctors');
        Route::get('/delete/{id}', 'DoctorController@destroy')->name('admin.doctor.delete')->middleware('permission:delete_doctors');
        Route::get('/status/{id}/{status}', 'DoctorController@changeStatus')->name('admin.doctor.status')->middleware('permission:edit_doctors');
        Route::get('days', 'DoctorController@getDoctorDays')->name('doctorDays');
        Route::get('/availableTimes/{date}', 'DoctorController@getDoctorAvailableTime')->name('admin.doctor.times');
        Route::post('addshift', 'DoctorController@AddShiftTime')->name('doctor.addshifttimes');
        Route::post('removeshift', 'DoctorController@removeShiftTimes')->name('doctor.removeshifttimes');

    });
    // Doctor Specification
    Route::group(['prefix' => 'specification'], function () {
        Route::get('/data', 'SpecificationController@getDataTable')->name('admin.specification.data');
        Route::get('/', 'SpecificationController@index')->name('admin.specification')->middleware('permission:show_specialists');
        Route::get('/add', 'SpecificationController@add')->name('admin.specification.add')->middleware('permission:add_specialists');
        Route::post('/store', 'SpecificationController@store')->name('admin.specification.store')->middleware('permission:add_specialists');
        Route::get('/edit/{id}', 'SpecificationController@edit')->name('admin.specification.edit')->middleware('permission:edit_specialists');
        Route::put('/update/{id}', 'SpecificationController@update')->name('admin.specification.update')->middleware('permission:edit_specialists');
        Route::get('/delete/{id}', 'SpecificationController@destroy')->name('admin.specification.delete')->middleware('permission:delete_specialists');
    });

    //  coupon  categories
    Route::group(['prefix' => 'promoCategories'], function () {
        Route::get('/data', 'PromoCategoriesController@getDataTable')->name('admin.promoCategories.data');
        Route::get('/', 'PromoCategoriesController@index')->name('admin.promoCategories');
        Route::get('/add', 'PromoCategoriesController@add')->name('admin.promoCategories.add');
        Route::post('/store', 'PromoCategoriesController@store')->name('admin.promoCategories.store');
        Route::get('/edit/{id}', 'PromoCategoriesController@edit')->name('admin.promoCategories.edit');
        Route::put('/update/{id}', 'PromoCategoriesController@update')->name('admin.promoCategories.update');
        Route::get('/delete/{id}', 'PromoCategoriesController@destroy')->name('admin.promoCategories.delete');
        Route::get('/reorder', 'PromoCategoriesController@reorder')->name('admin.promoCategories.reorder');
        Route::post('/reorder', 'PromoCategoriesController@saveReorder')->name('admin.promoCategories.reorder.save');
        Route::post('/addTotimer', 'PromoCategoriesController@addToTimer')->name('admin.promoCategories.addToTimer');
    });

    ######################################################################################

    //  Start offer categories routes
    Route::group(['prefix' => 'offerCategories'], function () {
        Route::get('/data', 'OfferCategoriesController@getDataTable')->name('admin.offerCategories.data');
        Route::get('/', 'OfferCategoriesController@index')->name('admin.offerCategories');
        Route::get('/add', 'OfferCategoriesController@add')->name('admin.offerCategories.add');
        Route::post('/store', 'OfferCategoriesController@store')->name('admin.offerCategories.store');
        Route::get('/edit/{id}', 'OfferCategoriesController@edit')->name('admin.offerCategories.edit');
        Route::put('/update/{id}', 'OfferCategoriesController@update')->name('admin.offerCategories.update');
        Route::get('/delete/{id}', 'OfferCategoriesController@destroy')->name('admin.offerCategories.delete');
        Route::get('/reorderCategories', 'OfferCategoriesController@reorder')->name('admin.offerCategories.reorder');
        Route::post('/reorder', 'OfferCategoriesController@saveReorder')->name('admin.offerCategories.reorder.save');
        Route::post('/addToTimer', 'OfferCategoriesController@addToTimer')->name('admin.offerCategories.addToTimer');
        Route::post('/subcategories', 'OfferCategoriesController@getSubcategories')->name('admin.offerCategories.subcategories');
    });
    //  End offer categories routes

    //  Start Offers Routes
    Route::group(['prefix' => 'offers'], function () {

        Route::group(['prefix' => 'banners'], function () {
            Route::get('/', 'BannerController@index')->name('admin.offers.banners');
            Route::get('/add', 'BannerController@create')->name('admin.offers.banners.add');
            Route::post('/add', 'BannerController@store')->name('admin.offers.banners.save');
            Route::get('/delete/{id}', 'BannerController@destroy')->name('admin.offers.banners.delete');
        });


        Route::group(['prefix' => 'mainbanners'], function () {
            Route::get('/', 'MainBannerController@index')->name('admin.offers.mainbanners');
            Route::get('/add', 'MainBannerController@create')->name('admin.offers.mainbanners.add');
            Route::post('/add', 'MainBannerController@store')->name('admin.offers.mainbanners.save');
            Route::get('/delete/{id}', 'MainBannerController@destroy')->name('admin.offers.mainbanners.delete');
        });


        Route::get('/data', 'OfferController@getDataTable')->name('admin.offers.data');
        Route::get('/getDataTableOfferBranches/{promoId}', 'OfferController@getDataTableOfferBranches')->name('admin.offers.databranch');
//        Route::get('/getDataTablePromoCodeDoctors/{promoId}', 'OfferController@getDataTablePromoCodeDoctors')->name('admin.promoCode.datadoctor');
        Route::get('/', 'OfferController@index')->name('admin.offers')->middleware('permission:show_coupons');
        Route::get('/show/{id}', 'OfferController@view')->name('admin.offers.view')->middleware('permission:show_coupons');
        Route::get('/add', 'OfferController@add')->name('admin.offers.add')->middleware('permission:add_coupons');
        Route::post('/store', 'OfferController@store')->name('admin.offers.store')->middleware('permission:add_coupons');
        Route::post('/getprovider/branches', 'OfferController@getProviderBranches')->name('admin.offers.providerbranches');
        Route::post('/getChildCatById', 'OfferController@getChildCatById')->name('admin.offers.getChildCatById');
//        Route::post('/getbranch/doctors', 'OfferController@getBranchDoctors')->name('admin.promoCode.brancheDoctors');
        Route::get('/branches/{id}', 'OfferController@branches')->name('admin.offers.branches');
//        Route::get('/doctors/{id}', 'OfferController@doctors')->name('admin.promoCode.doctors');
        Route::get('/edit/{id}', 'OfferController@edit')->name('admin.offers.edit')->middleware('permission:edit_coupons');
        Route::put('/update/{id}', 'OfferController@update')->name('admin.offers.update')->middleware('permission:edit_coupons');
        Route::get('/delete/{id}', 'OfferController@destroy')->name('admin.offers.delete')->middleware('permission:delete_coupons');
        Route::get('/mostReserved', 'OfferController@mostReserved')->name('admin.offers.mostreserved')->middleware('permission:show_coupons');

        //المستفدين من العرض
        Route::get('/discount/beneficiaries/{couponId}', 'OfferController@beneficiaries')->name('admin.offers.beneficiaries');

        Route::group(['prefix' => 'filters'], function () {
            Route::get('/', 'OfferController@filters')->name('admin.offers.filters');
            Route::get('/create', 'OfferController@addFilter')->name('admin.offers.filters.create');
            Route::post('/store', 'OfferController@storeFilters')->name('admin.offers.filters.store');
            Route::get('/edit/{id}', 'OfferController@editFilter')->name('admin.offers.filters.edit');
            Route::post('/update/{id}', 'OfferController@updateFilter')->name('admin.offers.filters.update');
            Route::get('/delete/{id}', 'OfferController@deleteFilter')->name('admin.offers.filters.delete');
        });

    });
    //  End Offers Routes
    ######################################################################################

    // Doctor Nickname
    Route::group(['prefix' => 'nickname'], function () {
        Route::get('/data', 'NicknameController@getDataTable')->name('admin.nickname.data');
        Route::get('/', 'NicknameController@index')->name('admin.nickname')->middleware('permission:show_titles');
        Route::get('/add', 'NicknameController@add')->name('admin.nickname.add')->middleware('permission:add_titles');
        Route::post('/store', 'NicknameController@store')->name('admin.nickname.store')->middleware('permission:add_titles');
        Route::get('/edit/{id}', 'NicknameController@edit')->name('admin.nickname.edit')->middleware('permission:edit_titles');
        Route::put('/update/{id}', 'NicknameController@update')->name('admin.nickname.update')->middleware('permission:edit_titles');
        Route::get('/delete/{id}', 'NicknameController@destroy')->name('admin.nickname.delete')->middleware('permission:delete_titles');
    });

    // Provider Types
    Route::group(['prefix' => 'types', 'middleware' => 'permission:show_providers_types'], function () {
        Route::get('/data', 'ProviderTypesController@getDataTable')->name('admin.types.data');
        Route::get('/', 'ProviderTypesController@index')->name('admin.types');
        Route::get('/add', 'ProviderTypesController@add')->name('admin.types.add');
        Route::post('/store', 'ProviderTypesController@store')->name('admin.types.store');
        Route::get('/edit/{id}', 'ProviderTypesController@edit')->name('admin.types.edit');
        Route::put('/update/{id}', 'ProviderTypesController@update')->name('admin.types.update');
        Route::get('/delete/{id}', 'ProviderTypesController@destroy')->name('admin.types.delete');
    });

    // City
    Route::group(['prefix' => 'city'], function () {
        Route::get('/data', 'CityController@getDataTable')->name('admin.city.data');
        Route::get('/', 'CityController@index')->name('admin.city')->middleware('permission:show_cities');
        Route::get('/add', 'CityController@add')->name('admin.city.add')->middleware('permission:add_cities');
        Route::post('/store', 'CityController@store')->name('admin.city.store')->middleware('permission:add_cities');
        Route::get('/edit/{id}', 'CityController@edit')->name('admin.city.edit')->middleware('permission:edit_cities');
        Route::put('/update/{id}', 'CityController@update')->name('admin.city.update')->middleware('permission:edit_cities');
        Route::get('/delete/{id}', 'CityController@destroy')->name('admin.city.delete')->middleware('permission:delete_cities');
    });
    // District
    Route::group(['prefix' => 'district'], function () {
        Route::get('/data', 'DistrictController@getDataTable')->name('admin.district.data');
        Route::get('/', 'DistrictController@index')->name('admin.district')->middleware('permission:show_districts');
        Route::get('/add', 'DistrictController@add')->name('admin.district.add')->middleware('permission:add_districts');
        Route::post('/store', 'DistrictController@store')->name('admin.district.store')->middleware('permission:add_districts');
        Route::get('/edit/{id}', 'DistrictController@edit')->name('admin.district.edit')->middleware('permission:edit_districts');
        Route::put('/update/{id}', 'DistrictController@update')->name('admin.district.update')->middleware('permission:edit_districts');
        Route::get('/delete/{id}', 'DistrictController@destroy')->name('admin.district.delete')->middleware('permission:delete_districts');
    });
    // Nationality
    Route::group(['prefix' => 'nationality'], function () {
        Route::get('/data', 'NationalityController@getDataTable')->name('admin.nationality.data');
        Route::get('/', 'NationalityController@index')->name('admin.nationality')->middleware('permission:show_nationalities');
        Route::get('/add', 'NationalityController@add')->name('admin.nationality.add')->middleware('permission:add_nationalities');
        Route::post('/store', 'NationalityController@store')->name('admin.nationality.store')->middleware('permission:add_nationalities');
        Route::get('/edit/{id}', 'NationalityController@edit')->name('admin.nationality.edit')->middleware('permission:edit_nationalities');
        Route::put('/update/{id}', 'NationalityController@update')->name('admin.nationality.update')->middleware('permission:edit_nationalities');
        Route::get('/delete/{id}', 'NationalityController@destroy')->name('admin.nationality.delete')->middleware('permission:delete_nationalities');
    });

    // brands
    Route::group(['prefix' => 'brands', 'middleware' => ['permission:show_brands']], function () {
        Route::get('/data', 'BrandsController@getDataTable')->name('admin.brands.data');
        Route::get('/', 'BrandsController@index')->name('admin.brands');
        Route::get('/add', 'BrandsController@add')->name('admin.brands.add');
        Route::post('/store', 'BrandsController@store')->name('admin.brands.store');
        Route::get('/edit/{id}', 'BrandsController@edit')->name('admin.brands.edit');
        Route::put('/update/{id}', 'BrandsController@update')->name('admin.brands.update');
        Route::get('/delete/{id}', 'BrandsController@destroy')->name('admin.brands.delete');
    });

    // PromoCode
    Route::group(['prefix' => 'promoCode'], function () {
        Route::get('/data', 'PromoCodeController@getDataTable')->name('admin.promoCode.data');
        Route::get('/getDataTablePromoCodeBranches/{promoId}', 'PromoCodeController@getDataTablePromoCodeBranches')->name('admin.promoCode.databranch');
        Route::get('/getDataTablePromoCodeDoctors/{promoId}', 'PromoCodeController@getDataTablePromoCodeDoctors')->name('admin.promoCode.datadoctor');
        Route::get('/', 'PromoCodeController@index')->name('admin.promoCode')->middleware('permission:show_coupons');
        Route::get('/show/{id}', 'PromoCodeController@view')->name('admin.promoCode.view')->middleware('permission:show_coupons');
        Route::get('/add', 'PromoCodeController@add')->name('admin.promoCode.add')->middleware('permission:add_coupons');
        Route::post('/store', 'PromoCodeController@store')->name('admin.promoCode.store')->middleware('permission:add_coupons');
        Route::post('/getprovider/branches', 'PromoCodeController@getProviderBranches')->name('admin.promoCode.providerbranches');
        Route::post('/getbranch/doctors', 'PromoCodeController@getBranchDoctors')->name('admin.promoCode.brancheDoctors');
        Route::get('/branches/{id}', 'PromoCodeController@branches')->name('admin.promoCode.branches');
        Route::get('/doctors/{id}', 'PromoCodeController@doctors')->name('admin.promoCode.doctors');
        Route::get('/edit/{id}', 'PromoCodeController@edit')->name('admin.promoCode.edit')->middleware('permission:edit_coupons');
        Route::put('/update/{id}', 'PromoCodeController@update')->name('admin.promoCode.update')->middleware('permission:edit_coupons');
        Route::get('/delete/{id}', 'PromoCodeController@destroy')->name('admin.promoCode.delete')->middleware('permission:delete_coupons');
        Route::get('/mostReserved', 'PromoCodeController@mostReserved')->name('admin.promoCode.mostreserved')->middleware('permission:show_coupons');

        //المستفدين من العرض
        Route::get('/discount/beneficiaries/{couponId}', 'PromoCodeController@beneficiaries')->name('admin.promoCode.beneficiaries');

        Route::group(['prefix' => 'filters'], function () {
            Route::get('/', 'PromoCodeController@filters')->name('admin.promoCode.filters');
            Route::get('/create', 'PromoCodeController@addFilter')->name('admin.promoCode.filters.create');
            Route::post('/store', 'PromoCodeController@storeFilters')->name('admin.promoCode.filters.store');
            Route::get('/edit/{id}', 'PromoCodeController@editFilter')->name('admin.promoCode.filters.edit');
            Route::post('/update/{id}', 'PromoCodeController@updateFilter')->name('admin.promoCode.filters.update');
            Route::get('/delete/{id}', 'PromoCodeController@deleteFilter')->name('admin.promoCode.filters.delete');
        });

    });
    // Admin Profile
    Route::group(['prefix' => 'data'], function () {
        Route::get('/agreement', 'AdminController@getAgreement')->name('admin.data.agreement')->middleware('permission:show_content');
        Route::get('/agreement/edit', 'AdminController@editAgreement')->name('admin.data.agreement.edit')->middleware('permission:edit_content');
        Route::put('/agreement/update', 'AdminController@updateAgreement')->name('admin.data.agreement.update')->middleware('permission:edit_content');
        Route::get('/information', 'AdminController@getInformation')->name('admin.data.information')->middleware('permission:show_content');
        Route::get('/information/edit', 'AdminController@editInformation')->name('admin.data.information.edit')->middleware('permission:edit_content');
        Route::put('/information/update', 'AdminController@updateInformation')->name('admin.data.information.update')->middleware('permission:edit_content');
        Route::get('/providers', 'AdminController@getProviders')->name('admin.data.providers');
        Route::get('/coupons', 'AdminController@getCouponsBalances')->name('admin.data.coupon.balance');
        Route::get('/{id}/branches', 'AdminController@getbranches')->name('admin.data.branches');
        Route::get('/provider/{id}/balance/edit', 'AdminController@editProviderBalance')->name('admin.data.provider.balance.edit');
        Route::put('/provider/{id}/balance/update', 'AdminController@updateProviderBalance')->name('admin.data.provider.balance.update');
        Route::get('/provider/{id}/branches/balance', 'AdminController@showProviderBranchesBalance')->name('admin.data.provider.branches.balance');

    });
    // Custom Page
    Route::group(['prefix' => 'customPage'], function () {
        Route::get('/data', 'CustomPageController@getDataTable')->name('admin.customPage.data');
        Route::get('/', 'CustomPageController@index')->name('admin.customPage')->middleware('permission:show_pages');
        Route::get('/add', 'CustomPageController@add')->name('admin.customPage.add')->middleware('permission:add_pages');
        Route::post('/store', 'CustomPageController@store')->name('admin.customPage.store')->middleware('permission:add_pages');
        Route::get('/edit/{id}', 'CustomPageController@edit')->name('admin.customPage.edit')->middleware('permission:edit_pages');
        Route::put('/update/{id}', 'CustomPageController@update')->name('admin.customPage.update')->middleware('permission:edit_pages');
        Route::get('/delete/{id}', 'CustomPageController@destroy')->name('admin.customPage.delete')->middleware('permission:delete_pages');
        Route::get('/status/{id}/{status}', 'CustomPageController@changeStatus')->name('admin.customPage.status')->middleware('permission:edit_pages');
    });
    // Branch
    Route::group(['prefix' => 'branch'], function () {
        Route::get('/data', 'BranchController@getDataTable')->name('admin.branch.data');
        Route::get('/', 'BranchController@index')->name('admin.branch')->middleware('permission:show_branches');
        Route::get('/edit/{id}', 'BranchController@edit')->name('admin.branch.edit')->middleware('permission:edit_branches');
        Route::get('/create', 'BranchController@create')->name('admin.branch.add')->middleware('permission:add_branches');
        Route::post('/store', 'BranchController@store')->name('admin.branch.store')->middleware('permission:add_branches');
        Route::put('/update/{id}', 'BranchController@update')->name('admin.branch.update')->middleware('permission:edit_branches');
        Route::get('/delete/{id}', 'BranchController@destroy')->name('admin.branch.delete')->middleware('permission:delete_branches');
        Route::get('/status/{id}/{status}', 'BranchController@changeStatus')->name('admin.branch.status');
        Route::get('/show/{id}', 'BranchController@view')->name('admin.branch.view')->middleware('permission:show_branches');
        Route::post('/addTOFeatured', 'BranchController@addProviderTOFeatured')->name('admin.branch.addTOFeatured')->middleware('permission:show_branches');
        Route::post('/removeFromFeatured', 'BranchController@removeProviderFromFeatured')->name('admin.branch.removeFromFeatured')->middleware('permission:show_branches');
    });
    // Reservation
    Route::group(['prefix' => 'reservation'], function () {
        Route::get('/data/{status}', 'ReservationController@getDataTable')->name('admin.reservation.data');
        Route::get('/', 'ReservationController@index')->name('admin.reservation')->middleware('permission:show_reservations');
        Route::get('/delete/{id}', 'ReservationController@destroy')->name('admin.reservation.delete')->middleware('permission:delete_reservations');
        Route::get('/show/{id}', 'ReservationController@view')->name('admin.reservation.view')->middleware('permission:show_reservations');
        Route::get('/status/{id}/{status}/{reason?}', 'ReservationController@changeStatus')->name('admin.reservation.status')->middleware('permission:edit_reservations');
        Route::get('/rejection', 'ReservationController@rejectReservation')->name('admin.reservation.rejectiond')->middleware('permission:edit_reservations');
        Route::get('/update/{id}', 'ReservationController@editReservationDateTime')->name('admin.reservation.update')->middleware('permission:edit_reservations');
        Route::post('/update/time', 'ReservationController@UpdateReservationDateTime')->name('admin.reservation.datetime.update')->middleware('permission:edit_reservations');

    });
    // User Messages
    Route::group(['prefix' => 'user'], function () {
        Route::group(['prefix' => 'message'], function () {
            Route::get('/data', 'UserMessageController@getDataTable')->name('admin.user.message.data');
            Route::get('/', 'UserMessageController@index')->name('admin.user.message')->middleware('permission:show_user_messages');
            Route::get('/delete/{id}', 'UserMessageController@destroy')->name('admin.user.message.delete')->middleware('permission:delete_user_messages');
            Route::get('/solved/{id}', 'ProviderMessageController@solvedMessage')->name('admin.user.message.solved');
            Route::get('/show/{id}', 'UserMessageController@view')->name('admin.user.message.view')->middleware('permission:show_user_messages');
            Route::post('/reply', 'UserMessageController@reply')->name('admin.user.message.reply')->middleware('permission:add_user_messages');
        });
    });

    // Provider Messages
    Route::group(['prefix' => 'provider'], function () {
        Route::group(['prefix' => 'message'], function () {
            Route::get('/data', 'ProviderMessageController@getDataTable')->name('admin.provider.message.data');

            Route::get('/', 'ProviderMessageController@index')->name('admin.provider.message')->middleware('permission:show_provider_messages');
            Route::get('/delete/{id}', 'ProviderMessageController@destroy')->name('admin.provider.message.delete')->middleware('permission:delete_provider_messages');
            Route::get('/solved/{id}', 'ProviderMessageController@solvedMessage')->name('admin.provider.message.solved');
            Route::get('/show/{id}', 'ProviderMessageController@view')->name('admin.provider.message.view')->middleware('permission:show_provider_messages');
            Route::post('/reply', 'ProviderMessageController@reply')->name('admin.provider.message.reply')->middleware('permission:add_provider_messages');
        });
    });
    // User
    Route::group(['prefix' => 'users'], function () {
        Route::get('/data', 'UserController@getDataTable')->name('admin.user.data');
        Route::get('/', 'UserController@index')->name('admin.user')->middleware('permission:show_users');
        Route::get('/delete/{id}', 'UserController@destroy')->name('admin.user.delete')->middleware('permission:delete_users');
        Route::get('/viewdelete/{id}', 'UserController@viewdestroy')->name('admin.user.viewdelete')->middleware('permission:show_users');
        Route::get('/show/{id}', 'UserController@view')->name('admin.user.view')->middleware('permission:show_users');
        Route::get('/status/{id}/{status}', 'UserController@changeStatus')->name('admin.user.status')->middleware('permission:edit_users');
    });

    // admins
    Route::group(['prefix' => 'admins'], function () {
        Route::get('/data', 'UserController@getAdminsDataTable')->name('admin.admins.data');
        Route::get('/', 'UserController@showAdmins')->name('admin.admins')->middleware('permission:show_admins');
        Route::get('/delete/{id}', 'UserController@destroyAdmin')->name('admin.admin.delete')->middleware('permission:delete_admins');
        Route::get('/edit/{id}', 'UserController@editAdmin')->name('admin.admin.edit')->middleware('permission:edit_admins');
        Route::put('/update/{id}', 'UserController@updateAdmin')->name('admin.admin.update')->middleware('permission:edit_admins');
        Route::get('/add', 'UserController@addAdmin')->name('admin.admins.add')->middleware('permission:add_admins');
        Route::post('/store', 'UserController@storeAdmin')->name('admin.admins.store')->middleware('permission:add_admins');
        Route::get('/status/{id}/{status}', 'UserController@changeAdminStatus')->name('admin.admin.status')->middleware('permission:edit_admins');
    });


    Route::group(['prefix' => 'notifications', 'middleware' => 'permission:notifications'], function () {

        Route::get('/list/{type}', "NotificationsController@index")->name('admin.notifications');
        Route::get('/data/{type}', "NotificationsController@getData")->name('admin.notifications.data');
        Route::get('/recdata/{notifyId}/{type}', "NotificationsController@getRecieversData")->name('admin.receivers.data');
        Route::get('/recievers/{notifyId}/{type}', "NotificationsController@getRecievers")->name('admin.notifications.recievers');
        Route::get("/add/{type}", "NotificationsController@get_add")->name('admin.notifications.add');
        Route::post("/store", "NotificationsController@post_add")->name('admin.notifications.post');
        Route::get('/delete/{notifyId}', "NotificationsController@delete")->name('admin.notifications.delete');
    });

    Route::group(['prefix' => 'comments', 'middleware' => 'permission:show_comments'], function () {
        Route::get('/', "CommentsController@index")->name('admin.comments');
        Route::get('/data', "CommentsController@getData")->name('admin.comments.data');
        Route::get('/delete/{id}', "CommentsController@delete")->name('admin.comments.delete');
        Route::post('/update', "CommentsController@update")->name('admin.comments.update');
    });

    Route::group(['prefix' => 'reports', 'middleware' => 'permission:show_reports'], function () {
        Route::get('/', "CommentsController@reports")->name('admin.reports');
        Route::get('/data', "CommentsController@getreportsData")->name('admin.reports.data');
        Route::get('/delete/{id}', "CommentsController@deletereport")->name('admin.reports.delete');
    });

    // Reservation
    Route::group(['prefix' => 'provider'], function () {
        Route::get('/reservation/data/{provider_id}', 'ReservationController@getProviderReservationsDataTable')->name('admin.provider.reservations.data');
        Route::get('/reservations/{provider_id}', 'ReservationController@providerReservations')->name('admin.provider.reservations');
    });

    // settings
    Route::group(['prefix' => 'settings', 'middleware' => 'permission:show_settings'], function () {
        Route::get('/', 'GeneralController@getContents')->name('admin.settings.index');
        Route::put('/', 'GeneralController@updateContents')->name('admin.settings.update');

    });

    Route::group(['middleware' => 'permission:share_application_setting'], function () {
        Route::get('/sharing', 'GeneralController@sharingSettings')->name('admin.sharing');
        Route::post('update/sharing', 'GeneralController@updateSharingSettings')->name('admin.sharing.update');
    });

    // Development Company
    Route::group(['prefix' => 'development', 'middleware' => 'permission:show_development'], function () {
        Route::get('/', 'GeneralController@getDevelopmentContents')->name('admin.development.index');
        Route::put('/', 'GeneralController@updateDevelopmentContents')->name('admin.development.update');

    });

    // subscription
    Route::group(['prefix' => 'subscriptions', 'middleware' => 'permission:show_subscriptions'], function () {
        Route::get('/data', 'GeneralController@getSubscribtionsData')->name('admin.subscriptions.data');
        Route::get('/', 'GeneralController@getSubscriptions')->name('admin.subscriptions.index');
        Route::get('/delete/{id}', 'GeneralController@deleteSubscription')->name('admin.subscriptions.delete');
    });

    // reservation bills
    Route::group(['prefix' => 'bills', 'middleware' => 'permission:show_bills'], function () {
        Route::get('/data', 'BillController@getDataTable')->name('admin.bills.data');
        Route::get('/', 'BillController@index')->name('admin.bills.index');
        Route::get('/delete/{id}', 'BillController@delete')->name('admin.bills.delete');
        Route::get('/show/{id}', 'BillController@show')->name('admin.bills.show');
        Route::post('/point/add', 'BillController@addPointToUser')->name('admin.bills.addPoints');  //add specific point to user
    });

    Route::group(['prefix' => 'reasons', 'middleware' => 'permission:show_cancellation_reasons'], function () {
        Route::get('/data', 'GeneralController@getReasonsData')->name('admin.reasons.data');
        Route::get('/', 'GeneralController@getReasons')->name('admin.reasons.index');
        Route::get('/add', 'GeneralController@addReason')->name('admin.reasons.add');
        Route::post('/store', 'GeneralController@storeReason')->name('admin.reasons.store');
        Route::get('/edit/{id}', 'GeneralController@editReason')->name('admin.reasons.edit');
        Route::put('/update/{id}', 'GeneralController@updateReason')->name('admin.reasons.update');
        Route::get('/delete/{id}', 'GeneralController@destroyReason')->name('admin.reasons.delete');
    });

    Route::group(['prefix' => 'lotteries'], function () {
        Route::get('/data', 'LotteryController@getDataTable')->name('admin.lotteriesBranches');
        Route::get('/branches', 'LotteryController@lotteryBranches')->name('admin.lotteriesBranches.index')->middleware('permission:show_lotteries_branches');
        Route::post('/add', 'LotteryController@addLotteryBranch')->name('admin.lotteriesBranches.add');
        Route::post('/remove', 'LotteryController@removeLotteryBranch')->name('admin.lotteriesBranches.remove');
        Route::post('/gift/add', 'LotteryController@addGiftToBranch')->name('admin.lotteriesBranches.addGiftToBranch');
        Route::get('/gift/delete/{GiftId}', 'LotteryController@deleteGiftTo')->name('admin.lotteriesBranches.deleteGift');
        Route::get('/gift/show/{branchId}', 'LotteryController@showBranchGifts')->name('admin.lotteriesBranches.showBranchGifts');
        Route::post('/loadGifts', 'LotteryController@loadBranchGifts')->name('admin.lotteries.loadGifts');
        Route::post('/load/users', 'LotteryController@loadGiftUsers')->name('admin.lotteries.loadUsers');
        Route::get('/users', 'LotteryController@users')->name('admin.lotteries.users')->middleware('permission:show_lotteries_users');
    });

    Route::get("mc33/search", 'GeneralController@search')->name('admin.search');
});

Route::group(['prefix' => 'mc33', 'middleware' => ['web', 'auth', 'ChangeLanguage']], function () {
    Route::get('logout', 'Auth\LoginController@logout')->name('Logout');
});


Route::get('map', 'Site\HomeController@getProvidersOnMap')->name('map');


Route::get('testd', function () {


    $providers = Provider::whereHas('subscriptions')->with('subscriptions')->whereHas('doctors')
        ->where('provider_id', '!=', null)->get();

    if ($providers->count() > 0) {
        foreach ($providers as $provider) {

            if ($provider->subscriptions->created_at)
                //$provider->where(DB::raw('DATE(created_at)'), '>=', date('Y-m-d H:i:s', strtotime('-'.DB::raw('duration').' day')));
                $provider->favourite = count($provider->favourites) > 0 ? 1 : 0;
            $provider->distance = (string)number_format($provider->distance * 1.609344, 2);
            $provider->has_home_services = $provider->homeServices()->count() > 0 ? 1 : 0;
            $provider->has_clinic_services = $provider->clinicServices()->count() > 0 ? 1 : 0;
            unset($provider->favourites);

            // branches that its featured time passes must not return
            $to = \Carbon\Carbon::now('Asia/Riyadh');
            $from = \Carbon\Carbon::createFromFormat('Y-m-d H:s:i', $provider->subscriptions->created_at);
            $diff_in_days = $to->diffInDays($from);
            if ($diff_in_days > $provider->subscriptions->duration) {
                unset($provider);
            }
        }

        $providers = $this->addProviderNameToCollectionResults($providers);
        return $this->returnData('featured_providers', $providers);
    }


});
