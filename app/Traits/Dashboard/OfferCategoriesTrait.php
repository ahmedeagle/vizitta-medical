<?php

namespace App\Traits\Dashboard;

use Freshbitsweb\Laratables\Laratables;
use App\Models\OfferCategory;

trait OfferCategoriesTrait
{
    public function getOfferCategoryById($id)
    {
        return OfferCategory::find($id);
    }

    public function getAllOfferCategories()
    {

        return Laratables::recordsOf(OfferCategory::class, function ($query) {
            return $query->orderBy('lft');
        });
    }

    public function createOfferCategory($request)
    {
        $category = OfferCategory::create($request);
        return $category;
    }

    public function updateOfferCategory($offerCategory, $request)
    {
        $offerCat = $offerCategory->update($request);
        return $offerCat;
    }

    public static function getOfferCategoryNameById($offerCategoryId)
    {
        $offerCatId = OfferCategory::find($offerCategoryId);
        if (!$offerCatId)
            return '--';
        return $offerCatId->name_ar;
    }

}
