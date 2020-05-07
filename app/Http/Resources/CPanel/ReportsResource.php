<?php

namespace App\Http\Resources\CPanel;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ReportsResource extends ResourceCollection
{
    public function toArray($request)
    {
        $result['data'] = $this->collection->transform(function ($data) {
            return [
                'id' => $data->id,
                'reservation_no' => $data->reservation_no,
                'reporting_type' => $data->reporting_type,
                'user_name' => isset($data->user) ? $data->user->name : null,
                'provider_name' => isset($data->provider) ? (app()->getLocale() == 'ar' ? $data->provider->name_ar : $data->provider->name_en) : null,
                'created_at' => $data->created_at->format('Y-m-d'),
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
