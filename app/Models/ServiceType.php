<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    protected $table = 'services_type';
    public $timestamps = true;

    protected $fillable = ['name_en', 'name_ar'];

    protected $hidden = ['created_at', 'updated_at'];

}
