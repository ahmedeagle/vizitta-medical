<?php

namespace App\Traits;

use App\Models\Banner;
use App\Models\Category;
use App\Models\District;
use App\Models\Doctor;
use App\Models\Filter;
use App\Models\Manager;
use App\Models\Mbanner;
use App\Models\Message;
use App\Models\Mix;
use App\Models\City;
use App\Models\Nationality;
use App\Models\Nickname;
use App\Models\OfferCategory;
use App\Models\Token;
use App\Models\Payment;
use App\Models\PromoCodeCategory;
use App\Models\Provider;
use App\Models\ProviderType;
use App\Models\Reservation;
use App\Models\Specification;
use App\Models\User;
use App\Models\InsuranceCompany;
use App\Models\PaymentMethod;
use App\Models\UserToken;
use DB;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;


trait GlobalTrait
{


    public function checkUser($id)
    {
        return User::find($id);
    }

    public function checkDoctor($id)
    {
        return Doctor::find($id);
    }

    public function checkProvider($id)
    {
        return Provider::find($id);
    }

    public function getUserByToken($token)
    {
        return User::where('api_token', $token)->first();
    }

    public function getReservationByNumber($no)
    {
        return Reservation::where('reservation_no', $no)->first();
    }

    public function getProviderByToken($token)
    {
        return Provider::where('api_token', $token)->first();
    }

    public function getCurrentLang()
    {
        return app()->getLocale();
    }

    public function returnError($errNum, $msg)
    {
        return response()->json([
            'status' => false,
            'errNum' => $errNum,
            'msg' => $msg
        ]);
    }

