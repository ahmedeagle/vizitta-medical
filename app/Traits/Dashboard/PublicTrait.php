<?php

namespace App\Traits\Dashboard;

use App\Models\City;
use App\Models\District;
use App\Models\Doctor;
use App\Models\InsuranceCompany;
use App\Models\Manager;
use App\Models\Nationality;
use App\Models\Nickname;
use App\Models\Offer;
use App\Models\OfferCategory;
use App\Models\PromoCode;
use App\Models\PromoCodeCategory;
use App\Models\Provider;
use App\Models\ProviderType;
use App\Models\Reservation;
use App\Models\Specification;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use DB;
use PhpParser\Comment\Doc;

trait PublicTrait
{
    public function getProvider($id)
    {
        return Provider::find($id);
    }

    public function getUser($id)
    {
        return User::find($id);
    }

    public function getActiveProviders($count = false, $type = 'providers')
    {

        if ($type == 'providers') {
            $providers = Provider::query()->whereNull('provider_id');   // main providers
        } else
            $providers = Provider::query()->whereNotNull('provider_id'); // branches

        if ($count)
            return $providers->count();

        return $providers->get();
    }

    public function getActiveDoctors($count = false)
    {
        $doctors = Doctor::query()->where('status', true);
        if ($count)
            return $doctors->count();

        return $doctors->get();
    }

    public function getPaidReservations($count = false)
    {
        $reservations = Reservation::query()->where('paid', true);
        if ($count)
            return $reservations->count();

        return $reservations->get();
    }

    public function getFinishedPaidReservations($count = false)
    {
        $reservations = Reservation::query()->where('paid', true)->whereDate('day_date', '<=', date('Y-m-d'));
        if ($count)
            return $reservations->count();

        return $reservations->get();
    }

    public function getFutureReservations($count = false)
    {
        $reservations = Reservation::current()->whereDate('day_date', '>', date('Y-m-d'));
        if ($count)
            return $reservations->count();

        return $reservations->get();
    }

    public function getActiveUsers($count = false)
    {
        $users = User::query()->where('status', true);
        if ($count)
            return $users->count();

        return $users->get();
    }

    public function getAllTypes()
    {
        return ProviderType::pluck('name_ar', 'id');
    }

    public function getAllCities()
    {
        return City::pluck('name_ar', 'id');
    }

    public function getAllDistricts()
    {
        return District::pluck('name_ar', 'id');
    }

    public function getAllSpecifications()
    {
        return Specification::pluck('name_ar', 'id');
    }

    public function getAllCategories()
    {
        return PromoCodeCategory::pluck('name_ar', 'id');
    }

    public function getAllCategoriesCollection()
    {
        return PromoCodeCategory::select('id', 'name_ar', 'hastimer')->get();
    }

    public function getAllCategoriesCollectionV2()
    {
        return OfferCategory::whereNull('parent_id')->select('id', 'name_ar', 'hastimer')->get();
    }

    public function getAllOffersCollectionV2()
    {
        return Offer::select('id', 'title_ar', 'photo')->get();
    }

    public function getAllOffersCollection()
    {
        return PromoCode::select('id', 'title_ar', 'photo')->get();
    }

    public function getAllActiveUsersWithCurrentOfferSelected($promoCode = null)
    {

        if ($promoCode != null) {
            return User::select('id',
                'name',
                DB::raw('IF ((SELECT count(id) FROM user_promocode WHERE user_promocode.promocode_id = ' . $promoCode->id . ' AND user_promocode.user_id = users.id) > 0, 1, 0) as selected'))->get();
        } else {
            return User::select('id', 'name', DB::raw('0 as selected'))->get();
        }
    }

    public function getAllCategoriesWithCurrentOfferSelected($promoCode = null)
    {
        if ($promoCode != null) {
            return PromoCodeCategory::select('id',
                'name_ar',
                'hastimer',
                DB::raw('IF ((SELECT count(id) FROM promocode_promocodescategory WHERE promocode_promocodescategory.promocode_id = ' . $promoCode->id . ' AND promocode_promocodescategory.category_id = promocodes_categories.id) > 0, 1, 0) as selected'))->get();
        } else {
            return PromoCodeCategory::select('id', 'name_ar', 'hastimer', DB::raw('0 as selected'))->get();
        }
    }


    public function getAllActiveUsers()
    {
        return User::active()->pluck('name', 'id')->toArray();
    }

    public function getAllNicknames()
    {
        return Nickname::pluck('name_ar', 'id');
    }

    public function getAllNationalities()
    {
        return Nationality::pluck('name_ar', 'id');
    }

    public function getAllInsuranceCompanies()
    {
        return InsuranceCompany::pluck('name_ar', 'id');
    }

    public function getAllInsuranceCompaniesWithSelected($doctor = null)
    {

        if ($doctor != null) {
            return InsuranceCompany::select('id', 'name_ar', DB::raw('IF ((SELECT count(id) FROM insurance_company_doctor WHERE insurance_company_doctor.doctor_id = ' . $doctor->id . ' AND insurance_company_doctor.insurance_company_id = insurance_companies.id) > 0, 1, 0) as selected'))->get();
        } else {
            return InsuranceCompany::select('id', 'name_ar', DB::raw('0 as selected'))->get();
        }

    }

    public function getAllActiveDoctors()
    {
        return Doctor::where('status', true)->pluck('name_ar', 'id');
    }

    public function getAllActiveProviders()
    {
        return Provider::where('status', true)->pluck('name_ar', 'id');
    }

    public function getAllMAinActiveProviders()
    {
        return Provider::where('status', true)->whereNull('provider_id')->pluck('name_ar', 'id');
    }

    public function getAllActiveBranches()
    {
        return Provider::where('status', true)->whereNotNull('provider_id')->select('name_ar', 'id', 'provider_id')->get();
    }

    public function uploadImage($folder, $image)
    {
        $image->store('/', $folder);
        $filename = $image->hashName();
        $path = 'images/' . $folder . '/' . $filename;
        return $path;
    }

    public function getManager()
    {
        return Manager::select('mobile', 'email')->find(1);
    }
}
