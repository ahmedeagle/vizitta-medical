<?php

namespace App\Http\Resources\CPanel;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OfferCategoryResource extends ResourceCollection
{
    public function toArray($request)
    {
        $result['data'] = $this->collection->transform(function ($data) {
            return [
                'id' => $data->id,
                'name' => app()->getLocale() == 'ar' ? $data->name_ar : $data->name_en,
                'name_ar' => $data->name_ar,
                'name_en' => $data->name_en,
                'status'   =>$data->status,
                'parentCategory' => $data->parentCategory ? (app()->getLocale() == 'ar' ? $data->parentCategory->name_ar : $data->parentCategory->name_en) : null,
                'hasTimer' => [
                    'name' => $data->hastimer == 1 ? __('main.active') : __('main.not_active'),
                    'value' => $data->hastimer,
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
