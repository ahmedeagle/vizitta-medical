<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Offer extends Model
{
    protected $table = 'offers';
    public $timestamps = true;
    protected $casts = ['application_percentage' => 'integer'];
    protected $forcedNullNumbers = ['application_percentage', 'featured'];
    protected $fillable = [
        'code', 'specification_id', 'category_id', 'title_ar', 'title_en', 'discount', 'price', 'available_count', 'available_count_type',
        'status', 'started_at', 'expired_at', 'provider_id', 'photo', 'application_percentage',
        'featured', 'paid_coupon_percentage', 'general', 'visits', 'uses', 'price_after_discount', 'gender', 'device_type'
    ];
    protected $hidden = ['visits', 'uses','category_id','coupons_type_id'];

    public function offerBranches()
    {
        return $this->hasMany('App\Models\OfferBranch', 'offer_id');
    }

    public function reservations()
    {
        return $this->hasMany('App\Models\Reservation', 'promocode_id');
    }

    public function provider() //branch
    {
        return $this->belongsTo('App\Models\Provider', 'provider_id');
    }

    public static function laratablesCustomAction($offer)
    {
        return view('offers.actions', compact('offer'))->render();
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

    public function getPhotoAttribute($val)
    {
        return ($val != "" ? asset($val) : "");
    }

    //observaer is better
    public function scopeDeleteWithRelations()
    {
        //can use observer instead
        $this->PromoCodeBranches()->delete();
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
        })
            ->whereDate('expired_at', '>=', Carbon::now()->format('Y-m-d'))
            ->whereDate('started_at', '<=', Carbon::now()->format('Y-m-d'));
    }

    public function scopeSelection($query)
    {
        $query->select('id', 'provider_id', 'photo', 'featured', 'category_id', 'coupons_type_id', 'title_' . app()->getLocale() . ' as title', 'price', 'price_after_discount', 'discount', 'code', 'started_at', 'expired_at', 'application_percentage', 'visits', 'uses');
    }

    public function getDiscountAttribute($val)
    {
        return ($val != null ? $val : 0);
    }

    public function scopeSelection2($query)
    {
        $query->select('id', 'coupons_type_id', 'title_' . app()->getLocale() . ' as title', 'photo', 'code', 'price', 'price_after_discount', 'discount', 'visits', 'uses');
    }

    public function scopeFeatured($query)
    {
        // featured  ---> 2   not featured   ---> 1
        return $query->where('featured', 2);
    }

    public function getApplicationPercentageAttribute($val)
    {
        return ($val != null ? $val : 0);
    }

    public function getCodeAttribute($val)
    {
        return ($val != null ? $val : '0');
    }

    public function getPriceAfterDiscountAttribute($val)
    {
        return ($val != null ? $val : '');
    }

    public function payments()
    {
        return $this->hasMany('\App\Models\Payment', 'offer_id');
    }

     public function specification()
    {
        return $this->belongsTo('App\Models\Specification', 'specification_id');
    }

    public function categories()
    {
        return $this->belongsToMany('App\Models\OfferCategory', 'offers_categories_pivot', 'offer_id', 'category_id', 'id', 'id');
    }


    public function users()
    {
        return $this->belongsToMany('App\Models\User', 'user_offers', 'offer_id', 'user_id');
    }

    public function getPriceAfterDiscount($val)
    {
        return ($val !== null ? $val : '');
    }

    public function contents()
    {
        return $this->hasMany('\App\Models\OfferContent', 'offer_id');
    }

    public function branchTimes()
    {
        return $this->belongsToMany('App\Models\Provider', 'offers_branches_times_old', 'offer_id', 'branch_id')
            ->withPivot('day_code', 'duration', 'start_from', 'end_to');
    }

    public function offerBranchTimes()
    {
        return $this->hasMany('App\Models\OfferTime', 'offer_id');
    }

    public function times()
    {
        return $this->hasMany('App\Models\OfferTime', 'offer_id', 'id');
    }

    public function paymentMethods()
    {
        return $this->belongsToMany('App\Models\PaymentMethod', 'offer_payment_methods', 'offer_id', 'payment_method_id')
            ->using('App\Models\OfferPaymentMethod')->withPivot('payment_amount_type', 'payment_amount');
    }


}
