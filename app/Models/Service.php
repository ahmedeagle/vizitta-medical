<?php

namespace App\Models;

use App\Traits\DoctorTrait;
use App\Traits\GlobalTrait;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use  GlobalTrait;
    protected $table = 'services';
    public $timestamps = true;

    protected $fillable = ['title_ar', 'title_en', 'information_ar', 'information_en', 'provider_id', 'branch_id', 'specification_id', 'price', 'status', 'type', 'rate', 'reservation_period', 'created_at', 'updated_at'];
    protected $hidden = ['specification_id', 'status', 'created_at', 'updated_at'];

    protected $forcedNullStrings = ['reservation_period'];
    protected $casts = [
        'status' => 'integer',
    ];
    protected $appends = ['available_time', 'hide'];



}

