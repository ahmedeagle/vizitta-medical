<?php

namespace App\Http\Resources\CPanel;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class FilterResource extends ResourceCollection
{
    public function toArray($request)
    {
        $result['data'] = $this->collection->transform(function ($data) {
            return [
                'id' => $data->id,
                'title' => app()->getLocale() == 'ar' ? $data->title_ar : $data->title_en,
                'title_ar' => $data->title_ar,
                'title_en' => $data->title_en,
                'price' => $data->price,
                'status' => [
                    'name' => $data->status == 1 ? __('main.active') : __('main.not_active'),
                    'value' => $data->status,
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
