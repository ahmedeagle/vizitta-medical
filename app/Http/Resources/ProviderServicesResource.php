<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProviderServicesResource extends ResourceCollection
{
    public function toArray($request)
    {
        $result['data'] = $this->collection->transform(function ($data) {
            return [
                'id' => $data->id,
                'title' => app()->getLocale() == 'ar' ? $data->title_ar : $data->title_en,
                'title_ar' => $data->title_ar,
                'title_en' => $data->title_en,
                'rate' => $data->rate,
                'price' => $data->price,
                'clinic_price_duration' => $data->clinic_price_duration,
                'home_price_duration' => $data->home_price_duration,
                'clinic_price' => $data->clinic_price,
                'home_price' => $data->home_price,
                'hide' => $data->hide,
                'type' => $data->types,
            ];
        });

        $result['pagination'] = [
            'total' => $this->total(),
            'count' => $this->count(),
            'per_page' => $this->perPage(),
            'current_page' => $this->currentPage(),
            'total_pages' => $this->lastPage()
        ];
        return $result;
    }

}
