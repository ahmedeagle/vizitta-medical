<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SingleMedicalCenterResource extends JsonResource
{
    public function toArray($request)
    {
        $result = [
            'id' => $this->id,
            'name' => $this->name,
            'branch_count' => $this->branch_count,
            'responsible_name' => $this->responsible_name,
            'responsible_mobile' => $this->responsible_mobile,
          /*  'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'photo' => $this->user->photo,
            ],*/
            'cities' => $this->cities,
            'specifications' => $this->specifications,
        ];

        return $result;
    }

}
