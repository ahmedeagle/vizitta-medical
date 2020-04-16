<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    public $timestamps = true;
    protected $fillable = ['chatable_type', 'chatable_id', 'consulting_id', 'message_no', 'type', 'importance', 'solved', 'created_at'];
    protected $hidden = ['updated_at'];


    public function scopeCreatedAt()
    {
        return Carbon::parse($this->created_at)->format('H:i Y-m-d');
    }

    public function chatable()
    {
        return $this->morphTo();
    }

    public function replies()
    {
        return $this->hasMany('App\Models\ChatReplay', 'chat_id', 'id');
    }

    function messages()
    {
        return $this->hasMany('App\Models\ChatReplay', 'chat_id', 'id');
    }


}


