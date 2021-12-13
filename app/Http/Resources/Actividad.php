<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Actividad extends JsonResource
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
            'titulo'=>$this->titulo,
            'imagen'=>$this->imagen,
            'fechaInicio'=>$this->fechaInicio,
            'fechaFin'=>$this->fechaFin,
            'creado'=>$this->created_at,
            'actualizado'=>$this->updated_at
        ];
    }
}
