<?php

namespace App\Http\Resources\CPanel;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class InsuranceCompanyResource extends ResourceCollection
{
    public function toArray($request)
    {
        $result['data'] = $this->collection->transform(function ($data) {
            return [
                'id' => $data->id,
                'name' => app()->getLocale() == 'ar' ? $data->name_ar : $data->name_en,
                'name_ar' => $data->name_ar,
                'name_en' => $data->name_en,
                'status' => [
                    'name' => $data->status == '1' ? __('main.active') : __('main.not_active'),
                    'value' => $data->status,
                ],
                'image' => $data->image,
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
