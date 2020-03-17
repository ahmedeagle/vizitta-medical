<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'admin_notifications';
    public $timestamps = true;

    protected $fillable = ['title', 'content','type','created_at'];
    protected  $hidden =['updated_at'];

    public function recievers()
    {
        return $this->hasMany('App\Models\Reciever', 'notification_id');
    }

    public static function laratablesCustomAction($notify)
    {

        return view('notifications.actions', compact('notify'))->render();
    }

}
