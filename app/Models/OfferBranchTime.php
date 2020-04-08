<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class OfferBranchTime extends Model
{
    protected $table = 'offers_branches_times';
    public $timestamps = true;

    protected $fillable = ['day_name', 'day_code', 'from_time', 'to_time', 'offer_id', 'reservation_period','branch_id', 'order',' offers_branches_times'];

    protected $hidden = ['offer_id', 'updated_at', 'created_at', 'branch_id','reservation_period', 'order'];

    protected $appends = ['time_duration'];

    public function getDayNameAttribute($value)
    {
        return trans('messages.' . $value);
    }

    public function getTimeDurationAttribute(){
        return $this->reservation_period;
    }

}
