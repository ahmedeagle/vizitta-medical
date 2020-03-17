<?php

namespace App\Http\Resources\CPanel;

use Illuminate\Http\Resources\Json\JsonResource;

class UserRecordsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'day_date' => $this->day_date,
            'summary' => $this->summary,
            'specification' => app()->getLocale() == 'ar' ? $this->specification->name_ar : $this->specification->name_en,
            'attachments' => $this->attachments,
        ];
    }

}
