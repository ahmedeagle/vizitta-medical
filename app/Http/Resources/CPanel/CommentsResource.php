<?php

namespace App\Http\Resources\CPanel;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CommentsResource extends ResourceCollection
{
    public function toArray($request)
    {
        $result['data'] = $this->collection->transform(function ($data) {
            return [
                'id' => $data->id,
                'reservation_no' => $data->reservation_no,
                'rate_comment' => $data->rate_comment,
                'comment_user_name' => $data->user->name,
                'mainProvider' => $data->mainprovider,
                'branch' => app()->getLocale() == 'ar' ? $data->provider->name_ar : $data->provider->name_en,
                'provider_rate' => $data->provider_rate,
                'doctor' => app()->getLocale() == 'ar' ? $data->doctor->name_ar : $data->doctor->name_en,
                'doctor_rate' => $data->doctor_rate,
                'rate_date' => $data->rate_date,
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
