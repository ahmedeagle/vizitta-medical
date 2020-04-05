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
            $q2->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'provider' => function ($q2) {
            $q2->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'serviceType' => function ($q3) {
            $q3->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }
        ]);

        if ($id != null)
            $services = $services->where('id', $id);
        $services = $services->select(
            'id',
            DB::raw('title_' . $this->getCurrentLang() . ' as title'),
            DB::raw('information_' . $this->getCurrentLang() . ' as information')
            , 'specification_id', 'provider_id', 'branch_id', 'type', 'rate', 'price', 'status', 'reservation_period'
        );

        if ($id != null)
            return $services->first();
        else
            return $services->paginate(PAGINATION_COUNT);
    }


    public function getServicesForEdit($id = null)
    {
        $services = Service::query();
        $services = $services->with(['specification' => function ($q1) {
            $q1->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'branch' => function ($q2) {
            $q2->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'provider' => function ($q2) {
            $q2->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'serviceType' => function ($q3) {
            $q3->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }
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
            'type',
            'rate',
            'price',
            'status',
            'reservation_period'
        );

        if ($id != null)
            return $services->first();
        else
            return $services->paginate(PAGINATION_COUNT);
    }

}
