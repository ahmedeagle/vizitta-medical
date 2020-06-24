<?php

namespace App\Traits\CPanel;

use App\Models\Doctor;
use App\Models\Message;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\Provider;
use App\Models\ProviderType;
use App\Models\Reservation;
use Carbon\Carbon;
use DateTime;
use DB;
use Illuminate\Support\Facades\Auth;

trait ServicesTrait
{

    public function getServices($id = null)
    {
        $services = Service::query();
        $services = $services->with(['specification' => function ($q1) {
            $q1->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'branch' => function ($q2) {
            $q2->select('id', DB::raw('name_' . app()->getLocale() . ' as name'),'provider_id');
        }, 'provider' => function ($q2) {
            $q2->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'types' => function ($q3) {
            $q3->select('services_type.id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }
        ]);

        if ($id != null)
            $services = $services->where('id', $id);
        $services = $services->select(
            'id',
            DB::raw('title_' . $this->getCurrentLang() . ' as title'),
            DB::raw('information_' . $this->getCurrentLang() . ' as information')
            ,'specification_id',
            'provider_id', 'branch_id',
            'rate', 'price','clinic_price',
            'home_price', 'home_price_duration',
            'clinic_price_duration', 'status', 'reservation_period as clinic_reservation_period'
        );

        if ($id != null)
            return $services->first();
        else
            return $services->orderBy('id','DESC')->paginate(PAGINATION_COUNT);
    }


    public function getServicesForEdit($id = null)
    {
        $services = Service::query();
        $services = $services->with(['specification' => function ($q1) {
            $q1->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'branch' => function ($q2) {
            $q2->select('id', DB::raw('name_' . app()->getLocale() . ' as name'),'provider_id');
        }, 'provider' => function ($q2) {
            $q2->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'types','paymentMethods'
        ]);


        if ($id != null)
            $services = $services->where('id', $id);
        $services = $services->select(
            'id',
            'title_ar',
            'title_en',
            'information_ar',
            'information_en',
            'specification_id',
            'provider_id',
            'branch_id',
            'rate',
            'price',
            'has_price',
            'home_price',
            'clinic_price',
            'home_price_duration',
            'clinic_price_duration',
            'status',
           'reservation_period as clinic_reservation_period'
        );

        if ($id != null)
            return $services->first();
        else
            return $services->paginate(PAGINATION_COUNT);
    }

}
