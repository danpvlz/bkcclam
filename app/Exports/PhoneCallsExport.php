<?php

namespace App\Exports;

use App\Models\PhoneCalls;
use App\Models\Associated\Associated;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PhoneCallsExport implements FromCollection, WithHeadings
{


    protected $since;
    protected $until;
    protected $idAsociado;
    protected $debCollector;
    
     function __construct($since, $until, $idAsociado, $debCollector) {
            $this->since = $since;
            $this->until = $until;
            $this->idAsociado = $idAsociado;
            $this->debCollector = $debCollector;
     }

    public function headings(): array
    {
        return ["Asociado", "documento","tipo", "estado", "sector", "cobrador" , "fecha","horaInicio", "horaFin", "detalle"];

    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $first= Associated::
        select(
            'Empresa.razonSocial as asociado', 
            'Empresa.ruc as documento',
            'Asociado.tipoAsociado',
            'Asociado.estado',
            'Sector.codigo  as sector',
            'Sector.descripcion',
            'Llamada.fecha',
            'Llamada.horaInicio',
            'Llamada.horaFin',
            'Llamada.detalle'
        )
        ->join('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
        ->join('Llamada', 'Llamada.idAsociado', '=', 'Asociado.idAsociado')
        ->join('Sector', 'Sector.idSector', '=', 'Asociado.idSector');

        if($this->since){
            $first->where('fecha','>=',$this->since);
        }

        if($this->until){
            $first->where('fecha','<=',$this->until);
        }

        if($this->idAsociado){
            $first->where('Asociado.idAsociado',$this->idAsociado);
        }

        if($this->debCollector){
            $first->where('Sector.idSector',$this->debCollector);
        }

        $final= Associated::
        select(
            'Persona.nombresCompletos as asociado', 
            'Persona.documento as documento',
            'Asociado.tipoAsociado',
            'Asociado.estado',
            'Sector.codigo as sector',
            'Sector.descripcion',
            'Llamada.fecha',
            'Llamada.horaInicio',
            'Llamada.horaFin',
            'Llamada.detalle'
        )
        ->join('Persona', 'Persona.idAsociado', '=', 'Asociado.idAsociado')
        ->join('Llamada', 'Llamada.idAsociado', '=', 'Asociado.idAsociado')
        ->join('Sector', 'Sector.idSector', '=', 'Asociado.idSector');

        if($this->since){
            $final->where('fecha','>=',$this->since);
        }

        if($this->until){
            $final->where('fecha','<=',$this->until);
        }

        if($this->idAsociado){
            $final->where('Asociado.idAsociado',$this->idAsociado);
        }

        if($this->debCollector){
            $final->where('Sector.idSector',$this->debCollector);
        }

        $final
        ->union($first)
        ->orderBy('fecha', 'desc')
        ->orderBy('horaInicio', 'desc');             
        return $final->get();
    }
}
