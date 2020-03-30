<?php

namespace App\Traits\Dashboard;

use App\Models\PaymentMethod;
use App\Models\Offer;
use App\Models\OfferBranch;
//use App\Models\PromoCode_Doctor;
use App\Models\OfferCategory;
use App\Models\Provider;
//use App\Models\Doctor;
use App\Models\User;
use Freshbitsweb\Laratables\Laratables;

//use Illuminate\Http\Request;
//use function foo\func;
use DB;

trait OfferTrait
{
    public function getOfferById($id)
    {
        return Offer::find($id);
    }

    public function getOfferByIdWithRelation($id)
    {
        $offer = Offer::with('offerBranches', 'reservations', 'contents', 'paymentMethods', 'branchTimes')->find($id);
        if (!$offer) {
            return null;
        }
        return $offer;
    }

    public function getOfferByIdWithRelations($id)
    {
        $offer = Offer::with(['offerBranches' => function ($q) {
            $q->wherehas('branch');
            $q->with(['branch' => function ($qq) {
                $qq->select('id', 'name_ar', 'provider_id');
            }]);
        }, 'reservations', 'paymentMethods'])->find($id);
        if (!$offer) {
            return null;
        }
        return $offer;
    }

    public function getAll()
    {
        return Laratables::recordsOf(Offer::class, function ($query) {
            return $query->orderBy('expired_at', 'DESC');
        });
    }

    public function getAllBeneficiaries($couponId)
    {
        return User::with(['reservations' => function ($re) use ($couponId) {
            // $re -> where('reservations.')select('user_id','reservation_no');
            $re->where('promocode_id', $couponId);
        }])->whereHas('reservations', function ($q) use ($couponId) {
            $q->whereHas('coupon', function ($qq) use ($couponId) {
                $qq->where('id', $couponId);
            });
        })->get();
    }

    public function getBranchTable($promoId)
    {
        return Laratables::recordsOf(OfferBranch::class, function ($query) use ($promoId) {
            return $query->where('offer_id', $promoId);
        });
    }

    /*public function getDoctorTable($promoId)
    {
        return Laratables::recordsOf(PromoCode_Doctor::class, function ($query) use ($promoId) {
            return $query->where('promocodes_id', $promoId);
        });
    }*/

    public function createOffer($request)
    {
        $offer = Offer::create($request);
        return $offer;
    }

    public function updateOffer($offer, $request)
    {
        $offer = $offer->update($request);
        return $offer;
    }

    public function saveCouponBranchs($offerId, $branchsIds, $provider_id)
    {
        if (count($branchsIds) > 0) {
            foreach ($branchsIds as $id) {
                OfferBranch::Create([
                    'offer_id' => $offerId,
                    'branch_id' => $id,
                ]);
            }
        } else { //save all branches  and doctors for that provider

            $branchs = Provider::where('provider_id', $provider_id)->pluck('id');
            if (count($branchs) > 0) {
                foreach ($branchs as $id) {
                    OfferBranch::Create([
                        'offer_id' => $offerId,
                        'branch_id' => $id,
                    ]);

                    /*$branch = Provider::find($id);
                    $doctorIds = $branch->doctors()->pluck('id');

                    foreach ($doctorIds as $doctor_id) {
                        PromoCode_Doctor::Create([
                            'promocodes_id' => $offerId,
                            'doctor_id' => $doctor_id,
                        ]);
                    }*/
                }
            }
        }
    }

    /*    public function saveCouponDoctors($pormoCodeId, $doctorsIds, $provider_id)
        {
            if (count($doctorsIds) > 0) {
                foreach ($doctorsIds as $id) {
                    PromoCode_Doctor::Create([
                        'promocodes_id' => $pormoCodeId,
                        'doctor_id' => $id,
                    ]);
                }
            } else {

                $branches = PromoCode_branch::where('promocodes_id', $pormoCodeId)->pluck('branch_id');

                if (count($branches) > 0) {
                    foreach ($branches as $branche_id) {
                        $doctors = Doctor::where('provider_id', $branche_id)->pluck('id');
                        foreach ($doctors as $doctor_id) {
                            PromoCode_Doctor::Create([
                                'promocodes_id' => $pormoCodeId,
                                'doctor_id' => $doctor_id,
                            ]);
                        }
                    }
                }
            }
        }*/

    public function getAllPaymentMethodWithSelected($offer = null)
    {
        if ($offer != null) {
            return PaymentMethod::where('status', 1)->select('id', 'name_ar', DB::raw('IF ((SELECT count(id) FROM offer_payment_methods WHERE offer_payment_methods.offer_id = ' . $offer->id . ' AND offer_payment_methods.payment_method_id = payment_methods.id) > 0, 1, 0) as selected'))->get();
        } else {
            return PaymentMethod::where('status', 1)->select('id', 'name_ar', DB::raw('0 as selected'))->get();
        }
    }

    public function getCategoriesWithCurrentOfferSelected($offer = null)
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

    public function getAllOfferCategoriesCollection()
    {
        return OfferCategory::parentCategories()->select('id', 'name_ar', 'hastimer')->get();
    }

    public function getActiveUsersWithCurrentOfferSelected($offer = null)
    {
        if ($offer != null) {
            return User::select('id',
                'name',
                DB::raw('IF ((SELECT count(id) FROM user_offers WHERE user_offers.offer_id = ' . $offer->id . ' AND user_offers.user_id = users.id) > 0, 1, 0) as selected'))->get();
        } else {
            return User::select('id', 'name', DB::raw('0 as selected'))->get();
        }
    }


}
