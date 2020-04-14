<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SingleDoctorConsultingReservationResource extends JsonResource
{
    public function toArray($request)
    {
        $result = [
            'id' => $this->id,
            'reservation_no' => $this->reservation_no,
            'day_date' => $this->day_date,
            'from_time' => $this->from_time,
            'to_time' => $this->to_time,
            'approved' => $this->approved,
            'price' => $this->price,
            'total_price' => $this->total_price,
            'doctor' => [
                'id' => $this->doctor->id,
                'name' => app()->getLocale() == 'ar' ? $this->doctor->name_ar : $this->doctor->name_en,
                'price' => $this->doctor->price,
            ],
            'payment_method' => [
                'id' => $this->paymentMethod->id,
                'name' => app()->getLocale() == 'ar' ? $this->paymentMethod->name_ar : $this->paymentMethod->name_en,
            ],
        ];

        return $result;
    }

}
