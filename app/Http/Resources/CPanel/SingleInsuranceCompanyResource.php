<?php

namespace App\Http\Resources\CPanel;

use Illuminate\Http\Resources\Json\JsonResource;

class SingleInsuranceCompanyResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => app()->getLocale() == 'ar' ? $this->name_ar : $this->name_en,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'status' => [
                'name' => $this->status == '1' ? __('main.active') : __('main.not_active'),
                'value' => $this->status,
            ],
            'image' => $this->image,
        ];
    }

}
