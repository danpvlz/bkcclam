<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Noticia extends JsonResource
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
            'fecha'=>$this->fecha,
            'url'=>$this->url,
            'miniatura'=>$this->miniatura,
            'titulo'=>$this->titulo,
            'contenido'=>$this->contenido,
            'creado'=>$this->created_at,
            'actualizado'=>$this->updated_at
        ];
    }
}
