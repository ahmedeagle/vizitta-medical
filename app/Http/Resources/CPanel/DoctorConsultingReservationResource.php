<?php

namespace App\Http\Resources\CPanel;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DoctorConsultingReservationResource extends ResourceCollection
{
    public function toArray($request)
    {
        $result['data'] = $this->collection->transform(function ($data) {
            return [
                'id' => $data->id,
                'reservation_no' => $data->reservation_no,
                'transaction_id' => $data->transaction_id,
                'day_date' => $data->day_date,
                'from_time' => $data->from_time,
                'to_time' => $data->to_time,
                'paid' => $data->paid,
                'approved' => $data->approved,
                'price' => $data->price,
                'total_price' => $data->total_price,
                'provider_name' => $data->provider == null ? null : (app()->getLocale() == 'ar' ? $data->provider->name_ar : $data->provider->name_en),
                'branch_name' => $data->branch == null ? null : (app()->getLocale() == 'ar' ? $data->branch->name_ar : $data->branch->name_en),
                'doctor_name' => app()->getLocale() == 'ar' ? $data->doctor->name_ar : $data->doctor->name_en,
                'user_name' => $data->user->name,
                'show_delete' => $data->approved == 0 || $data->approved == 2 ? 1 : 0,
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
