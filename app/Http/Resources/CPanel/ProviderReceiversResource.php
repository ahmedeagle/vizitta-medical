<?php

namespace App\Http\Resources\CPanel;

use Illuminate\Http\Resources\Json\JsonResource;

class ProviderReceiversResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => app()->getLocale() == 'ar' ? $this->provider->name_ar : $this->provider->name_en,
        ];
    }

}
