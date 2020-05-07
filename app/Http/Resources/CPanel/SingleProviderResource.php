<?php

namespace App\Http\Resources\CPanel;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SingleProviderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'balance' => number_format($this->balance, 2),
        ];
    }

}
