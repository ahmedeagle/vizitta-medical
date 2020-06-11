<?php

namespace App\Http\Resources\CPanel\DoctorArea;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DoctorConsultingReservationResource extends ResourceCollection
{
    public function toArray($request)
    {
        $result['data'] = $this->collection->transform(function ($data) {
            return [
                'id' => $data->id,
                'reservation_no' => $data->reservation_no,
                'day_date' => $data->day_date,
                'from_time' => $data->from_time,
                'to_time' => $data->to_time,
                'approved' => $data->approved,
                'chat_id' => $data->chatId,
                'allow_chat' => $data->allow_chat,
                'total_price' => $data->total_price,
                'user' => [
                    'id' => $data->user->id,
                    'name' => $data->user->name,
                    'photo' => $data->user->photo,
                ]
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
