<?php

namespace App\Exports;

use App\Models\Cuenta;
use App\Models\Membresia;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MembresiaExport implements FromCollection, WithHeadings
{
    protected $status;
    protected $idAsociado;
    protected $debCollector;
    
     function __construct($status, $idAsociado, $debCollector) {
            $this->status = $status;
            $this->idAsociado = $idAsociado;
            $this->debCollector = $debCollector;
     }

    public function headings(): array
    {
        return ["Asociado" ,"Mes",'Año','Cuenta',"Emisión","Estado", "Cobrado", "Pagado", "Cobrador"];
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $first= Membresia::join('Asociado', 'Asociado.idAsociado', '=', 'Membresia.idAsociado')
        ->join('Sector', 'Sector.idSector', '=', 'Asociado.idSector')
        ->join('Cuenta', 'Cuenta.idCuenta', '=', 'Membresia.idCuenta')
        ->leftJoin('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
        ->leftJoin('Persona', 'Persona.idAsociado', '=', 'Asociado.idAsociado')
        ->select(
            \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.razonSocial,Persona.nombresCompletos) as asociado'),
            \DB::raw('IF(Membresia.mes=1, "Enero",  IF(Membresia.mes=2, "Febrero",  IF(Membresia.mes=3, "Marzo",  IF(Membresia.mes=4, "Abril",  IF(Membresia.mes=5, "Mayo",  IF(Membresia.mes=6, "Junio",  IF(Membresia.mes=7, "Julio",  IF(Membresia.mes=8, "Agosto",  IF(Membresia.mes=9, "Setiembre",  IF(Membresia.mes=10, "Octubre",  IF(Membresia.mes=11, "Noviembre",  IF(Membresia.mes=12, "Diciembre",  Membresia.masdeuno))))))))))))'),
            'Membresia.year', 
            \DB::raw('CONCAT(Cuenta.serie,"-",Cuenta.numero) as cuenta'),
            \DB::raw('Cuenta.fechaEmision'),
            \DB::raw('IF(Membresia.estado=1, "Por Cancelar",  IF(Membresia.estado=2, "Cancelada",  "Anulada"))'),
            'Membresia.cobrado', 
            'Membresia.pagado', 
            'Sector.descripcion'
        )->where('Cuenta.serie','like','%109');

        if($this->status){
            $first->where('Membresia.estado','=',$this->status);
        }

        if($this->idAsociado){
            $first->where('Asociado.idAsociado','=',$this->idAsociado);
        }

        if($this->debCollector){
            $first->where('Sector.idSector','=',$this->debCollector);
        }
        
        return $first->orderBy('asociado', 'asc')->orderBy('Membresia.year', 'asc')->orderBy('Membresia.mes', 'asc')->get();
    }
}
