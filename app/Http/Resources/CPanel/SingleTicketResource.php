<?php

namespace App\Http\Resources\CPanel;

use Illuminate\Http\Resources\Json\JsonResource;

class SingleTicketResource extends JsonResource
{
    public function toArray($request)
    {
        $result = [
            'id' => $this->id,
            'message_no' => $this->message_no,
            'title' => $this->title,
            'created_at' => $this->created_at->format('Y-m-d'),
        ];

        if (request()->route()->getName() == 'single_provider_messages')
            $result['provider_name'] = isset($this->provider) ? (app()->getLocale() == 'ar' ? $this->provider->name_ar : $this->provider->name_en) : null;
        else
            $result['user_name'] = isset($this->user) ? $this->user->name : null;

        ////////////////////////////// Start get type column ///////////////////////////////

        if ($this->type == 1)
            $result['type'] = trans('messages.Inquiry');

        else if ($this->type == 2)
            $result['type'] = trans('messages.Suggestion');

        else if ($this->type == 3)
            $result['type'] = trans('messages.Complaint');

        else if ($this->type == 4)
            $result['type'] = trans('messages.Others');

        ////////////////////////////// End get type column ///////////////////////////////

        $result['importance'] = $this->importance == 1 ? trans('messages.Quick') : trans('messages.Normal');
        $result['replays'] = $this->replays;
        return $result;
    }

}
