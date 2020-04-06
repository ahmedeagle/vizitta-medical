<?php

namespace App\Http\Resources\CPanel;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OffersResource extends ResourceCollection
{
    public function toArray($request)
    {
        $result['data'] = $this->collection->transform(function ($data) {
            return [
                'id' => $data->id,
                'code' => $data->code,
                'title' => app()->getLocale() == 'ar' ? $data->title_ar : $data->title_en,
                'title_ar' => $data->title_ar,
                'title_en' => $data->title_en,
                'discount' => $data->discount,
                'application_percentage' => $data->application_percentage,
                'price' => $data->price,
                'available_count' => $data->available_count,
                'status' => [
                    'name' => $data->status == 1 ? __('main.active') : __('main.not_active'),
                    'value' => $data->status,
                ],
                'expired_at' => $data->expired_at,
                'provider' => $data->provider ? (app()->getLocale() == 'ar' ? $data->provider->name_ar : $data->provider->name_en) : null,
                'featured' => [
                    'name' => $data->featured == 1 ? __('main.not_featured') : __('main.featured'),
                    'value' => $data->featured,
                ],
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
