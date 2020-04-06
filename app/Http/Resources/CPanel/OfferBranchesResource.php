<?php

namespace App\Http\Resources\CPanel;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OfferBranchesResource extends ResourceCollection
{
    public function toArray($request)
    {
        $result['data'] = $this->collection->transform(function ($data) {
            return [
                'id' => $data->branch->id,
                'name' => app()->getLocale() == 'ar' ? $data->branch->name_ar : $data->branch->name_en,
                'name_ar' => $data->branch->name_ar,
                'name_en' => $data->branch->name_en,
                'status' => [
                    'name' => $data->branch->status == 1 ? __('main.active') : __('main.not_active'),
                    'value' => $data->branch->status,
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
