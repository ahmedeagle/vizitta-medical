<?php

namespace App\Http\Resources\CPanel;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ManagerPermissionsResource extends ResourceCollection
{
    public function toArray($request)
    {
        $result = [];
        foreach ($this->collection->toArray() as $key => $value) {
            $result[$value['name']] = [
                'view' => $value['pivot']['view'],
                'add' => $value['pivot']['add'],
                'edit' => $value['pivot']['edit'],
                'delete' => $value['pivot']['delete'],
            ];
        }
        return (array)$result;
    }

}
