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
        $result = ProviderType::get();
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
        return Specification::select(DB::raw('id, name_' . app()->getLocale() . ' as name'))->get();
    }

    public function apiGetAllNicknames()
    {
        return Nickname::select(DB::raw('id, name_' . app()->getLocale() . ' as name'))->get();
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
        $doctor = Doctor::find($id);
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
        }, 'paymentMethod'])->find($id);
    }

    public function getAllOfferParentCategoriesList()
    {
        return OfferCategory::parentCategories()->select(DB::raw('id, name_' . app()->getLocale() . ' as name, hastimer'))->get();
    }

    public function getAllActiveUsersList()
    {
        return User::active()->get(['id', 'name']);
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
            $parents = OfferCategory::whereIn('id', $selectedChildCat->toArray())->pluck('parent_id');

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
                'name',
                DB::raw('IF ((SELECT count(id) FROM user_offers WHERE user_offers.offer_id = ' . $offer->id . ' AND user_offers.user_id = users.id) > 0, 1, 0) as selected'))->get();
        } else {
            return User::select('id', 'name', DB::raw('0 as selected'))->get();
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

}

