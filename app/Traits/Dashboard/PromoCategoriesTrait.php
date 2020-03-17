<?php

namespace App\Traits\Dashboard;

use Freshbitsweb\Laratables\Laratables;
use App\Models\PromoCodeCategory;

trait PromoCategoriesTrait
{
    public function getPromoCodeCategoryById($id)
    {
        return PromoCodeCategory::find($id);
    }

    public function getAllPromoCodeCategories()
    {

        return Laratables::recordsOf(PromoCodeCategory::class, function ($query) {
            return $query->orderBy('lft');
        });
     }

    public function createPromoCodeCategory($request)
    {
        $category = PromoCodeCategory::create($request);
        return $category;
    }

    public function updatePromoCodeCategory($promoCodeCategory, $request)
    {
        $promoCodeCategory = $promoCodeCategory->update($request);
        return $promoCodeCategory;
    }

    public static function getPromoCodeCategoryNameById($promoCodeCategory_id)
    {
        $promoCodeCategory_id = PromoCodeCategory::find($promoCodeCategory_id);
        if (!$promoCodeCategory_id)
            return '--';
        return $promoCodeCategory_id->name_ar;
    }

}
