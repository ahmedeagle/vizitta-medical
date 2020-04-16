<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ChatReplay extends Model
{
    protected $table = "chat_replies";
    public $timestamps = true;
    protected $fillable = ['message', 'seenByDoctor','message_type', 'hasReplayFromDoctor', 'seenByUser', 'FromUser', 'chat_id', 'created_at'];
    protected $hidden = ['updated_at'];

    function ticket()
    {
        return $this->belongsTo('App\Models\Chat', 'chat_id', 'id');
    }



}
