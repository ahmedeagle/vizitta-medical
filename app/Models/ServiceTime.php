<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ServiceTime extends Model
{
    protected $table = 'service_times';
    public $timestamps = true;

    protected $fillable = ['day_name', 'day_code', 'from_time', 'to_time', 'service_id', 'reservation_period', 'provider_id', 'branch_id', 'order'];

    protected $forcedNullStrings = ['reservation_period'];
    protected $hidden = ['service_id', 'updated_at', 'created_at', 'provider_id', 'reservation_period', 'order'];
    protected $appends = ['time_duration'];


}
