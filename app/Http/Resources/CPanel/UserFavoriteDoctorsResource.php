<?php

namespace App\Http\Resources\CPanel;

use Illuminate\Http\Resources\Json\JsonResource;

class UserFavoriteDoctorsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'doctor_id' => $this->doctor_id,
            'doctor' => app()->getLocale() == 'ar' ? $this->doctor->name_ar : $this->doctor->name_en,
        ];
    }

}
