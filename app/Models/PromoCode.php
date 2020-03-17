<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PromoCode extends Model
{
    protected $table = 'promocodes';
    public $timestamps = true;
    protected $casts = ['application_percentage' => 'integer'];
    protected $forcedNullNumbers = ['application_percentage', 'featured'];
    protected $fillable = ['code', 'specification_id', 'category_id', 'title_ar', 'title_en', 'discount', 'price', 'available_count', 'status', 'expired_at', 'provider_id', 'coupons_type_id', 'features_ar', 'features_en', 'photo', 'application_percentage', 'featured', 'paid_coupon_percentage', 'offer_id', 'general','visits','uses','price_after_discount'];
    protected $hidden =['visits','uses'];

    public function PromoCodeType()
    {
        return $this->belongsTo('App\Models\PromoCodeType', 'coupons_type_id');
    }

    public function promocodebranches()
    {

        return $this->hasMany('App\Models\PromoCode_branch', 'promocodes_id');
    }

    public function promocodedoctors()
    {

        return $this->hasMany('App\Models\PromoCode_Doctor', 'promocodes_id');
    }

    public function reservations()
    {
        return $this->hasMany('App\Models\Reservation', 'promocode_id');
    }

    public function provider() //branch
    {
        return $this->belongsTo('App\Models\Provider', 'provider_id');
    }


    public static function laratablesCustomAction($promoCode)
    {
        return view('promoCode.actions', compact('promoCode'))->render();
    }

    public function laratablesCouponsTypeId()
    {

        return ($this->coupons_type_id == 1 ? 'كوبون خصم' : 'كوبون مدفوع');
    }

    public function laratablesApplicationPercentage()
    {

        return ($this->coupons_type_id == 1 ? $this->application_percentage : '--');
    }


    public function laratablesStatus()
    {
        return ($this->status ? 'مفعّل' : 'غير مفعّل');
    }

    public function laratablesFeatured()
    {
        return ($this->featured == 1 ? ' غير مميز ' : ' مميز ');
    }

    public function laratablesCoupons_type_id()
    {
        return ($this->coupons_type_id == 1 ? 'خصم ' : 'كوبون ');
    }


    public function getPhotoAttribute($val)
    {
        return ($val != "" ? asset($val) : "");
    }

    //observaer is better
    public function scopeDeleteWithRelations()
    {
        //can use observer instead
        $this->PromoCodeBranches()->delete();
        $this->PromoCodeDoctors()->delete();
        $this->delete();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeValid($query)
    {
        $query->where(function ($q) {
            $q->where('available_count', '>', 0)->orWhereHas('users');
        })->whereDate('expired_at', '>=', Carbon::now()->format('Y-m-d'));
    }

    public function scopeSelection($query)
    {
        $query->select('id', 'provider_id', 'photo', 'featured', 'category_id', 'coupons_type_id', 'title_' . app()->getLocale() . ' as title', 'price','price_after_discount', 'discount', 'code', 'features_' . app()->getLocale() . ' as features', 'expired_at', 'application_percentage','visits','uses');
    }

    public function getDiscountAttribute($val)
    {
        return ($val != null ? $val : 0);
    }

    public function scopeSelection2($query)
    {
        $query->select('id', 'coupons_type_id', 'title_' . app()->getLocale() . ' as title', 'photo', 'code', 'price','price_after_discount', 'discount','visits','uses');
    }

    public function scopeFeatured($query)
    {
        // featured  ---> 2   not featured   ---> 1
        return $query->where('featured', 2);
    }

    public
    function getApplicationPercentageAttribute($val)
    {
        return ($val != null ? $val : 0);
    }

    public
    function getCodeAttribute($val)
    {
        return ($val != null ? $val : '0');
    }

    public function  getPriceAfterDiscountAttribute($val){
        return ($val != null ? $val : '');
    }
    public
    function payments()
    {
        return $this->hasMany('\App\Models\Payment', 'offer_id');
    }

//removed coupon now has its  own category
    public
    function specification()
    {
        return $this->belongsTo('App\Models\Specification', 'specification_id');
    }

    /* public
     function category()
     {
         return $this->belongsTo('App\Models\PromoCodeCategory', 'category_id');
     }*/

    public function categories()
    {
        return $this->belongsToMany('App\Models\PromoCodeCategory', 'promocode_promocodescategory', 'promocode_id', 'category_id', 'id', 'id');
    }

    public function users()
    {
        return $this->belongsToMany('App\Models\User', 'user_promocode', 'promocode_id', 'user_id');
    }


    public function getPriceAfterDiscount($val){
        return ($val !== null ? $val : '');
    }

//    protected $hidden = ['created_at', 'updated_at', 'available_count', 'status', 'expired_at'];

    /*

    public function doctor()
    {
        return $this->belongsTo('App\Models\Doctor', 'doctor_id');
    }

    public function reservations()
    {
        return $this->hasMany('App\Models\Reservation', 'promocode_id');
    }

    public static function laratablesCustomAction($promoCode)
    {
        return view('promoCode.actions', compact('promoCode'))->render();
    }

    public function laratablesStatus( )
    {
        return ($this->status ? 'مفعّل' : 'غير مفعّل');
    }

    public function laratablesPublic( )
    {
        return ($this->public ? 'عام' : 'خاص');
    }*/

}
