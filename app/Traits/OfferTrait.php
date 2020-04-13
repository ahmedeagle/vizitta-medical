<?php

namespace App\Traits;

use App\Models\OfferCategory;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait OfferTrait
{
    public function getOfferCatsV2()
    {
        $category = OfferCategory::query();
        return $category
            ->withOutTimer()
            ->select('id',
                DB::raw('name_' . $this->getCurrentLang() . ' as name'), 'photo',
                'hours',
                'minutes',
                'seconds')
            ->orderBy('lft')
            ->get();
    }

    public function getTimerOfferCategoriesV2()
    {
        $category = OfferCategory::query();
        return $category
            ->withTimer()
            ->select('id',
                DB::raw('name_' . $this->getCurrentLanguage() . ' as name'), 'photo',
                'hours',
                'minutes',
                'seconds')
            ->orderBy('lft')
            ->get();
    }

    public function getCurrentLanguage()
    {
        return app()->getLocale();
    }

    public function getUserOffersReservations($userId)
    {

        return Reservation::with(['commentReport' => function ($q) use ($userId) {
            $q->where('user_id', $userId);
        }, 'offer' => function ($q) {
            $q->select('id', 'specification_id',
                DB::raw('title_' . app()->getLocale() . ' as title'),
                DB::raw('abbreviation_' . app()->getLocale() . ' as abbreviation'),
                DB::raw('information_' . app()->getLocale() . ' as information'))
                ->with(['specification' => function ($qq) {
                    $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                }]);
        }, 'provider' => function ($que) {
            $que->select('id', 'provider_id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'paymentMethod' => function ($qu) {
                $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }])
            ->where('user_id', $userId)
            ->orderBy('day_date')
            ->orderBy('order')
            ->paginate(PAGINATION_COUNT);
    }


}
