<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $table = 'banners';
    public $timestamps = true;
    protected $fillable = ['photo', 'bannerable_type', 'bannerable_id', 'subCategory_id','external_link','lft','rgt','depth'];
    protected $hidden = ['created_at', 'updated_at', 'bannerable_type', 'bannerable_id'];

    public function getPhotoAttribute($val)
    {
        return ($val != "" ? asset($val) : "");
    }

    public function getExternalLinkAttribute($val)
    {
        return ($val != null ? $val : "");
    }

    public function bannerable()
    {
        return $this->morphTo();
    }

    public function scopeSelection($query){

        return $query -> select('id', 'photo', 'bannerable_type', 'bannerable_id');
    }


    public function getTypeIdAttribute($val){
        return ($val !== null ? $val : "");
    }
}