    public function checkToken(Request $request)
    {
        try {
            $request->headers->set('Authorization', 'Bearer ' . $request->api_token);
            JWTAuth::parseToken()->authenticate();
            return true;
        } catch (\Exception $e) {
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function returnValidationError($code = "E001", $validator)
    {
        return $this->returnError($code, $validator->errors()->first());
    }

    public function returnSuccessMessage($msg = "", $errNum = "S000")
    {
        return ['status' => true, 'errNum' => $errNum, 'msg' => $msg];
    }

    public function returnData($key, $value, $msg = "")
    {
        return response()->json(['status' => true, 'errNum' => "S000", 'msg' => $msg, $key => $value]);
    }

    public function returnCodeAccordingToInput($validator)
    {
        $inputs = array_keys($validator->errors()->toArray());
        $code = $this->getErrorCode($inputs[0]);
        return $code;
    }

    public function getErrorCode($input)
    {
        if ($input == "name")
            return 'E0011';

        else if ($input == "password")
            return 'E002';

        else if ($input == "mobile")
            return 'E003';

        else if ($input == "id_number")
            return 'E004';

        else if ($input == "birth_date")
            return 'E005';

        else if ($input == "agreement")
            return 'E006';

        else if ($input == "email")
            return 'E007';

        else if ($input == "city_id")
            return 'E008';

        else if ($input == "insurance_company_id")
            return 'E009';

        else if ($input == "activation_code")
            return 'E010';

        else if ($input == "longitude")
            return 'E011';

        else if ($input == "latitude")
            return 'E012';

        else if ($input == "id")
            return 'E013';

        else if ($input == "promocode")
            return 'E014';

        else if ($input == "doctor_id")
            return 'E015';

        else if ($input == "payment_method" || $input == "payment_method_id")
            return 'E016';

        else if ($input == "day_date")
            return 'E017';

        else if ($input == "specification_id")
            return 'E018';

        else if ($input == "importance")
            return 'E019';

        else if ($input == "type")
            return 'E020';

        else if ($input == "message")
            return 'E021';

        else if ($input == "reservation_no")
            return 'E022';

        else if ($input == "reason")
            return 'E023';

        else if ($input == "branch_no")
            return 'E024';

        else if ($input == "name_en")
            return 'E025';

        else if ($input == "name_ar")
            return 'E026';

        else if ($input == "gender")
            return 'E027';

        else if ($input == "nickname_en")
            return 'E028';

        else if ($input == "nickname_ar")
            return 'E029';

        else if ($input == "rate")
            return 'E030';

        else if ($input == "price")
            return 'E031';

        else if ($input == "information_en")
            return 'E032';

        else if ($input == "information_ar")
            return 'E033';

        else if ($input == "street")
            return 'E034';

        else if ($input == "branch_id")
            return 'E035';

        else if ($input == "insurance_companies")
            return 'E036';

        else if ($input == "photo")
            return 'E037';

        else if ($input == "logo")
            return 'E038';

        else if ($input == "working_days")
            return 'E039';

        else if ($input == "insurance_companies")
            return 'E040';

        else if ($input == "reservation_period")
            return 'E041';

        else if ($input == "nationality_id")
            return 'E042';

        else if ($input == "commercial_no")
            return 'E043';

        else if ($input == "nickname_id")
            return 'E044';

        else if ($input == "reservation_id")
            return 'E045';

        else if ($input == "attachments")
            return 'E046';

        else if ($input == "summary")
            return 'E047';

        else if ($input == "user_id")
            return 'E048';

        else if ($input == "mobile_id")
            return 'E049';

        else if ($input == "paid")
            return 'E050';

        else if ($input == "use_insurance")
            return 'E051';

        else if ($input == "doctor_rate")
            return 'E052';

        else if ($input == "provider_rate")
            return 'E053';

        else if ($input == "message_id")
            return 'E054';

        else if ($input == "hide")
            return 'E055';

        else if ($input == "checkoutId")
            return 'E056';

        else
            return "";
    }

    public function getCodeByDay($dayName)
    {
        if ($dayName == "Saturday")
            return 'sat';

        else if ($dayName == "Sunday")
            return 'sun';

        else if ($dayName == "Monday")
            return 'mon';

        else if ($dayName == "Tuesday")
            return 'tue';

        else if ($dayName == "Wednesday")
            return 'wed';

        else if ($dayName == "Thursday")
            return 'thu';

        else if ($dayName == "Friday")
            return 'fri';

        else
            return null;
    }

    public function getDayByCode($code)
    {
        if ($code == "sat")
            return 'Saturday';

        else if ($code == "sun")
            return 'Sunday';

        else if ($code == "mon")
            return 'Monday';

        else if ($code == "tue")
            return 'Tuesday';

        else if ($code == "wed")
            return 'Wednesday';

        else if ($code == "thu")
            return 'Thursday';

        else if ($code == "fri")
            return 'Friday';

        else
            return null;
    }

    public function checkCityByID($id)
    {
        $city = City::find($id);
        if ($city != null)
            return true;
        return false;
    }

    public function checkInsuranceCompanyByID($id)
    {
        $insuranceCompany = InsuranceCompany::find($id);
        if ($insuranceCompany != null)
            return true;
        return false;
    }

    public function checkPaymentMethodByID($id)
    {
        $paymentMethod = PaymentMethod::find($id);
        if ($paymentMethod != null)
            return true;
        return false;
    }

    public function getPaymentMethodByID($id)
    {
        return PaymentMethod::find($id);
    }

    public function getTypeById($id)
    {
        return ProviderType::find($id);
    }

    public function getCityByID($id)
    {
        return City::find($id);
    }

    public function getAllCities()
    {
        return City::select('id', DB::raw('name_' . $this->getCurrentLang() . ' as name'))->get();
    }

    public function checkDistrictByID($id)
    {
        $district = District::find($id);
        if ($district != null)
            return true;
        return false;
    }

    public function getDistrictByID($id)
    {
        return District::select('id', DB::raw('name_' . $this->getCurrentLang() . ' as name'))->find($id);
    }

    public function getAllDistricts($cityID = null)
    {
        $districts = District::query()->with(['city' => function ($q) {
            $q->select('id', DB::raw('name_' . $this->getCurrentLang() . ' as name'));
        }]);
        if ($cityID != null)
            $districts = $districts->where('city_id', $cityID);

        return $districts->select('id', 'city_id', DB::raw('name_' . $this->getCurrentLang() . ' as name'))->get();
    }

    public function getInsuranceCompanyByID($id)
    {
        return InsuranceCompany::select('id', 'image', DB::raw('name_' . $this->getCurrentLang() . ' as name'))->find($id);
    }

    public function getAllInsuranceCompanies($doctor_id = null, $branch_id = null)
    {
        $insuranceCompany = InsuranceCompany::query()->where('status', 1);
        if ($doctor_id != null && $doctor_id != 0)
            $insuranceCompany = $insuranceCompany->whereIn('id', function ($q) use ($doctor_id) {
                $q->select('insurance_company_id')->from('insurance_company_doctor')->where('doctor_id', $doctor_id);
            });

        if ($branch_id != null && $branch_id != 0)
            $insuranceCompany = $insuranceCompany->whereIn('id', function ($q) use ($branch_id) {
                $q->select('insurance_company_id')->from('insurance_company_doctor')->where('doctor_id', function ($qq) use ($branch_id) {
                    $qq->select('id')->from('doctors')->where('provider_id', $branch_id);
                });
            });

        $insuranceCompany = $insuranceCompany->select('id', 'image', DB::raw('name_' . $this->getCurrentLang() . ' as name'));
        return $insuranceCompany->get();
    }

    public function getAgreementText()
    {
        return Mix::select(DB::raw('agreement_' . $this->getCurrentLang() . ' as content'), 'updated_at')->first();
    }

    public function getNotesText()
    {
        return Mix::select(DB::raw('reservationNote_' . $this->getCurrentLang() . ' as content'))->first();
    }


    public function getReservationRulesText()
    {
        return Mix::select(DB::raw('reservation_rules_' . $this->getCurrentLang() . ' as content'))->first();
    }

    public function getProviderReservationRulesText()
    {
        return Mix::select(DB::raw('provider_reg_rules_' . $this->getCurrentLang() . ' as content'))->first();
    }

    public function getAllPaymentMethods()
    {
        return PaymentMethod::select('id', DB::raw('name_' . $this->getCurrentLang() . ' as name'))->get();
    }

    public function getSpecificationByID($id)
    {
        return Specification::select('id', DB::raw('name_' . $this->getCurrentLang() . ' as name'))->find($id);
    }

    public function checkValidationFields($specification_id = null, $city_id = null, $nickname_id = null,
                                          $typeId = null, $district_id = null, $insurance_company_id = null)
    {
        $query = '';
        if (isset($specification_id) && $specification_id != 0) {
            $query .= "(SELECT count(*) from specifications where id = '" . $specification_id . "') as specification_found, ";
        }

        if (isset($city_id) && $city_id != 0) {
            $query .= "(SELECT count(*) from cities where id = " . $city_id . ") as city_found, ";
        }

        if (isset($nickname_id) && $nickname_id != 0) {
            $query .= "(SELECT count(*) from doctor_nicknames where id = '" . $nickname_id . "') as nickname_found, ";
        }

        if (isset($typeId) && is_array($typeId) && count($typeId) > 0) {
            $query .= "(SELECT count(*) from provider_types where id in (" . implode(",", $typeId) . ")) as type_found, ";
        }

        if (isset($district_id) && $district_id != 0) {
            $query .= "(SELECT count(*) from districts where id = '" . $district_id . "') as district_found, ";
        }

        if (isset($insurance_company_id) && $insurance_company_id != 0) {
            $query .= "(SELECT count(*) from insurance_companies where id = '" . $insurance_company_id . "') as insurance_company_found, ";
        }

        $query .= "(SELECT 1) as counts";
        return \Illuminate\Support\Facades\DB::select('SELECT ' . $query . ' from dual;')[0];
    }

    public function validateDoctorReserveFields(Array $fields)
    {
        $query = '';


        if (isset($fields['mobile']) && $fields['mobile'] != 0) {
            $query .= "(SELECT count(*) from providers where mobile = '" . $fields['mobile'] . "') as mobile_found, ";
        }

        if (isset($fields['email']) && $fields['email'] != 0) {
            $query .= "(SELECT count(*) from providers where email = '" . $fields['email'] . "') as email_found, ";
        }

        if (isset($fields['specification_id']) && $fields['specification_id'] != 0) {
            $query .= "(SELECT count(*) from specifications where id = '" . $fields['specification_id'] . "') as specification_found, ";
        }

        if (isset($fields['provider_id']) && $fields['provider_id'] != 0) {
            $query .= "(SELECT count(*) from providers where id = '" . $fields['provider_id'] . "') as provider_found, ";
        }

        if (isset($fields['city_id']) && $fields['city_id'] != 0) {
            $query .= "(SELECT count(*) from cities where id = " . $fields['city_id'] . ") as city_found, ";
        }

        if (isset($fields['nickname_id']) && $fields['nickname_id'] != 0) {
            $query .= "(SELECT count(*) from doctor_nicknames where id = '" . $fields['nickname_id'] . "') as nickname_found, ";
        }

        if (isset($fields['typeId']) && is_array($fields['typeId']) && count($fields['typeId']) > 0) {
            $query .= "(SELECT count(*) from provider_types where id in (" . implode(",", $fields['typeId']) . ")) as type_found, ";
        }

        if (isset($fields['district_id']) && $fields['district_id'] != 0) {
            $query .= "(SELECT count(*) from districts where id = '" . $fields['district_id'] . "') as district_found, ";
        }

        if (isset($fields['insurance_company_id']) && $fields['insurance_company_id'] != 0) {
            $query .= "(SELECT count(*) from insurance_companies where id = '" . $fields['insurance_company_id'] . "') as insurance_company_found, ";
        }

        $query .= "(SELECT 1) as counts";
        return \Illuminate\Support\Facades\DB::select('SELECT ' . $query . ' from dual;')[0];
    }

    public function validateFields(Array $fields)
    {
        $query = '';


        if (isset($fields['mobile']) && $fields['mobile'] != 0) {
            $query .= "(SELECT count(*) from providers where mobile = '" . $fields['mobile'] . "') as mobile_found, ";
        }

        if (isset($fields['email']) && $fields['email'] != 0) {
            $query .= "(SELECT count(*) from providers where email = '" . $fields['email'] . "') as email_found, ";
        }

        if (isset($fields['specification_id']) && $fields['specification_id'] != 0) {
            $query .= "(SELECT count(*) from specifications where id = '" . $fields['specification_id'] . "') as specification_found, ";
        }

        if (isset($fields['nationality_id']) && $fields['nationality_id'] != 0) {
            $query .= "(SELECT count(*) from nationalities where id = '" . $fields['nationality_id'] . "') as nationality_found, ";
        }

        if (isset($fields['provider_id']) && $fields['provider_id'] != 0) {
            $query .= "(SELECT count(*) from providers where id = '" . $fields['provider_id'] . "') as provider_found, ";
        }

        if (isset($fields['user_id']) && $fields['user_id'] != 0) {
            $query .= "(SELECT count(*) from users where id = '" . $fields['user_id'] . "') as user_found, ";
        }

        if (isset($fields['doctor_id']) && $fields['doctor_id'] != 0) {
            $query .= "(SELECT count(*) from doctors where id = '" . $fields['doctor_id'] . "') as doctor_found, ";
        }

        if (isset($fields['city_id']) && $fields['city_id'] != 0) {
            $query .= "(SELECT count(*) from cities where id = " . $fields['city_id'] . ") as city_found, ";
        }

        if (isset($fields['nickname_id']) && $fields['nickname_id'] != 0) {
            $query .= "(SELECT count(*) from doctor_nicknames where id = '" . $fields['nickname_id'] . "') as nickname_found, ";
        }

        if (isset($fields['insurance_companies']) && is_array($fields['insurance_companies']) && count($fields['insurance_companies']) > 0) {
            $query .= "(SELECT count(*) from insurance_companies where id in (" . implode(",", $fields['insurance_companies']) . ")) as insurance_companies_found, ";
        }


        if (isset($fields['sensitivities']) && is_array($fields['sensitivities']) && count($fields['sensitivities']) > 0) {
            $query .= "(SELECT count(*) from sensitivity where id in (" . implode(",", $fields['sensitivities']) . ")) as sensitivities_found, ";
        }

        if (isset($fields['typeId']) && is_array($fields['typeId']) && count($fields['typeId']) > 0) {
            $query .= "(SELECT count(*) from provider_types where id in (" . implode(",", $fields['typeId']) . ")) as type_found, ";
        }

        if (isset($fields['district_id']) && $fields['district_id'] != 0) {
            $query .= "(SELECT count(*) from districts where id = '" . $fields['district_id'] . "') as district_found, ";
        }

        if (isset($fields['insurance_company_id']) && $fields['insurance_company_id'] != 0) {
            $query .= "(SELECT count(*) from insurance_companies where id = '" . $fields['insurance_company_id'] . "') as insurance_company_found, ";
        }

        if (isset($fields['payment_method_id']) && $fields['payment_method_id'] != 0) {
            $query .= "(SELECT count(*) from payment_methods where id = '" . $fields['payment_method_id'] . "') as payment_method_found, ";
        }

        if (isset($fields['branch']) && is_array($fields['branch']) && count($fields['branch']) > 2) {
            if (isset($fields['mobile']) && $fields['mobile'] != 0) {
                $query .= "(SELECT count(*) from providers where mobile = '" . $fields['mobile'] . "' and id != '" . $fields['branch']['provider_id'] . "') as branch_mobile_found, ";
            }
            $query .= "(SELECT count(*) from providers where id = '" . $fields['branch']['provider_id'] . "' AND  provider_id = '" . $fields['branch']['main_provider_id'] . "') as branch_found, ";
            $query .= "(SELECT count(*) from providers where id != '" . $fields['branch']['provider_id'] . "' AND  provider_id = '" . $fields['branch']['main_provider_id'] . "' AND  branch_no = '" . $fields['branch']['branch_no'] . "') as branch_no_found, ";
        }


        if (isset($fields['reservation']) && is_array($fields['reservation']) && count($fields['reservation']) > 3) {
            $query .= "(SELECT count(*) from reservations where doctor_id = '" . $fields['reservation']['doctor_id']
                . "' AND  day_date = '" . Carbon::parse($fields['reservation']['day_date'])->format('Y-m-d')
                . "' AND  from_time = '" . $fields['reservation']['from_time']
                . "' AND  to_time = '" . $fields['reservation']['to_time']
                . "' AND approved != '2') as reservation_found, ";
            $query .= "(SELECT count(*) from reserved_times where doctor_id = '" . $fields['reservation']['doctor_id']
                . "' AND  day_date = '" . Carbon::parse($fields['reservation']['day_date'])->format('Y-m-d')
                . "') as reserved_times_found, ";
        }
        if (isset($fields['offer']) && is_array($fields['offer']) && count($fields['offer']) > 3) {
            $query .= "(SELECT count(*) from reservations where offer_id = '" . $fields['offer']['offer_id']
                . "' AND  day_date = '" . Carbon::parse($fields['offer']['day_date'])->format('Y-m-d')
                . "' AND  from_time = '" . $fields['offer']['from_time']
                . "' AND  to_time = '" . $fields['offer']['to_time']
                . "' AND  provider_id = '" . $fields['offer']['provider_id']
                . "' AND approved != '2') as offer_found, ";
            $query .= "(SELECT count(*) from reserved_times where offer_id = '" . $fields['offer']['offer_id']
                . "' AND  day_date = '" . Carbon::parse($fields['offer']['day_date'])->format('Y-m-d')
                . "' AND  branch_id = '" . $fields['offer']['provider_id']
                . "') as reserved_times_found, ";
        }


        $query .= "(SELECT 1) as counts";
        return \Illuminate\Support\Facades\DB::select('SELECT ' . $query . ' from dual;')[0];
    }


    public function getAllSpecifications($provider_id = null)
    {
        $specification = Specification::query();
        if ($provider_id != null) {
            $specification = $specification->whereIn('id', function ($q) use ($provider_id) {
                $q->select('specification_id')->from('doctors')->whereIn('provider_id', function ($qu) use ($provider_id) {
                    $qu->select('id')->from('providers')->where('provider_id', $provider_id)->orWhere('id', $provider_id);
                });
            });
        }
        return $specification->select('id', DB::raw('name_' . $this->getCurrentLang() . ' as name'))->orderBy('name_ar')->get();
    }


    public function getAllSpecificationsV2($provider_id = null)
    {
        /* $specification = Specification::query();
         if ($provider_id != null) {
             $specification = $specification->whereIn('id', function ($q) use ($provider_id) {
                 $q->select('specification_id')->from('doctors')->whereIn('provider_id', function ($qu) use ($provider_id) {
                     $qu->select('id')->from('providers')->where('provider_id', $provider_id)->orWhere('id', $provider_id);
                 });
             });
         }


         return $specification->whereIn('id', function ($q) {
             $q->select('specification_id')->from('doctors')->where('doctor_type','clinic')->whereIn('provider_id', function ($qu) {
                 $qu->select('id')->from('providers');
             });
         }) ->select('id', DB::raw('name_' . $this->getCurrentLang() . ' as name'))->orderBy('name_ar')->get();*/

        $specification = Specification::query();
        if ($provider_id != null) {
            $specification = $specification->whereIn('id', function ($q) use ($provider_id) {
                $q->select('specification_id')->from('doctors')->whereIn('provider_id', function ($qu) use ($provider_id) {
                    $qu->select('id')->from('providers')->where('provider_id', $provider_id)->orWhere('id', $provider_id);
                });
            });
        }
        return $specification->select('id', DB::raw('name_' . $this->getCurrentLang() . ' as name'))->orderBy('name_ar')->get();
    }

    public function getActiveFilters()
    {
        $specification = Filter::active()->selection()->orderBy('id', 'DESC')->get();
        return $specification;
    }


    public function getAllPromoCategories(/*$timer*/)
    {
        $category = PromoCodeCategory::query();
        $query = '';
        //if ($timer == 1)// get all categories with countrt time down
        return $category
            ->active()
            // ->withTimer()
            ->select('id',
                DB::raw('name_' . $this->getCurrentLang() . ' as name'), 'photo',
                'hours',
                'minutes',
                'seconds')
            ->orderBy('lft')
            ->get();
        //  else
        /* return $category
             ->withOutTimer()
             ->select('id',
                 DB::raw('name_' . $this->getCurrentLang() . ' as name'), 'photo',
                 'hours',
                 'minutes',
                 'seconds')
             ->orderBy('lft')
             ->get();*/
    }

    public function getPromoCategoriesV2()
    {
        $category = OfferCategory::query();
        return $category
            ->whereHas('childCategories', function ($q) {
                $q->whereHas('offers', function ($qq){
                    $qq -> where('status',1);
                });
            })
            ->withOutTimer()
            ->parentCategories()
            ->select('id',
                DB::raw('name_' . $this->getCurrentLang() . ' as name'), 'photo',
                'hours',
                'minutes',
                'seconds')
            ->orderBy('lft')
            ->get();
    }


    public function getSubCategories($category_id)
    {
        $category = OfferCategory::query();
        return $category
            ->withOutTimer()
            ->whereNotNull('parent_id')
            ->where('parent_id', $category_id)
            ->select('id',
                DB::raw('name_' . $this->getCurrentLang() . ' as name'), 'photo',
                'hours',
                'minutes',
                'seconds')
            ->orderBy('lft')
            ->get();
    }

    public function getBannersV2()
    {
        $banners = Banner::query();
        return $banners
            ->select('id',
                'photo',
                DB::raw('bannerable_type AS type'),
                DB::raw('bannerable_id  as type_id'),
                'subCategory_id',
                'external_link'
            )
            ->orderBy('lft')
            ->paginate(PAGINATION_COUNT);
    }

    public function getBannersList()
    {
        $banners = Banner::query();
        return $banners
            ->select('id',
                'photo',
                DB::raw('bannerable_type AS type'),
                DB::raw('bannerable_id  as type_id'),
                'lft',
                'rgt',
                'depth',
                'subCategory_id',
                'external_link'
            )
            ->orderBy('lft')
            ->get();
    }


    public function getBannersV2List()
    {
        $banners = Banner::query();
        return $banners
            ->select('id',
                'photo',
                DB::raw('bannerable_type AS type'),
                DB::raw('bannerable_id  as type_id'),
                'subCategory_id',
                'external_link'
            )
            ->orderBy('lft')
            ->get();
    }



    public function getBanners()
    {
        $banners = Mbanner::query();
        return $banners
            ->select('id',
                'photo',
                DB::raw('bannerable_type AS type'),
                DB::raw('bannerable_id  as type_id')
            )
            ->orderBy('id', 'DESC')
            ->get();
    }

    public function getTimerPromoCategoriesV2()
    {
        $category = OfferCategory::query();
        return $category
            ->whereHas('childCategories', function ($q) {
                $q->has('offers');
            })
            ->withTimer()
            ->whereNull('parent_id')
           // ->where('timerexpired', 0)
            ->select('id',
                DB::raw('name_' . $this->getCurrentLang() . ' as name'), 'photo',
                'hours',
                'minutes',
                'seconds',
                'created_at'
            )
            ->orderBy('lft')
            ->get();
    }

    public function getAppInfo()
    {
        return Mix::select('mobile', 'email', 'app_price')->first();
    }

    public function getNationalityById($id)
    {
        return Nationality::find($id);
    }

    public function getAllNationalities()
    {
        return Nationality::select('id', DB::raw('name_' . $this->getCurrentLang() . ' as name'))->get();
    }

    public function getNicknameById($id)
    {
        return Nickname::select('id', DB::raw('name_' . $this->getCurrentLang() . ' as name'))->find($id);
    }

    public function getDoctorNicknames($provider_id = null)
    {
        $nickname = Nickname::query();
        $nickname = $nickname->active()
            ->select('id', DB::raw('name_' . $this->getCurrentLang() . ' as name'));
        if ($provider_id != null) {
            $nickname = $nickname->whereIn('id', function ($q) use ($provider_id) {
                $q->select('nickname_id')->from('doctors')->whereIn('provider_id', function ($qu) use ($provider_id) {
                    $qu->select('id')->from('providers')->where('provider_id', $provider_id);
                })->orWhere('provider_id', $provider_id) ;
            });
        }
        return $nickname-> distinct() -> get();
    }

    public function getCategoriesFromDB()
    {
        return Category::select('id', DB::raw('name_' . app()->getLocale() . ' as name'))->get();
    }

    public function getProviderBranches($id)
    {
        return Provider::where('provider_id', $id)->where('status', true)->select('id', DB::raw('name_' . app()->getLocale() . ' as name'))->get();
    }

    public function getLastReplyInMessage($id)
    {
        return Message::where('message_id', $id)->orderBy('order', 'DESC')->first();
    }

    public function saveImage($folder, $photo)
    {
        $img = str_replace('data:image/jpg;base64,', '', $photo);
        $img = str_replace('data:image/png;base64,', '', $img);
        $img = str_replace('data:image/gif;base64,', '', $img);
        $img = str_replace('data:image/jpeg;base64,', '', $img);
        $img = str_replace(' ', '+', $img);
        $data = base64_decode($img);
        $filename = time() . '_' . $folder . '.png';
        $path = 'images/' . $folder . '/' . $filename;
        file_put_contents($path, $data);
        return 'images/' . $folder . '/' . $filename;
    }

    public function saveFile($folder, $photo)
    {

        $file_extension = $photo->getClientOriginalExtension();
        $filename = time() . '_' . $folder . '.' . $file_extension;
        $path = 'images/' . $folder;
        $photo->move($path, $filename);

        return 'images/' . $folder . '/' . $filename;
    }

    public function calculateBalance($provider, $paymentMethod_id, Reservation $reservation, $request)
    {
        // all this balance make by - minus because the payment only is  cash no visa untill now
        $manager = $this->getAppInfo();
        $mainprov = Provider::find($provider->provider_id == null ? $provider->id : $provider->provider_id);
        $mainprov->makeVisible(['application_percentage_bill', 'application_percentage', 'application_percentage_bill_insurance', '']);
        //if there is bill  take app percentage from bill + reservation price
        $reservationBalance = 0;
        $discountType = '--';
        if (!is_numeric($mainprov->application_percentage_bill) || $mainprov->application_percentage_bill == 0) {
            $provider_has_bill = 0;
        } else {
            $provider_has_bill = 1;
        }

        if (!is_numeric($mainprov->application_percentage_bill_insurance) || $mainprov->application_percentage_bill_insurance == 0) {
            $provider_has_bill_insurance = 0;
        } else {
            $provider_has_bill_insurance = 1;
        }

        //if reservation without any coupon
        if ($reservation->use_insurance == 1 && $reservation->promocode_id == null) {
            $discountType = " فاتورة حجز نقدي بتأمين";
            $total_amount = ($provider_has_bill == 1 or $provider_has_bill_insurance == 1) && ($reservation->promocode_id == null) ? $request->bill_total : $reservation->price;
            $MC_percentage = $reservation->use_insurance == 0 ? $mainprov->application_percentage_bill + $mainprov->application_percentage : $mainprov->application_percentage_bill_insurance + $mainprov->application_percentage;
            $reservationBalanceBeforeTax = ($total_amount * $MC_percentage) / 100;
            $additional_tax_value = ($reservationBalanceBeforeTax * 5) / 100;
            $reservationBalance = ($reservationBalanceBeforeTax + $additional_tax_value);
        } elseif ($reservation->use_insurance == 0 && $reservation->promocode_id == null) {
            $discountType = " فاتورة حجز نقدي ";
            $total_amount = ($provider_has_bill == 1 or $provider_has_bill_insurance == 1) && ($reservation->promocode_id == null) ? $request->bill_total : $reservation->price;
            $MC_percentage = $reservation->use_insurance == 0 ? $mainprov->application_percentage_bill + $mainprov->application_percentage : $mainprov->application_percentage_bill_insurance + $mainprov->application_percentage;
            $reservationBalanceBeforeTax = ($total_amount * $MC_percentage) / 100;
            $additional_tax_value = ($reservationBalanceBeforeTax * 5) / 100;
            $reservationBalance = ($reservationBalanceBeforeTax + $additional_tax_value);
        }

        //if reservation with coupon
        if ($reservation->promocode_id != null) {
            if ($reservation->coupon->coupons_type_id == 1) {  //discount coupon
                //get  coupon total amount    step 1
                $totalCouponPrice = $reservation->coupon->price;   //1000
                $coupounDiscountPercentage = $reservation->coupon->discount;  //20
                $amountAfterDiscount = $coupounDiscountPercentage > 0 ? ($totalCouponPrice - (($totalCouponPrice * $coupounDiscountPercentage) / 100)) : $totalCouponPrice; //800
                $MC_percentage = $reservation->coupon->application_percentage;
                $medicalAmount = $MC_percentage > 0 ? ($amountAfterDiscount * $MC_percentage) / 100 : 0;
                $addationan_tax = ($medicalAmount * 5) / 100;
                //get amount after coupoun discount applied step 2
                //calculate admin percentage of step 2
                $discountType = " فاتورة حجز بكوبون خصم ";
                $reservationBalance = $medicalAmount + $addationan_tax;
            } else {   //prepaid coupon
                $offer_amount_WithoutVAT = $reservation->coupon->price;
                $MC_percentage = isset($reservation->coupon->paid_coupon_percentage) ? $reservation->coupon->paid_coupon_percentage : 0; //15
                $discountType = " فاتورة حجز بكوبون مدفوع  ";
                $amount = ($offer_amount_WithoutVAT * $MC_percentage) / 100;
                $addtional_tax = ($amount * 5) / 100;
                $reservationBalance = $amount + $addtional_tax;
            }
        }

        $provider = $reservation->provider;  // always get branch
        $provider->update([
            'balance' => $provider->balance - $reservationBalance,
        ]);
        $reservation->update([
            'discount_type' => $discountType,
            'application_balance_value' => -$reservationBalance
        ]);
        /*  $manager->update([
              'balance' => $manager->unpaid_balance + $reservationBalance
          ]);*/

        return true;
    }


    function getDistance($lat1, $lon1, $lat2, $lon2, $unit)
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);
        if ($unit == "K") {
            return round(($miles * 1.609344));
        } else if ($unit == "N") {
            return round(($miles * 0.8684));
        } else {
            return round($miles);
        }
    }

