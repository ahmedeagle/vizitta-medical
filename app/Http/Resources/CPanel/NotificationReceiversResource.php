<?php

namespace App\Http\Resources\CPanel;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationReceiversResource extends JsonResource
{

    public function toArray($request)
    {
        if ($this->type == 'users')
            return UserReceiversResource::collection($this->whenLoaded('recievers'));
        else
            return ProviderReceiversResource::collection($this->whenLoaded('recievers'));
    }
}
