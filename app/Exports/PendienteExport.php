<?php

namespace App\Exports;

use App\Models\Cuenta;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PendienteExport implements FromCollection, WithHeadings
{
    protected $fecha;
    protected $idAsociado;
    protected $debCollector;
    
     function __construct($fecha, $idAsociado, $debCollector) {
            $this->fecha = $fecha;
            $this->idAsociado = $idAsociado;
            $this->debCollector = $debCollector;
     }

    public function headings(): array
    {
        return ["Emision", "Serie-Numero","Asociado", "Total", "Estado", "Cobrador" , "Anulacion"];
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {

        $subQuery = \DB::query()->from('Cuenta')->where('Cuenta.serie','like','%109')->where('Cuenta.fechaEmision','<',$this->fecha);

        $query = \DB::query()->fromSub($subQuery, 'a')
        ->leftJoin('Pago', 'Pago.idCuenta', '=', 'a.idCuenta')
        ->select(
            'a.fechaEmision', 
            \DB::raw('CONCAT(a.serie,"-",a.numero) as serieNumero'),
            'a.idAdquiriente',
            'a.tipoAsociado',
            'a.total',
            'a.estado',
            'a.fechaAnulacion'
        )
        ->where('Pago.fecha','>',$this->fecha)
        ->orWhere('Pago.fecha','=','"NULL"')
        ->orWhereNull('Pago.fecha');
        
        $asociados = \DB::query()->fromSub($query, 'b')
        ->join('Asociado', 'Asociado.idAsociado', '=', 'b.idAdquiriente')
        ->join('Sector', 'Sector.idSector', '=', 'Asociado.idSector')
        ->leftJoin('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
        ->leftJoin('Persona', 'Persona.idAsociado', '=', 'Asociado.idAsociado')
        ->select(
            'b.fechaEmision', 
            'b.serieNumero', 
            'b.idAdquiriente', 
            \DB::raw('IF(b.tipoAsociado=1,Empresa.razonSocial, Persona.nombresCompletos) as asociado'),
            'b.total',
            'b.estado',
            'Sector.idSector',
            'Sector.descripcion',
            'b.fechaAnulacion'
        )
        ->where('b.fechaAnulacion','>',$this->fecha)
        ->orWhere('b.fechaAnulacion','=','"NULL"')
        ->orWhereNull('b.fechaAnulacion');

        $final = \DB::query()->fromSub($asociados, 'f');

        if($this->idAsociado){
            $final->where('idAdquiriente','=',$this->idAsociado);
        }

        if($this->debCollector){
            $final->where('idSector',"=", $this->debCollector);
        }

        return $final->orderBy('fechaEmision', 'desc')->get();
    }
}
