<?php

namespace App\Http\Resources\CPanel;

use App\Models\Provider;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ConsultingBalanceResource extends ResourceCollection
{
    public function toArray($request)
    {
        $result['data'] = $this->collection->transform(function ($data) {
            return [
                'id' => $data->id,
                'doctor_name' => $data->name,
                'doctor_photo' => $data->photo,
                'doctor_type' => $data->doctor_type,
                'is_consult' => $data->is_consult,
                'branch_name' => $data->provider->name,
                'provider_name' => Provider::find($data->provider->provider_id) ? Provider::find($data->provider->provider_id)->{'name_' . app()->getLocale()} : '',
                'balance' => number_format($data->balance, 2),
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
