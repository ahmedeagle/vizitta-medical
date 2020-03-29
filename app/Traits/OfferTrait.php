<?php

namespace App\Traits;

use App\Models\OfferCategory;
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

}
