<?php

namespace App\Traits\CPanel;

use App\Models\CustomPage;
use App\Models\OfferCategory;
use DB;

trait BannerTrait
{
      public function getAllCategories(){
          return OfferCategory::select('id','name_'.app()->getLocale().' as name')-> get();
      }
}
