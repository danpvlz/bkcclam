<?php

namespace App\Http\Resources\FormacionYDesarrollo;

use Illuminate\Http\Resources\Json\JsonResource;

class Inscripcion extends JsonResource
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
            'idInscripcion'=>$this->idInscripcion,
            'fecha'=>$this->fecha,
            'curso'=>$this->curso,
            'dni'=>$this->dni,
            'participante'=>$this->nombres . ' ' . $this->apellidoPaterno . ' ' . $this->apellidoMaterno,
            'celular'=>$this->celular,
            'pagado'=>$this->pagado
        ];
    }
}
