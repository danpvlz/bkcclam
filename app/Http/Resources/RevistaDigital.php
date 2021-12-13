<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RevistaDigital extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'=>$this->id,
            'url'=>$this->url,
            'imagen'=>$this->imagen,
            'fecha'=>$this->fecha,
            'active'=>$this->active,
            'creado'=>$this->created_at,
            'actualizado'=>$this->updated_at
        ];
    }
}
