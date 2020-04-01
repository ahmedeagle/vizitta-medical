<?php

namespace App\Models;

use App\Observers\PromoCodeCategoryObserver;
use Illuminate\Database\Eloquent\Model;

class PromoCodeCategory extends Model
{
    protected $table = 'promocodes_categories';
    public $timestamps = true;

    protected $fillable = ['name_en', 'name_ar', 'photo', 'hastimer', 'hours', 'minutes', 'seconds', 'timerexpired', 'status'];

    protected $hidden = ['created_at', 'updated_at', 'hastimer'];


    protected static function boot()
    {
        parent::boot();
        PromoCodeCategory::observe(PromoCodeCategoryObserver::class);
    }

    public static function laratablesCustomAction($category)
    {
        return view('promoCategories.actions', compact('category'))->render();
    }

    /*public function user_attachments()
    {
        return $this->belongsTo('App\Models\UserAttachment', 'category_id');
    }*/
    public function laratablesHastimer()
    {
        return ($this->hastimer ? 'مفعّل' : 'غير مفعّل');
    }

    /*public function promoCodes()
    {
        return $this->hasMany('App\Models\PromoCode', 'category_id', 'id');
    }*/

    public function promoCodes()
    {
        return $this->belongsToMany('App\Models\PromoCode', 'promocode_promocodescategory', 'category_id', 'promocode_id', 'id', 'id');
    }

    public function getPhotoAttribute($val)
    {
        return ($val != "" ? asset($val) : "");
    }

    public function scopeWithOutTimer($query)
    {
        return $query->where('hastimer', 0);
    }

    public function scopeWithTimer($query)
    {
        return $query->where('hastimer', 1)->where('timerexpired', 0);
    }

    public function getHoursAttribute($val)
    {
        return ($val !== null ? $val : "");
    }

    public function getMinutesAttribute($val)
    {
        return ($val !== null ? $val : "");
    }

    public function getSecondsAttribute($val)
    {
        return ($val !== null ? $val : "");
    }


    public function laratablesStatus()
    {
        return ($this->status ? 'مفعّل' : 'غير مفعّل');
    }

    public function  scopeActive($query){
        $query -> where('status',1);
    }


}
