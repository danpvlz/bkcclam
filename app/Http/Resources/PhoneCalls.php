<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PhoneCalls extends JsonResource
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
            'idLlamada'=>$this->idLlamada,
            'idAsociado'=>$this->idAsociado,
            'asociado'=>$this->asociado,
            'tipo'=>$this->tipoAsociado,
            'estado'=>$this->estado,
            'sector'=>$this->sector,
            'cobrador'=>$this->descripcion,
            'fecha'=>$this->fecha,
            'inicio'=>$this->horaInicio,
            'fin'=>$this->horaFin,
            'detalle'=>$this->detalle,
            'colaborador'=>$this->colaborador,
        ];
    }
}
