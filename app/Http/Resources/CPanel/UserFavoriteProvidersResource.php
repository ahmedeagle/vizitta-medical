<?php

namespace App\Http\Resources\CPanel;

use Illuminate\Http\Resources\Json\JsonResource;

class UserFavoriteProvidersResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'provider_id' => $this->provider_id,
            'provider' => app()->getLocale() == 'ar' ? $this->provider->name_ar : $this->provider->name_en,
        ];
    }

}
