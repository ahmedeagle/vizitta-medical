<?php

namespace App\Http\Resources\CPanel;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProviderResource extends ResourceCollection
{
    public function toArray($request)
    {
        $result['data'] = $this->collection->transform(function ($data) {
            return [
                'id' => $data->id,
                'name' => app()->getLocale() == 'ar' ? $data->name_ar : $data->name_en,
                'status' => [
                    'name' => $data->status == '1' ? __('main.active') : __('main.not_active'),
                    'value' => $data->status,
                ],
                'username' => $data->username,
                'mobile' => $data->mobile,
                'application_percentage' => $data->application_percentage,
                'application_percentage_bill' => $data->application_percentage_bill,
                'commercial_no' => $data->commercial_no,
                'type' => app()->getLocale() == 'ar' ? $data->type->name_ar : $data->type->name_en,
                'city' => app()->getLocale() == 'ar' ? $data->city->name_ar : $data->city->name_en,
                'district' => app()->getLocale() == 'ar' ? $data->district->name_ar : $data->district->name_en,
                'lottery' => [
                    'name' => $data->lottery == '1' ? __('main.yes') : __('main.no'),
                    'value' => $data->lottery,
                ],
                'created_at' => $data->created_at->format('Y-m-d'),
                'show_delete' => $data->branches->count() > 0 ? 0 : 1,
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
