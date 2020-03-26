<?php

namespace App\Traits;
use App\Models\OfferCategory;
use Illuminate\Support\Facades\DB;

trait OfferTrait
{
    public function getPromoCategoriesV2()
    {
        $category = PromoCodeCategory::query();
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

    public function getTimerPromoCategoriesV2()
    {
        $category = PromoCodeCategory::query();
        return $category
            ->withTimer()
            ->select('id',
                DB::raw('name_' . $this->getCurrentLang() . ' as name'), 'photo',
                'hours',
                'minutes',
                'seconds')
            ->orderBy('lft')
            ->get();
    }

}
