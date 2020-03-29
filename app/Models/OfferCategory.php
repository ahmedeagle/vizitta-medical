<?php

namespace App\Models;

use App\Observers\OfferCategoryObserver;
use Illuminate\Database\Eloquent\Model;

class OfferCategory extends Model
{
    protected $table = 'offers_categories';
    public $timestamps = true;

    protected $fillable = ['parent_id', 'name_en', 'name_ar', 'photo', 'hastimer', 'hours', 'minutes', 'seconds', 'timerexpired'];

    protected $hidden = ['created_at', 'updated_at', 'hastimer'];


    protected static function boot()
    {
        parent::boot();
        OfferCategory::observe(OfferCategoryObserver::class);
    }

    public static function laratablesCustomAction($category)
    {
        return view('offerCategories.actions', compact('category'))->render();
    }

    public function laratablesHastimer()
    {
        return ($this->hastimer ? 'مفعّل' : 'غير مفعّل');
    }

    /*public function  promoCodes(){
        return $this -> belongsToMany('App\Models\PromoCode','promocode_promocodescategory','category_id','promocode_id','id','id');
    }*/

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

    public function scopeParentCategories($query)
    {
        return $query->whereNull('parent_id');
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

    public function childCategories()
    {
        return $this->hasMany(OfferCategory::class, 'parent_id');
    }

    public function parentCategory()
    {
        return $this->belongsTo(OfferCategory::class, 'parent_id');
    }

}
