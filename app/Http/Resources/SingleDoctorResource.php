<?php

namespace App\Http\Resources;

use App\Models\Provider;
use App\Models\User;
use App\Traits\GlobalTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class SingleDoctorResource extends JsonResource
{

    use GlobalTrait;

    public $request;
    public $doctor;

    public function __construct($doctor, $request)
    {

        $this->doctor-> request = $request;
        $this->doctor-> doctor = $doctor;
    }

    public function toArray($request)
    {


        $authUser = $this->doctor->auth('user-api');
        if (!$authUser)
            $user = null;
        else
            $user = User::find($authUser->id);

        $result = [
            'id' => $this->doctor->doctor -> id,
            'doctor_type' => $this->doctor->doctor_type,
            'name' => app()->getLocale() == 'ar' ? $this->doctor->name_ar : $this->doctor->name_en,
            'information' => app()->getLocale() == 'ar' ? $this->doctor->information_ar : $this->doctor->information_en,
            'abbreviation' => app()->getLocale() == 'ar' ? $this->doctor->abbreviation_ar : $this->doctor->abbreviation_en,
            'branch' => app()->getLocale() == 'ar' ? isset($this->doctor->provider->name_ar) ? $this->doctor->provider->name_ar : "" : isset($this->doctor->provider->name_en) ? $this->doctor->provider->name_en : "",
            'token' => request()->api_token,
            'token2' => $this -> request->api_token,
            'nickname' => [
                'id' => $this->doctor->nickname->id,
                'name' => app()->getLocale() == 'ar' ? $this->doctor->nickname->name_ar : $this->doctor->nickname->name_en,
            ],
            'specification' => [
                'id' => $this->doctor->specification->id,
                'name' => app()->getLocale() == 'ar' ? $this->doctor->specification->name_ar : $this->doctor->specification->name_en
            ],
            'price' => $this->doctor->price,
            'rate' => $this->doctor->rate,
            'photo' => $this->doctor->photo,
            'favourite' => $user == null ? 0 : ($user->favourites()->where('doctor_id', $this->doctor->id)->first() == null ? 0 : 1),
        ];

        if (!empty($this->doctor->provider)) {
            $mainProvider = Provider::whereNull('provider_id')->find($this->doctor->provider->provider_id);
            $result['provider'] = $mainProvider ? (app()->getLocale() == 'ar' ? $mainProvider->name_ar : $mainProvider->name_en) : null;
        } else {
            $result['provider'] = null;
        }

        $res = !is_null($this->doctor->consultativeTimes) && count($this->doctor->consultativeTimes) > 0 ? $this->doctor->consultativeTimes() : $this->doctor->times();
        $res = $res->distinct()->get(['day_code', 'day_name'])->makeHidden(['time_duration']);
        $result['times'] = $res;

        return $result;
    }

}
