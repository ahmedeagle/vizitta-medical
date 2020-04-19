<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\CommentReport;
use App\Models\Doctor;
use App\Models\Mix;
use App\Models\Provider;
use App\Models\Reason;
use App\Models\ReportingType;
use App\Models\Reservation;
use App\Models\Sensitivity;
use App\Models\Specification;
use App\Models\Subscribtion;
use App\Traits\GlobalTrait;
use App\Traits\SearchTrait;
use Freshbitsweb\Laratables\Laratables;
use http\Env\Response;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Constraint\Count;
use Validator;
use Illuminate\Pagination\LengthAwarePaginator;
use DB;

class GlobalController extends Controller
{
    use GlobalTrait, SearchTrait;

    public function logout()
    {
        Auth::logout();
        return $this->returnData('message', "Logout Successfully");
    }


    public
    function getAgreement()
    {
        try {
            $agreement = $this->getAgreementText();
            if ($agreement)
                return $this->returnData('agreement', $agreement);

            return $this->returnError('E001', trans('messages.There is no agreement found'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    public
    function getReservationNotes()
    {

        try {
            $notes = $this->getNotesText();
            if ($notes)
                return $this->returnData('notes', $notes);

            return $this->returnError('E001', trans('messages.There is no reservation notes found'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    public
    function getReservationRules()
    {
        try {
            $rules = $this->getReservationRulesText();
            if ($rules)
                return $this->returnData('rules', $rules);

            return $this->returnError('E001', trans('messages.There is no rules found'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public
    function getProviderRegisterationRules()
    {
        try {
            $rules = $this->getProviderReservationRulesText();
            if ($rules)
                return $this->returnData('rules', $rules);

            return $this->returnError('E001', trans('messages.There is no rules found'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public
    function getPaymentMethods()
    {
        try {
            $methods = $this->getAllPaymentMethods();
            if ($methods && count($methods) > 0)
                return $this->returnData('methods', $methods);

            return $this->returnError('E001', trans('messages.There is no payment methods found'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public
    function getSpecifications(Request $request)
    {
        try {
            if (isset($request->provider_id)) {
                $provider = $this->checkProvider($request->provider_id);
                if ($provider == null)
                    return $this->returnError('D000', trans('messages.There is no provider with this id'));
            }
            $specifications = $this->getAllSpecifications($request->provider_id);
            if ($specifications && count($specifications) > 0)
                return $this->returnData('specifications', $specifications);

            return $this->returnError('E001', trans('messages.There is no specifications found'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


 public
    function getSpecificationsV2(Request $request)
    {
        try {
            if (isset($request->provider_id)) {
                $provider = $this->checkProvider($request->provider_id);
                if ($provider == null)
                    return $this->returnError('D000', trans('messages.There is no provider with this id'));
            }
            $specifications = $this->getAllSpecificationsV2($request->provider_id);
            if ($specifications && count($specifications) > 0)
                return $this->returnData('specifications', $specifications);

            return $this->returnError('E001', trans('messages.There is no specifications found'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public
    function getCouponsFilters()
    {
        try {
            $filters = $this->getActiveFilters();
                return $this->returnData('filters', $filters);
            // return $this->returnError('E001', trans('messages.There is no filters found'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public
    function getCouponsCategories(Request $request)
    {
        try {
            /* $validator = Validator::make($request->all(), [
                 "timer" => "required|in:0,1",
             ]);
             if ($validator->fails()) {
                 $code = $this->returnCodeAccordingToInput($validator);
                 return $this->returnValidationError($code, $validator);
             }*/

            /*  if (isset($request->provider_id)) {
                  $provider = $this->checkProvider($request->provider_id);
                  if ($provider == null)
                      return $this->returnError('D000', trans('messages.There is no provider with this id'));
              }*/
            $categories = $this->getAllPromoCategories(/*$request->timer*/);
            return $this->returnData('categories', $categories);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public
    function getCouponsCategoriesV2(Request $request)
    {
        try {
            /*  $validator = Validator::make($request->all(), [
                  "timer" => "required|in:0,1",
              ]);
              if ($validator->fails()) {
                  $code = $this->returnCodeAccordingToInput($validator);
                  return $this->returnValidationError($code, $validator);
              }*/

            /*  if (isset($request->provider_id)) {
                  $provider = $this->checkProvider($request->provider_id);
                  if ($provider == null)
                      return $this->returnError('D000', trans('messages.There is no provider with this id'));
              }*/

            $settings = Mix::first();
            $price_less = "100"; // default value
            if ($settings) {
                if ($settings->price_less !== "")
                    $price_less = $settings->price_less;
            }

            $categories = $this->getPromoCategoriesV2();
            $timerCategories = $this->getTimerPromoCategoriesV2();

            if(isset($timerCategories) &&  $timerCategories -> count() > 0)
            {
                $timerCategories->each(function ($timerCategory) {

                   // $categoryAllowTimeInMinutes = ($timerCategory -> hours * 60) + ($timerCategory -> minutes) + ()
                     return $timerCategory;
                });
            }

            $obj = new \stdClass();
            $obj->price_less = $price_less;
            $obj->categories = $categories;
            $obj->timerCategories = $timerCategories;

            return $this->returnData('data', $obj);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    } public

    function getOfferSubcategories(Request $request)
    {
        try {
              $validator = Validator::make($request->all(), [
                  "category_id" => "required|exists:offers_categories,id",
              ]);
              if ($validator->fails()) {
                  $code = $this->returnCodeAccordingToInput($validator);
                  return $this->returnValidationError($code, $validator);
              }

            $subCategories = $this->getSubCategories($request -> category_id);

            return $this->returnData('data', $subCategories);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    /* public
    function search(Request $request)
    {

        $user = null;
        $validation = $this->checkValidationFields($request->specification_id, $request->city_id, $request->nickname_id, $request->type_id,
            $request->district_id, $request->insurance_company_id);

        if ($this->checkToken($request))
            $user = $this->auth('user-api');

        if (isset($request->specification_id) && $request->specification_id != 0) {
            if ($validation->specification_found == 0)
                return $this->returnError('D000', trans('messages.There is no specification with this id'));
        }

        if (isset($request->ciy_id) && $request->ciy_id != 0) {
            if ($validation->city_found == 0)
                return $this->returnError('D000', trans('messages.There are no city with this id'));
        }

        if (isset($request->nickname_id) && $request->nickname_id != 0) {
            if ($validation->nickname_found == 0)
                return $this->returnError('D000', trans("messages.There is no nickname with this id"));
        }

        if (is_array($request->type_id) && count($request->type_id) > 0) {
            if (count($request->type_id) != $validation->type_found)
                return $this->returnError('D000', trans("messages.There is no type with this id"));
        }

        if (isset($request->district_id) && $request->district_id != 0) {
            if ($validation->district_found == 0)
                return $this->returnError('D000', trans('messages.There are no district with this id'));
        }

        if (isset($request->insurance_company_id) && $request->insurance_company_id != 0) {
            if ($validation->insurance_company_found == 0)
                return $this->returnError('D000', trans('messages.There is no insurance company with this id'));
        }

        if (isset($request->nearest_date) && $request->nearest_date == 1) {
            if (isset($request->specification_id) && $request->specification_id != 0) {
                $specification_id = $request->specification_id;
                $providers = $this->searchDateSortedResult($user ? $user->id : null, $request, $specification_id);
                $providers1 = $providers[0];
                if (count($providers1->toArray()) > 0) {
                    $total_count = $providers1->total();
                    $providers1 = json_decode($providers1->toJson());
                    $providersJson = new \stdClass();
                    $providersJson->current_page = $providers1->current_page;
                    $providersJson->total_pages = $providers1->last_page;
                    $providersJson->total_count = $total_count;
                    $providersJson->data = $providers[1];
                    return $this->returnData('providers', $providersJson);
                }
            } else
                return $this->returnError('E001', trans('messages.you must choose doctor specification'));
        } else {
            $result = $this->searchResult($user ? $user->id : null, $request);

            if (count($result) > 0) {
                $total_count = $result->total();
                $result->getCollection()->each(function ($provider) {
                    $provider->favourite = count($provider->favourites) > 0 ? 1 : 0;
                    $provider->distance = (string)number_format($provider->distance * 1.609344, 2);
                    unset($provider->favourites);
                    return $provider;
                });


                $providers = json_decode($result->toJson());

                if (isset($providers->data) && count($providers->data) > 0) {

                    foreach ($providers->data as $key => $provider) {
                        if ($provider->provider_id == null) {
                            $provider->provider = new \stdClass();

                        } else {

                            $provider->provider = Provider::find($provider->provider_id, ['id', 'name_' . app()->getLocale() . ' as name']);

                        }
                    }

                }

                $providersJson = new \stdClass();
                $providersJson->current_page = $providers->current_page;
                $providersJson->total_pages = $providers->last_page;
                $providersJson->total_count = $total_count;
                $providersJson->data = $providers->data;
                return $this->returnData('providers', $providersJson);
            }
            return $this->returnError('E001', trans('messages.No data founded'));
        }


    }*/


    public
    function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "rate" => "boolean",   // provider rate
            "type_id" => "array",   // provider type clinic - doctor - hospital  - ....
        ]);

        $user = null;
        $validation = $this->checkValidationFields($request->specification_id, $request->city_id, $request->nickname_id, $request->type_id,
            $request->district_id, $request->insurance_company_id);

        //  if ($this->checkToken($request))
        $user = $this->auth('user-api');
        if (isset($request->specification_id) && $request->specification_id != 0) {
            if ($validation->specification_found == 0)
                return $this->returnError('D000', trans('messages.There is no specification with this id'));
        }

        if (isset($request->ciy_id) && $request->ciy_id != 0) {
            if ($validation->city_found == 0)
                return $this->returnError('D000', trans('messages.There are no city with this id'));
        }

        if (isset($request->nickname_id) && $request->nickname_id != 0) {
            if ($validation->nickname_found == 0)
                return $this->returnError('D000', trans("messages.There is no nickname with this id"));
        }

        if (is_array($request->type_id) && count($request->type_id) > 0) {
            if (count($request->type_id) != $validation->type_found)
                return $this->returnError('D000', trans("messages.There is no type with this id"));
        }

        if (isset($request->district_id) && $request->district_id != 0) {
            if ($validation->district_found == 0)
                return $this->returnError('D000', trans('messages.There are no district with this id'));
        }

        if (isset($request->insurance_company_id) && $request->insurance_company_id != 0) {
            if ($validation->insurance_company_found == 0)
                return $this->returnError('D000', trans('messages.There is no insurance company with this id'));
        }

        if (isset($request->nearest_date) && $request->nearest_date != 0) {
            if (!isset($request->specification_id) && $request->specification_id == 0) {
                return $this->returnError('D000', trans('messages.you must choose doctor specification'));
            }
        }

        if (isset($request->nearest_date) && $request->nearest_date != 0) {
            $resultArray = $this->searchDateSortedResult($user ? $user->id : null, $request);
            $providers = $resultArray[0];  //all result doctor with its providers
            if (count($providers->toArray()) > 0) {
                $total_count = $providers->total();
                $providersData = json_decode($providers->toJson());
                $providersJson = new \stdClass();
                $providersJson->current_page = $providersData->current_page;
                $providersJson->total_pages = $providersData->last_page;
                $providersJson->total_count = $total_count;
                $providersJson->data = $resultArray[1];  // the providers of doctors
                if (!empty($providersJson->data) && count($providersJson->data) > 0) {
                    $providersJson = $this->addProviderToresults($providersJson);
                }
                return $this->returnData('providers', $providersJson);
            }
        } else {
            $results = $this->searchResult($user ? $user->id : null, $request);
            if (count($results->toArray()) > 0) {
                $total_count = $results->total();
                $results->getCollection()->each(function ($result) use ($request) {
                    $result->favourite = count($result->favourites) > 0 ? 1 : 0;
                    $result->distance = (string)number_format($result->distance * 1.609344, 2);
                    /* //nearest  availble time date
                     if ($result->doctor == '1') {
                         $doctor = Doctor::find($result->id);
                         if ($doctor) {
                             //   $result->times = $doctor->times()->get();
                             $result->available_time = $doctor->nearest_available_time;
                         } else
                             $result->times = [];
                     }*/
                    unset($result->favourites);
                    return $result;
                });

                /* //order by nearest available date
                 if (isset($request->nearest_date) && $request->nearest_date != 0) {
                     $dataResults = $results->sortBy(function ($a) use ($request) {
                         return strtotime($a->available_time);
                     })->values()->all();*/

                /*    $newDataResults = [];
                    foreach ($dataResults as $item) {
                        if ($item->specification_id == $request -> specification_id) {
                            array_push($newDataResults, $item);
                        }
                    }*/
            }

            $results = json_decode($results->toJson());
            $resultsJson = new \stdClass();
            $resultsJson->current_page = $results->current_page;
            $resultsJson->total_pages = $results->last_page;
            $resultsJson->total_count = $total_count;

            /*   if (isset($request->nearest_date) && $request->nearest_date != 0) {
                   $resultsJson->data = $dataResults;
               } else {
                   $resultsJson->data = $results->data;
               }*/

            $resultsJson->data = $results->data;
            if (!empty($resultsJson->data) && count($resultsJson->data) > 0) {
                $resultsJson = $this->addProviderToresults($resultsJson);
            }
            return $this->returnData('providers', $resultsJson);
        }

        return $this->returnError('E001', trans('messages.No data founded'));

    }

    protected
    function addProviderToresults($providersJson)
    {
        foreach ($providersJson->data as $key => $branch) {
            $provider = Provider::where('id', $branch->provider_id)
                ->select('id', 'name_' . app()->getLocale() . ' as name', 'logo')
                ->first();
            //set main provider  to branches results
            $branch->provider = $provider;

            /* if ($branch->doctor == "0") {
                 $provider = Provider::find($branch->provider_id);
                 $provider = $provider->select('name_' . app()->getLocale() . ' as name', 'logo')->first();
                 //set main provider  to branches results
                 $branch->provider = $provider;
                 $branch->specification_name = '';
             } else {
                 $specific = Specification::where('id', $branch->specification_id)->first();
                 if ($specific)
                     $branch->specification_name = $specific->{'name_' . app()->getLocale()};
                 else
                     $branch->specification_name = '';
             }*/
        }
        return $providersJson;
    }


    public
    function paginate($items, $perPage = 10, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }

    public
    function getAppData()
    {
        try {
            $info = $this->getAppInfo();
            return $this->returnData('information', $info);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public
    function getNationalities()
    {
        try {
            $nationalities = $this->getAllNationalities();
            if (count($nationalities) > 0)
                return $this->returnData('nationalities', $nationalities);

            return $this->returnError('E001', trans('messages.There are no nationalities found'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public
    function getNicknames(Request $request)
    {
        try {
            $nicknames = [];
            if (isset($request->provider_id)) {
                $provider = Provider::with(['doctors', 'doctors.nickname'])->find($request->provider_id);
                if ($provider == null)
                    return $this->returnError('D000', trans('messages.There is no provider with this id'));
                foreach ($provider->doctors as $doctor) {
                    if ($doctor->nickname) {
                        $nicknames[] = ['id' => $doctor->nickname->id, 'name' => $doctor->nickname->{'name_' . app()->getLocale()}];
                    }
                }
            } else
                $nicknames = $this->getDoctorNicknames(null);

            if (count($nicknames) > 0)
                return $this->returnData('nicknames', $nicknames);

            return $this->returnError('E001', trans('messages.There are no nicknames found'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public
    function getReportingTypes()
    {
        return $this->returnData('types', ReportingType::select('id', 'name_' . app()->getLocale() . ' as name')->get());
    }


    public
    function getCategories()
    {
        try {
            $categories = $this->getCategoriesFromDB();

            if (count($categories) > 0)
                return $this->returnData('categories', $categories);

            return $this->returnError('E001', trans('messages.There are no categories found'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    public
    function getBranches(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "provider_id" => "required|numeric",
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $provider = $this->checkProvider($request->provider_id);
            if ($provider == null)
                return $this->returnError('D000', trans('messages.There is no provider with this id'));

            $branches = $this->getProviderBranches($request->provider_id);
            if (count($branches) > 0)
                return $this->returnData('branches', $branches);

            return $this->returnError('E001', trans('messages.There are no branches found'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    public
    function settings(Request $request)
    {
        try {
            $settings = Mix::first();
            if ($settings) {
                $setting = Mix::select(
                    DB::raw('approve_message_' . $this->getCurrentLang() . ' as approve_message'),
                    DB::raw('title_' . $this->getCurrentLang() . ' as title'),
                    DB::raw('meta_keywords_' . $this->getCurrentLang() . ' as meta_keywords'),
                    DB::raw('meta_description_' . $this->getCurrentLang() . ' as meta_description'),
                    DB::raw('aboutApp_' . $this->getCurrentLang() . ' as aboutApp'),
                    DB::raw('app_text_' . $this->getCurrentLang() . ' as app_text'),
                    DB::raw('use1_' . $this->getCurrentLang() . ' as use1'),
                    DB::raw('use2_' . $this->getCurrentLang() . ' as use2'),
                    DB::raw('use3_' . $this->getCurrentLang() . ' as use3'),
                    DB::raw('address_' . $this->getCurrentLang() . ' as address'),
                    'email',
                    'mobile',
                    'facebook',
                    'twitter',
                    'instg',
                    'linkedIn',
                    'whatsApp',
                    'app_store',
                    'google_play',
                    'home_image1',
                    'home_image2'
                )->first();
                return $this->returnData('settings', $setting);
            }

            return $this->returnError('E001', trans('messages.There is no settings found'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }

    }

    public
    function getDevelopmentCompanyInfo(Request $request)
    {
        try {
            $settings = Mix::first();
            if ($settings) {
                $setting = Mix::select(
                    DB::raw('dev_company_' . $this->getCurrentLang() . ' as about_company'),
                    'dev_company_logo',
                    'dev_company_link'
                )->first();
                return $this->returnData('aboutCompany', $setting);
            }

            return $this->returnError('E001', trans('messages.There is no Data found'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }

    }

    protected
    function getCurrentLang()
    {
        return app()->getLocale();
    }


    public
    function subscriptions(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "email" => "required|email|max:225",
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $subscibed = Subscribtion::where('email', '=', $request->email)->first();
            if ($subscibed)
                return $this->returnError('D000', trans('messages.subscribed before'));

            else
                Subscribtion::create(['email' => $request->email]);
            return $this->returnSuccessMessage(__('messages.subscribtion Done'));

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public
    function getReasons()
    {
        try {
            $reasons = Reason::select('id', \Illuminate\Support\Facades\DB::raw('name_' . app()->getLocale() . ' as name'))->get();
            if ($reasons) {
                return $this->returnData('reasons', $reasons);
            }
            return $this->returnError('E001', trans('messages.No Reasons Availble Now'));

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    public function brands()
    {
        try {
            $brands = Brand::select('id', DB::raw("CONCAT('" . url('/') . "','/',photo) AS image"))->get();
            if (isset($brands) && $brands->count() > 0) {
                return $this->returnData('brands', $brands);
            }
            return $this->returnError('E001', trans('messages.No Brands Availble Now'));

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

}
