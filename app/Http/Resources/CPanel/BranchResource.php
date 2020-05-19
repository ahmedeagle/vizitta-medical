<?php

namespace App\Http\Resources\CPanel;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BranchResource extends ResourceCollection
{
    public function toArray($request)
    {
        $result['data'] = $this->collection->transform(function ($data) {
            return [
                'id' => $data->id,
                'name' => app()->getLocale() == 'ar' ? $data->name_ar : $data->name_en,
                'username' => $data->username,
                'mobile' => $data->mobile,
                'city' => app()->getLocale() == 'ar' ? $data->city->name_ar : $data->city->name_en,
                'district' => app()->getLocale() == 'ar' ? $data->district->name_ar : $data->district->name_en,
                'status' => [
                    'name' => $data->status == '1' ? __('main.active') : __('main.not_active'),
                    'value' => $data->status,
                ],
                'balance' => $data->balance,
                'main_provider' => app()->getLocale() == 'ar' ? $data->main_provider->name_ar : $data->main_provider->name_en,
                'pinned' => $data->subscriptions ? 1 : 0,
                'created_at' => $data->created_at->format('Y-m-d'),
                'show_delete' => $data->reservations->count() > 0 || $data->doctors->count() > 0 ? 0 : 1,
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
