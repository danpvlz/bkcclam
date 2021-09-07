<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Colaborador extends JsonResource
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
            'idColaborador'=>$this->idColaborador,
            'colaborador'=>$this->nombres . ' ' . $this->apellidoPaterno . ' ' . $this->apellidoMaterno,
            'foto'=>$this->foto ? 'storage/colaborador/'.$this->foto : $this->foto,
            'dni'=>$this->dni,
            'fechaIngreso'=>$this->fechaIngreso,
            'usuario'=>$this->usuario,
            'estado'=>$this->estado,
        ];
    }
}
