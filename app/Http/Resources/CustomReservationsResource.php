<?php

namespace App\Http\Resources;

use App\Models\Provider;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomReservationsResource extends ResourceCollection
{
    public function toArray($request)
    {
        $result['data'] = $this->collection->transform(function ($data) {
            $res = [
                'id' => $data->id,
                'reservation_no' => $data->reservation_no,
                'day_date' => $data->day_date,
                'from_time' => $data->from_time,
                'to_time' => $data->to_time,
                'approved' => $data->approved,
                'is_visit_doctor' => $data->is_visit_doctor,
                'service_id' => $data->service_id,
                'doctor_id' => $data->doctor_id,
                'promocode_id' => $data->promocode_id,
//                'provider_id' => $data->provider_id,
//                'branch_id' => $data->branch_id,
                'payment_method' => [
                    'id' => $data->paymentMethod->id,
                    'name' => app()->getLocale() == 'ar' ? $data->paymentMethod->name_ar : $data->paymentMethod->name_en,
                ],
            ];

            if ($data->provider_id == "") {
                $branch = Provider::find($data->branch_id);
                $mainProvider = Provider::whereNull('provider_id')->find($branch->provider_id);
                $res['provider'] = [
                    'id' => $mainProvider->id,
                    'name' => app()->getLocale() == 'ar' ? $mainProvider->name_ar : $mainProvider->name_en,
                ];
                $res['branch'] = [
                    'id' => $branch->id,
                    'name' => app()->getLocale() == 'ar' ? $branch->name_ar : $branch->name_en,
                ];
            } else {
                $res['provider'] = [
                    'id' => $data->provider->id,
                    'name' => app()->getLocale() == 'ar' ? $data->provider->name_ar : $data->provider->name_en,
                ];
                $res['branch'] = [
                    'id' => $data->branch->id,
                    'name' => app()->getLocale() == 'ar' ? $data->branch->name_ar : $data->branch->name_en,
                ];
            }

            return $res;

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
