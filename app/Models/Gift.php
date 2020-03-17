<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use DB;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Gift  extends Model
{
    protected $table = 'gifts';
    public $timestamps = true;
    protected $fillable = ['branch_id', 'title','amount','created_at','updated_at'];


    public function provider(){
        return $this -> belongsTo('App\Models\Provider','branch_id','id');
    }
    public function user(){
        return $this -> belongsToMany('App\Models\User','users_gifts','gift_id','user_id');
    }
}
