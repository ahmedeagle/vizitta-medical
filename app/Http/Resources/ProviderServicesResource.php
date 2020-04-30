<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProviderServicesResource extends JsonResource
{
    public function toArray($request)
    {
//        $result['data'] = $this->collection->transform(function ($data) {
            return [
                'id' => $this->id,
            ];
//        });
//
//        $result['pagination'] = [
//            'total' => $this->total(),
//            'count' => $this->count(),
//            'per_page' => $this->perPage(),
//            'current_page' => $this->currentPage(),
//            'total_pages' => $this->lastPage()
//        ];
//        return $result;
    }

}
