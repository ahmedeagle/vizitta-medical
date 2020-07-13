<?php

namespace App\Traits\CPanel;

use App\Http\Resources\CPanel\ProviderTypesResource;
use App\Http\Resources\CPanel\CitiesResource;
use App\Http\Resources\CPanel\DistrictsResource;
use App\Http\Resources\CPanel\MainActiveProvidersResource;
use App\Http\Resources\CPanel\SingleDoctorResource;
use App\Http\Resources\CPanel\UserFavoriteDoctorsResource;
use App\Http\Resources\CPanel\UserFavoriteProvidersResource;
use App\Http\Resources\CPanel\UserRecordsResource;
use App\Http\Resources\CPanel\UserReservationsResource;
use App\Http\Resources\CPanel\SingleInsuranceCompanyResource;
use App\Models\Brand;
use App\Models\City;
use App\Models\District;
use App\Models\Doctor;
use App\Models\Favourite;
use App\Models\InsuranceCompany;
use App\Models\Nationality;
use App\Models\Nickname;
use App\Models\Offer;
use App\Models\OfferCategory;
use App\Models\PaymentMethod;
use App\Models\Provider;
use App\Models\ProviderType;
use App\Models\Reservation;
use App\Models\Specification;
use App\Models\User;
use App\Models\UserRecord;
use Illuminate\Support\Facades\DB;
use function foo\func;

trait GeneralTrait
{
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

    public function getProviderTypes()
    {
        $result = ProviderType::active()->get();
        return ProviderTypesResource::collection($result);
    }

    public function getCities()
    {
        $result = City::get();
        return CitiesResource::collection($result);
    }

    public function getDistricts()
    {
        $result = District::get();
        return DistrictsResource::collection($result);
    }

    public function getDistrictsByCityId($cityId)
    {
        $result = District::where('city_id', $cityId)->get();
        return DistrictsResource::collection($result);
    }

    public function getMainActiveProviders()
    {
        $result = Provider::where('status', true)->whereNull('provider_id')->get();
        return MainActiveProvidersResource::collection($result);
    }

    public function getMainActiveBranches()
    {
        $result = Provider::where('status', true)->whereNotNull('provider_id')->get();
        return MainActiveProvidersResource::collection($result);
    }

    public function getMainActiveProviderBranches($id)
    {
        $result = Provider::where('status', true)->where('provider_id', $id)->get();
        return MainActiveProvidersResource::collection($result);
    }

    public function apiGetAllSpecifications()
    {
        return Specification::active()
            ->select(DB::raw('id, name_' . app()
                    ->getLocale() . ' as name'))
            ->get();
    }

    public function apiGetAllNicknames()
    {
        return Nickname::active()->select(DB::raw('id, name_' . app()->getLocale() . ' as name'))->get();
    }

    public function apiGetAllNationalities()
    {
        return Nationality::select(DB::raw('id, name_' . app()->getLocale() . ' as name'))->get();
    }

    public function apiGetAllInsuranceCompaniesWithSelected($doctor = null)
    {
        if ($doctor != null) {
            return InsuranceCompany::select('id', 'name_' . app()->getLocale() . ' as name', DB::raw('IF ((SELECT count(id) FROM insurance_company_doctor WHERE insurance_company_doctor.doctor_id = ' . $doctor->id . ' AND insurance_company_doctor.insurance_company_id = insurance_companies.id) > 0, 1, 0) as selected'))->get();
        } else {
            return InsuranceCompany::select('id', 'name_' . app()->getLocale() . ' as name', DB::raw('0 as selected'))->get();
        }
    }

    public function getCustomUserReservations($id)
    {
        $result = Reservation::where('user_id', $id)->orderBy('day_date')->orderBy('from_time')->get();
        return UserReservationsResource::collection($result);
    }

    public function getCustomUserRecords($id)
    {
        $result = UserRecord::where('user_id', $id)->orderBy('day_date')->orderBy('created_at')->get();
        return UserRecordsResource::collection($result);
    }

    public function getCustomUserFavoriteDoctors($id)
    {
        $result = Favourite::where('user_id', $id)->whereNotNull('doctor_id')->get();
        return UserFavoriteDoctorsResource::collection($result);
    }

    public function getCustomUserFavoriteProviders($id)
    {
        $result = Favourite::where('user_id', $id)->whereNotNull('provider_id')->get();
        return UserFavoriteProvidersResource::collection($result);
    }

    public function createCustomBrand($request)
    {
        $fileName = "";
        if (isset($request->photo) && !empty($request->photo)) {
            $fileName = $this->saveImage('brands', $request->photo);
        }
        $brand = Brand::create(['photo' => $fileName]);
        return $brand;
    }

    public function getInsuranceCompanyById($id)
    {
        $company = InsuranceCompany::find($id);
        return new SingleInsuranceCompanyResource($company);
    }

    public function getDoctorDetailsById($id)
    {
        $doctor = Doctor::with(['branch' => function ($q) {
            $q->select('id', 'name_' . app()->getLocale() . ' as name', 'provider_id');
            $q->with(['provider' => function ($qq) {
                $qq->select('id', 'name_' . app()->getLocale() . ' as name', 'provider_id');
            }]);
        }])
            ->find($id);
        return new SingleDoctorResource($doctor);
    }

