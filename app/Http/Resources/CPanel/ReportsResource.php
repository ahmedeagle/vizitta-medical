<?php

namespace App\Http\Resources\CPanel;

use Illuminate\Http\Resources\Json\JsonResource;

class ReportsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'reservation_no' => $this->reservation_no,
            'reporting_type' => app()->getLocale() == 'ar' ? $this->reportingType->name_ar : $this->reportingType->name_en,
            'user_name' => isset($this->user) ? $this->user->name : null,
            'provider_name' => isset($this->provider) ? (app()->getLocale() == 'ar' ? $this->provider->name_ar : $this->provider->name_en) : null,
            'created_at' => $this->created_at,
        ];
    }

}
