<?php

namespace App\Http\Resources\CPanel;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomPageResource extends ResourceCollection
{
    public function toArray($request)
    {
        $result['data'] = $this->collection->transform(function ($data) {
            return [
                'id' => $data->id,
                'title' => app()->getLocale() == 'ar' ? $data->title_ar : $data->title_en,
                'content' => app()->getLocale() == 'ar' ? $data->content_ar : $data->content_en,
                'status' => $data->status == 1 ? __('main.published') : __('main.un_published'),
                'provider' => $data->provider == 1 ? __('main.yes') : __('main.no'),
                'user' => $data->user == 1 ? __('main.yes') : __('main.no'),
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
