<?php

namespace App\Http\Resources\CPanel;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ReservationResource extends ResourceCollection
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
                'provider_id' => $data->provider_id,
                'approved' => $data->approved,
                'price' => $data->price,
                'bill_total' => $data->bill_total,
                'rejection_reason' => $data->rejection_reason,
                'for_me' => $data->for_me,
                'branch_no' => $data->branch_no,
                'is_reported' => $data->is_reported,
                'mainprovider' => $data->mainprovider,
                'admin_value_from_reservation_price_Tax' => $data->admin_value_from_reservation_price_Tax,
                'reservation_total' => $data->reservation_total,
                'branch_name' => $data->branch_name,
                'doctor_name' => app()->getLocale() == 'ar' ? $data->doctor->name_ar : $data->doctor->name_en,
                'user_name' => $data->user->name,
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
