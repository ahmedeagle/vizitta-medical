<?php

namespace App\Http\Resources\CPanel;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TicketResource extends ResourceCollection
{
    public function toArray($request)
    {
        $result['data'] = $this->collection->transform(function ($data) {
            $res = [
                'id' => $data->id,
                'message_no' => $data->message_no,
                'title' => $data->title,
                'created_at' => $data->created_at->format('Y-m-d'),
            ];

            if (request()->route()->getName() == 'providers_messages')
                $res['provider_name'] = isset($data->provider) ? (app()->getLocale() == 'ar' ? $data->provider->name_ar : $data->provider->name_en) : null;
            else
                $res['user_name'] = isset($data->user) ? $data->user->name : null;

            ////////////////////////////// Start get type column ///////////////////////////////
            if ($data->type == 1)
                $res['type'] = trans('messages.Inquiry');

            else if ($data->type == 2)
                $res['type'] = trans('messages.Suggestion');

            else if ($data->type == 3)
                $res['type'] = trans('messages.Complaint');

            else if ($data->type == 4)
                $res['type'] = trans('messages.Others');

            ////////////////////////////// End get type column ///////////////////////////////

            $res['importance'] = $data->importance == 1 ? trans('messages.Quick') : trans('messages.Normal');

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
