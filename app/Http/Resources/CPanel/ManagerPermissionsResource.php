<?php

namespace App\Http\Resources\CPanel;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ManagerPermissionsResource extends ResourceCollection
{
    public function toArray($request)
    {
        return $this->collection->transform(function ($data) {
            $res[$data->name] = [
                'view' => $data->pivot->view,
                'add' => $data->pivot->add,
                'edit' => $data->pivot->edit,
                'delete' => $data->pivot->delete,
            ];
            return $res;
        });
    }

}
