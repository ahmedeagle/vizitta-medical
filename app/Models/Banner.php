<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $table = 'banners';
    public $timestamps = true;
    protected $fillable = ['photo', 'bannerable_type', 'bannerable_id'];
    protected $hidden = ['created_at', 'updated_at', 'bannerable_type', 'bannerable_id'];
}
