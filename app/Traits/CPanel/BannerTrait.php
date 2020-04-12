<?php

namespace App\Traits\CPanel;

use App\Models\CustomPage;
use App\Models\OfferCategory;
use DB;

trait BannerTrait
{
    public function getAllCategories()
    {
        return OfferCategory::whereNull('parent_id')->select('id', 'name_' . app()->getLocale() . ' as name')->get();
    }

    public function getSubCategoriesByCatId($categoryId)
    {
        return OfferCategory::whereNotNull('parent_id')
            ->where('parent_id', $categoryId)
            ->select('id', 'name_' . app()->getLocale() . ' as name')
            ->get();
    }
}
