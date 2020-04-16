<?php

namespace App\Traits\Dashboard;

use App\Models\Doctor;
use App\Models\Provider;
use App\Models\Reservation;
use Carbon\Carbon;
use Freshbitsweb\Laratables\Laratables;
use DB;
trait GlobalOfferTrait
{
    public function getReservationById($id)
    {
        return Reservation::find($id);
    }

    public function getReservationByNoWihRelation($reservation_id)
    {

        return Reservation::with(['commentReport', 'offer' => function ($g) {
            $g->select('id', DB::raw('title_' . app()->getLocale() . ' as title'), 'photo');
        }, 'rejectionResoan' => function ($rs) {
            $rs->select('id', DB::raw('name_' . app()->getLocale() . ' as rejection_reason'));
        }, 'paymentMethod' => function ($qu) {
            $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }
            , 'user' => function ($q) {
                $q->select('id', 'name', 'mobile', 'insurance_company_id', 'insurance_image', 'mobile')->with(['insuranceCompany' => function ($qu) {
                    $qu->select('id', 'image', DB::raw('name_' . app()->getLocale() . ' as name'));
                }]);
            }, 'provider' => function ($qq) {
                $qq->whereNotNull('provider_id')->select('id', DB::raw('name_' . app()->getLocale() . ' as name'))
                    ->with(['provider' => function ($g) {
                        $g->select('id', 'type_id', DB::raw('name_' . app()->getLocale() . ' as name'))
                            ->with(['type' => function ($gu) {
                                $gu->select('id', 'type_id', DB::raw('name_' . app()->getLocale() . ' as name'));
                            }]);
                    }]);
            }])->where('id',$reservation_id)
            ->first();
    }


}
