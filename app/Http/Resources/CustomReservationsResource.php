<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomReservationsResource extends ResourceCollection
{
    public function toArray($request)
    {
        $result['data'] = $this->collection->transform(function ($data) {
            return [
                'id' => $data->id,
                'reservation_no' => $data->reservation_no,
                'day_date' => $data->day_date,
                'from_time' => $data->from_time,
                'to_time' => $data->to_time,
                'approved' => $data->approved,
                'is_visit_doctor' => $data->is_visit_doctor,
                'provider' => [
                    'id' => $data->provider->id,
                    'name' => app()->getLocale() == 'ar' ? $data->provider->name_ar : $data->provider->name_en,
                ],
                'branch' => [
                    'id' => $data->branch->id,
                    'name' => app()->getLocale() == 'ar' ? $data->branch->name_ar : $data->branch->name_en,
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
