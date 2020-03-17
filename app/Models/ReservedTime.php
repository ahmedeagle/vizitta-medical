<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservedTime extends Model
{
    protected $table = 'reserved_times';
    public $timestamps = false;

    protected $fillable = ['doctor_id', 'day_date'];

    public function doctor()
    {
        return $this->belongsTo('App\Models\Doctor', 'doctor_id')->select('id', 'name_' . app()->getLocale() . ' as name');
    }

}
