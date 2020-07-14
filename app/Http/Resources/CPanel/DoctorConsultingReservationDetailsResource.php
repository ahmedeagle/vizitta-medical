<?php

namespace App\Http\Resources\CPanel;

use Illuminate\Http\Resources\Json\JsonResource;

class DoctorConsultingReservationDetailsResource extends JsonResource
{
    public function toArray($request)
    {
        $result = [
            'id' => $this->id,
            'reservation_no' => $this->reservation_no,
            'day_date' => $this->day_date,
            'from_time' => $this->from_time,
            'to_time' => $this->to_time,
            'created_at'  =>  $this->created_at,
            'paid' => $this->paid,
            'price' => $this->price,
            'total_price' => $this->total_price,

            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'hours_duration' => $this->hours_duration,

            'doctor_rate' => $this->doctor_rate,
            'provider_rate' => $this->provider_rate,
            'rate_comment' => $this->rate_comment,
            'rate_date' => $this->rate_date,
            'rejection_reason' => $this->rejectionResoan,
            'bill_photo' => $this->bill_photo,
            'doctor_rejected_reason_notes' => $this->doctor_rejection_reason,
            'user_rejected_reason_notes' => $this->rejected_reason_notes,
            'approved' => [
                'name' => $this->getApproved(),
                'value' => $this->approved,
            ],
        ];

        if ($this->provider != null) {
            $result['provider'] = [
                'id' => $this->provider->id,
                'name' => app()->getLocale() == 'ar' ? $this->provider->name_ar : $this->provider->name_en
            ];
        }

        if ($this->branch != null) {
            $result['branch'] = [
                'id' => $this->branch->id,
                'name' => app()->getLocale() == 'ar' ? $this->branch->name_ar : $this->branch->name_en
            ];
        }

        if ($this->doctor != null) {
            $result['doctor'] = [
                'id' => $this->doctor->id,
                'name' => app()->getLocale() == 'ar' ? $this->doctor->name_ar : $this->doctor->name_en
            ];
        }

        if ($this->user != null) {
            $result['user'] = [
                'id' => $this->user->id,
                'name' => $this->user->name
            ];
        }

        if ($this->paymentMethod != null) {
            $result['paymentMethod'] = [
                'id' => $this->paymentMethod->id,
                'name' => app()->getLocale() == 'ar' ? $this->paymentMethod->name_ar : $this->paymentMethod->name_en
            ];
        }

        return $result;
    }

}
