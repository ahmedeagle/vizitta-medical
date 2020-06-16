<?php

namespace App\Http\Resources;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class SingleNotificationResource extends JsonResource
{
    public function toArray($request)
    {

        $result = [
            'id' => $this->id,
            'title' => $this->notification->title,
            'content' => $this->notification->content,
            'photo' => $this->notification->photo,
            'seen' => $this->seen,
            'created_at' => $this->created_at
        ];


        return $result;
    }

}
