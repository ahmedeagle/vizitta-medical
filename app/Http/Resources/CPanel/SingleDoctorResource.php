<?php

namespace App\Http\Resources\CPanel;

use Illuminate\Http\Resources\Json\JsonResource;

class SingleDoctorResource extends JsonResource
{
    public function toArray($request)
    {
        $result = [
            'id' => $this->id,
            'doctor_type' => $this->doctor_type,
            'name' => app()->getLocale() == 'ar' ? $this->name_ar : $this->name_en,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'username' => $this->username,
            'information_ar' => $this->information_ar,
            'information_en' => $this->information_en,
            'abbreviation_ar' => $this->abbreviation_ar,
            'abbreviation_en' => $this->abbreviation_en,
            'gender' => $this->gender == 1 ? __('main.male') : __('main.female'),
            'nickname' => app()->getLocale() == 'ar' ? $this->nickname->name_ar : $this->nickname->name_en,
            'specification' => app()->getLocale() == 'ar' ? $this->specification->name_ar : $this->specification->name_en,
            'nationality' => app()->getLocale() == 'ar' ? $this->nationality->name_ar : $this->nationality->name_en,
            'price' => $this->price,
            'price_consulting' => $this->price_consulting,
            'rate' => $this->rate,
            'photo' => $this->photo,
            'reservation_period' => $this->reservation_period,
            'waiting_period' => $this->waiting_period,
            'available_time' => $this->available_time,
            'times' => $this->times,
            'consultativeTimes' => $this->consultativeTimes,

            'status' => [
                'name' => $this->status == '1' ? __('main . active') : __('main . not_active'),
                'value' => $this->status,
            ],
            'show_delete' => $this->reservations->count() > 0 || $this->doctorConsultingReservations->count() > 0 ? 0 : 1,
        ];

        if ($this->doctor_type == 'clinic') {
            $result['branch'] = $this->branch;
        }
        return $result;
    }

}
