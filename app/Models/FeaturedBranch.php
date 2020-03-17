<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use DB;
use Tymon\JWTAuth\Contracts\JWTSubject;

class FeaturedBranch  extends Model
{
    protected $table = 'featuredbranches';
    public $timestamps = true;
    protected $fillable = ['branch_id', 'duration','created_at','updated_at'];


    public function provider(){
        return $this -> belongsTo('App\Models\Provider','branch_id','id');
    }
}
