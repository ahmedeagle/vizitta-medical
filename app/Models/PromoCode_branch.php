<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PromoCode_branch extends Model
{
    protected $table = 'promocodes_branches';
    public $timestamps = true;

    protected $fillable = ['promocodes_id','branch_id'];
    protected $hidden = ['created_at','updated_at','status'];


   public function PromoCode(){

   	   return $this -> belongsTo('App\Models\PromoCode','promocodes_id');
   }

    public function branch(){

   	   return $this -> belongsTo('App\Models\Provider','branch_id','id');
   }


  public function laratablesStatus( )
    {
        return ($this->status ? 'مفعّل' : 'غير مفعّل');
    }


   public static function laratablesCustomAction($promoCode)
    {
        return view('promoCode.branches_actions', compact('promoCode'))->render();
    }
}
