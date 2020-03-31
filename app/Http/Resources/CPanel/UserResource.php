<?php

namespace App\Http\Resources\CPanel;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserResource extends ResourceCollection
{
    public function toArray($request)
    {
        $result['data'] = $this->collection->transform(function ($data) {
            return [
                'id' => $data->id,
                'name' => $data->name,
                'mobile' => $data->mobile,
                'id_number' => $data->id_number,
                'points' => isset($data->point) ? $data->point->points : null,
                'birth_date' => $data->birth_date,
                'city' => app()->getLocale() == 'ar' ? $data->city->name_ar : $data->city->name_en,
                'insuranceCompany' => app()->getLocale() == 'ar' ? $data->insuranceCompany->name_ar : $data->insuranceCompany->name_en,
                'status' => [
                    'name' => $data->status == '1' ? __('main.active') : __('main.not_active'),
                    'value' => $data->status,
                ],
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
