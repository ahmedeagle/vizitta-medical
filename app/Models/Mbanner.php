<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mbanner extends Model
{
    protected $table = 'mbanners';
    public $timestamps = true;
    protected $fillable = ['photo', 'bannerable_type', 'bannerable_id'];
    protected $hidden = ['created_at', 'updated_at', 'bannerable_type', 'bannerable_id'];

    public function getPhotoAttribute($val)
    {
        return ($val != "" ? asset($val) : "");
    }

    public function bannerable()
    {
        return $this->morphTo();
    }

    public function getTypeIdAttribute($val){
        return ($val !== null ? $val : "");
    }

}