    // Providers
    public function getProviderDetailsById($id)
    {
        return Provider::with(['city', 'district'])->find($id);
    }

    public function getReservationDetailsById($id)
    {
        return Reservation::with(['offer' => function ($q) {
            $q->select('id',
                DB::raw('title_' . app()->getLocale() . ' as title'),
                'expired_at');
        }, 'paymentMethod', 'user' => function ($q) {
            $q->select('id', 'name');
        }, 'doctor' => function ($q) {
            $q->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'branch' => function ($q) {
            $q->select('id', 'name_' . app()->getLocale() . ' as name','provider_id');
            $q->with(['provider' => function($qq){
                $qq -> select('id', 'name_' . app()->getLocale() . ' as name','provider_id');
            }]);
        },'rejectionResoan' => function($q){
            $q ->select('id', 'name_' . app()->getLocale() . ' as name');
        }])
            ->find($id);
    }

    public function getAllOfferParentCategoriesList()
    {
        return OfferCategory::parentCategories()->active()->select(DB::raw('id, name_' . app()->getLocale() . ' as name, hastimer'))->get();
    }

    public function getAllActiveUsersList()
    {
        return User::active()->get(['id', 'name']);
    }

    public function getOfferUser($offer)
    {
        return $offer->users()
            ->get();
    }

    public function getAllPaymentMethodWithSelectedList($offer = null)
    {
        if ($offer != null) {
            return PaymentMethod::where('status', 1)->select(DB::raw('id, flag, name_' . app()->getLocale() . ' as name, IF ((SELECT count(id) FROM offer_payment_methods WHERE offer_payment_methods.offer_id = ' . $offer->id . ' AND offer_payment_methods.payment_method_id = payment_methods.id) > 0, 1, 0) as selected'))->get();
        } else {
            return PaymentMethod::where('status', 1)->select(DB::raw('id, flag, name_' . app()->getLocale() . ' as name, 0 as selected'))->get();
        }
    }

    public function getChildCategoriesListByParentCategory($id)
    {
        return OfferCategory::where('parent_id', $id)->get(['name_' . app()->getLocale() . ' as name', 'id']);
    }

    public function getOfferDetailsById($id)
    {
        $offer = Offer::with(['offerBranches' => function ($q) {
            $q->wherehas('branch');
            $q->with(['branch' => function ($qq) {
                $qq->select('id', 'name_' . app()->getLocale() . ' as name', 'provider_id');
            }]);
        }, 'reservations', 'paymentMethods', 'offerBranchTimes'])->find($id);
        if (!$offer) {
            return null;
        }
        return $offer;
    }

    public function getOfferCategoriesWithSelected($offer = null)
    {
        if ($offer != null) {
            $selectedChildCat = \Illuminate\Support\Facades\DB::table('offers_categories_pivot')
                ->where('offer_id', $offer->id)
                ->pluck('category_id');
            $parents = OfferCategory::active()->whereIn('id', $selectedChildCat->toArray())->pluck('parent_id');

            $data = OfferCategory::whereNull('parent_id')
                ->with(['childCategories' => function ($query) use ($offer) {
                    $query->select('id', 'parent_id', 'name_ar',
                        DB::raw('IF ((SELECT count(id) FROM offers_categories_pivot WHERE offers_categories_pivot.offer_id = ' . $offer->id . ' AND offers_categories_pivot.category_id = offers_categories.id) > 0, 1, 0) as selected'));
                }])->select('id'
                    , 'name_ar'
                    , 'hastimer'
                )->get();

            foreach ($data as $key => $cat) {
                if (in_array($cat->id, $parents->toArray()))
                    $data[$key]['selected'] = 1;
                else
                    $data[$key]['selected'] = 0;
            }

            return $data;
        } else {
            return [];
        }
    }

    public function getOfferActiveUsersWithSelected($offer = null)
    {
        if ($offer != null) {
            return User::select('id',
                'name'
            /*  DB::raw('IF ((SELECT count(id) FROM user_offers WHERE user_offers.offer_id = ' . $offer->id . ' AND user_offers.user_id = users.id) > 0, 1, 0) as selected')*/)->get();
        } else {
            return User::select('id', 'name')->get();
        }
    }


    public function getOfferActiveUsersWithPaginateSelected($offer = null)
    {
        if ($offer != null) {
            return User::select('id',
                'name',
                DB::raw('IF ((SELECT count(id) FROM user_offers WHERE user_offers.offer_id = ' . $offer->id . ' AND user_offers.user_id = users.id) > 0, 1, 0) as selected'))->paginate(PAGINATION_COUNT);
        } else {
            return User::select('id', 'name', DB::raw('0 as selected'))->paginate(PAGINATION_COUNT);
        }
    }

    public function returnData($key, $value, $msg = "")
    {
        return response()->json(['status' => true, 'errNum' => "S000", 'msg' => $msg, $key => $value]);
    }

    public function returnError($errNum, $msg)
    {
        return response()->json([
            'status' => false,
            'errNum' => $errNum,
            'msg' => $msg
        ]);
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

    public function returnValidationError($code = "E001", $validator)
    {
        return $this->returnError($code, $validator->errors()->first());
    }

    public function returnSuccessMessage($msg = "", $errNum = "S000")
    {
        return ['status' => true, 'errNum' => $errNum, 'msg' => $msg];
    }

}