    public
    function auth($guard, $relations = [])
    {
        $user = null;
        if (isset(request()->api_token)) {
            $api_token = request()->api_token;
            if ($guard == 'user-api') {
                $user = UserToken::where('api_token', request()->api_token)->first();
                $id = $user ? $user->user_id : 0;
                $user = User::where('id', $id);

            } else if ($guard == 'provider-api') {
                $user = Token::where('api_token', request()->api_token)->first();
                $id = $user ? $user->provider_id : 0;
                $user = Provider::where('id', $id);
            } else if ($guard == 'manager-api') {
                $user = Manager::where('api_token', request()->api_token);
            }

            if ($relations && is_array($relations))
                $user->with($relations);

            $user = $user->first();
        }
        return $user;
    }


    function getRandomString($length)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[mt_rand(0, strlen($characters) - 1)];
        }
        $chkCode = Payment::where('code', $string)->first();
        if ($chkCode) {
            $this->getRandomString(6);
        }
        return $string;
    }

    function getRandomStringForInvitation($length = 6)
    {
        $charactersNum = '0123456789';
        $charactersChar = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $string = '';
        for ($i = 0; $i < 3; $i++) {
            $string .= $charactersNum[mt_rand(0, strlen($charactersNum) - 1)];
        }

        for ($i = 0; $i < 3; $i++) {
            $string .= $charactersChar[mt_rand(0, strlen($charactersChar) - 1)];
        }

        $randomCode = '';
        $randomCode .= $string[mt_rand(0, strlen($string) - 1)];

        $chkCode = User::where('invitation_code', $randomCode)->first();

        if ($chkCode) {
            $this->getRandomStringForInvitation(6);
        }
        return $randomCode;
    }


    public
    function nearestDate()
    {
        try {
            /*  $days = $this->times;
              $match = $this->getMatchedDateToDays($days);
              if (!$match || $match['date'] == null)
                  return null;
              $doctorTimesCount = $this->getDoctorTimePeriodsInDay($match['day'], $match['day']['day_code'], true);

              $availableTime = $this->getFirstAvailableTime($this->id, $doctorTimesCount, $days, $match['date'], $match['index']);

              if (count((array)$availableTime))
                  return $availableTime['date'] . ' ' . $availableTime['from_time'];
              else
                  return null;*/

            return '2016-05-12 15:30:00';
        } catch (\Exception $ex) {

        }
    }

    public function getAuthDoctor()
    {
        // get auth doctor in doctor area
        return auth('doctor-api')->userOrFail();
    }

    public function getHyperPayMerchantTransactionUniqueId($length = 10){

        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[mt_rand(0, strlen($characters) - 1)];
        }
        $chkCode = HyperPayTransaction::where('random_id', $string)->first();
        if ($chkCode) {
            $this->getHyperPayMerchantTransactionUniqueId(6);
        }
        return $string;

    }
}
