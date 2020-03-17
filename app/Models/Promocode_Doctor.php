<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PromoCode_Doctor extends Model
{
    protected $table = 'promocodes_doctors';
    public $timestamps = true;

    protected $fillable = ['promocodes_id','doctor_id'];
     protected $hidden = ['created_at','updated_at','status'];

   public function PromoCode(){
   	   return $this -> belongsTo('App\Models\PromoCode','promocodes_id');
   }


    public function doctor(){
   	   return $this -> belongsTo('App\Models\Doctor','doctor_id');
   }

   public function laratablesStatus( )
    {
        return ($this->status ? 'مفعّل' : 'غير مفعّل');
    }
}
