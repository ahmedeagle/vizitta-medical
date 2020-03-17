<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    public $timestamps  = true;
    protected $fillable = ['title', 'actor_id','actor_type','message_no', 'type', 'importance' ,'solved'];
    protected $hidden   = ['actor_id', 'updated_at'];

    public static function laratablesCustomUserAction($message)
    {
        return view('message.user.actions', compact('message'))->render();
    }

    public static function laratablesCustomProviderAction($message)
    {
        return view('message.provider.actions', compact('message'))->render();
    }

    public function laratablesCreatedAt()
    {
        return Carbon::parse($this->created_at)->format('H:i Y-m-d');
    }

    public function user(){
        return $this -> belongsTo('App\Models\User','actor_id','id');
    }


    public function provider(){
        return $this -> belongsTo('App\Models\Provider','actor_id','id');
    }

    public function laratablesType()
    {
        if($this->type == 1)
            return trans('messages.Inquiry');

        else if($this->type == 2)
            return trans('messages.Suggestion');

        else if($this->type == 3)
            return trans('messages.Complaint');

        else if($this->type == 4)
            return trans('messages.Others');

        return "";
    }

    public function laratablesImportance( )
    {
        if($this->importance == 1)
            return trans('messages.Quick');

        else if($this->importance == 2)
            return trans('messages.Normal');

        return "";
    }



    public function replays()
    {
        return $this->hasMany('App\Models\Replay', 'ticket_id', 'id');
    }

    public function ticketable()
    {
        return $this->morphTo();
    }

   function messages()
    {
        return $this->hasMany('App\Models\Replay', 'ticket_id', 'id');
    }





}
