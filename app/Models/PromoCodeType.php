<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PromoCodeType extends Model
{
    protected $table = 'promocodes_branches';
    public $timestamps = true;

    protected $fillable = ['promocodes_id','branch_id','status'];



   public function PromoCodes(){

   	   return $this -> hasMany('App\Models\PromoCode','coupons_type_id');
   }

     

}
