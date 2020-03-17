<?php

namespace App\Traits\Dashboard;

use App\Models\PromoCode;
use App\Models\PromoCode_branch;
use App\Models\PromoCode_Doctor;
use App\Models\Provider;
use App\Models\Doctor;
use App\Models\User;
use Freshbitsweb\Laratables\Laratables;
use Illuminate\Http\Request;
use function foo\func;

trait PromoCodeTrait
{
    public function getPromoCodeById($id)
    {
        return PromoCode::find($id);
    }

    public function getPromoCodeByIdWithRelation($id)
    {
        $promoCode = PromoCode::with('promocodebranches', 'Promocodedoctors', 'reservations')->find($id);
        if (!$promoCode) {
            return null;
        }
        return $promoCode;
    }

    public function getPromoCodeByIdWithRelations($id)
    {
        $promoCode = PromoCode::with(['promocodebranches' => function ($q) {
            $q->wherehas('branch');
            $q->with(['branch' => function ($qq) {
                $qq->select('id', 'name_ar', 'provider_id');
            }]);
        }, 'Promocodedoctors' => function ($q) {
            $q->wherehas('doctor');
            $q->with(['doctor' => function ($qq) {
                $qq->select('id', 'name_ar');
            }]);
        },'reservations'])->find($id);
        if (!$promoCode) {
            return null;
        }
        return $promoCode;
    }

    public function getAll()
    {
        return Laratables::recordsOf(PromoCode::class, function ($query) {
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
        return Laratables::recordsOf(PromoCode_branch::class, function ($query) use ($promoId) {
            return $query->where('promocodes_id', $promoId);
        });
    }

    public function getDoctorTable($promoId)
    {
        return Laratables::recordsOf(PromoCode_Doctor::class, function ($query) use ($promoId) {
            return $query->where('promocodes_id', $promoId);
        });
    }

    public function createPromoCode($request)
    {
        $promoCode = PromoCode::create($request);
        return $promoCode;
    }

    public function updatePromoCode($promoCode, $request)
    {
        $promoCode = $promoCode->update($request);
        return $promoCode;
    }

    public function saveCouponBranchs($pormoCodeId, $branchsIds, $provider_id)
    {
        if (count($branchsIds) > 0) {
            foreach ($branchsIds as $id) {
                PromoCode_branch::Create([
                    'promocodes_id' => $pormoCodeId,
                    'branch_id' => $id,
                ]);
            }
        } else { //save all branches  and doctors for that provider

            $branchs = Provider::where('provider_id', $provider_id)->pluck('id');
            if (count($branchs) > 0) {
                foreach ($branchs as $id) {
                    PromoCode_branch::Create([
                        'promocodes_id' => $pormoCodeId,
                        'branch_id' => $id,
                    ]);

                    $branch = Provider::find($id);
                    $doctorIds = $branch->doctors()->pluck('id');

                    foreach ($doctorIds as $doctor_id) {
                        PromoCode_Doctor::Create([
                            'promocodes_id' => $pormoCodeId,
                            'doctor_id' => $doctor_id,
                        ]);
                    }
                }
            }
        }
    }

    public function saveCouponDoctors($pormoCodeId, $doctorsIds, $provider_id)
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
    }


}
