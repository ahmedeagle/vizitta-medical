<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class SingleDoctorResource extends JsonResource
{
    public function toArray($request)
    {
        $authUser = $this->auth('user-api');
        $user = User::find($authUser->id);

        $result = [
            'id' => $this->id,
            'doctor_type' => $this->doctor_type,
            'name' => app()->getLocale() == 'ar' ? $this->name_ar : $this->name_en,
            'information' => app()->getLocale() == 'ar' ? $this->information_ar : $this->information_en,
            'abbreviation' => app()->getLocale() == 'ar' ? $this->abbreviation_ar : $this->abbreviation_en,
            'nickname' => [
                'id' => $this->nickname->id,
                'name' => app()->getLocale() == 'ar' ? $this->nickname->name_ar : $this->nickname->name_en,
            ],
            'specification' => [
                'id' => $this->specification->id,
                'name' => app()->getLocale() == 'ar' ? $this->specification->name_ar : $this->specification->name_en
            ],
            'price' => $this->price,
            'rate' => $this->rate,
            'photo' => $this->photo,
            'favourite' => $user->favourites()->where('doctor_id', $this->id)->first() == null ? 0 : 1,
        ];

        return $result;
    }

}
