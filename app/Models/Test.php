<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    protected $table = 'for_test';
     public $timestamps = false;

    protected $fillable = [
        'user_id','provider_id'
    ];

}
