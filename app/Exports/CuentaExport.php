<?php

namespace App\Exports;

use App\Models\Cuenta;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CuentaExport implements FromCollection, WithHeadings
{
    protected $since;
    protected $until;
    protected $status;
    protected $number;
    protected $idAsociado;
    protected $debCollector;
    protected $tipocomprob;
    
     function __construct(
        $since,
        $until,
        $status,
        $number,
        $idAsociado,
        $debCollector,
        $tipocomprob
    ) {
            $this->since = $since;
            $this->until = $until;
            $this->status = $status;
            $this->number = $number;
            $this->idAsociado = $idAsociado;
            $this->debCollector = $debCollector;
            $this->tipocomprob = $tipocomprob;
     }

    public function headings(): array
    {
        return ["Emision",  "Tipo","Serie-Numero","RUC/DNI","Asociado", "Estado", "Total", "Cobrador" , "FechaPago","Anulacion"];
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {

        $first= Cuenta::join('Asociado', 'Asociado.idAsociado', '=', 'Cuenta.idAdquiriente')
        ->join('Sector', 'Sector.idSector', '=', 'Asociado.idSector')
        ->leftJoin('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
        ->leftJoin('Persona', 'Persona.idAsociado', '=', 'Asociado.idAsociado')
        ->select(
            'Cuenta.fechaEmision', 
            \DB::raw('IF(Cuenta.tipoDocumento=1, "Factura",  IF(Cuenta.tipoDocumento=2, "Boleta",  "Nota de credito"))'),
            \DB::raw('CONCAT(Cuenta.serie,"-",Cuenta.numero) as serieNumero'),
            \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.ruc,Persona.documento) as documento'),
            \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.razonSocial,Persona.nombresCompletos) as asociado'),
            \DB::raw('IF(Cuenta.estado=1, "Por Cancelar",  IF(Cuenta.estado=2, "Cancelada",  "Anulada"))'),
            'Cuenta.total', 
            'Sector.descripcion',
            'Cuenta.fechaFinPago',
            'Cuenta.fechaAnulacion'
        )->where('Cuenta.serie','like','%109');

        if($this->status && $this->status<4){
            $first->where('Cuenta.estado','=',$this->status);
            if($this->status==2){
                $first->where('Cuenta.tipoDocumento','<',3);
            }
        }

        if($this->tipocomprob){
            $first->where('Cuenta.tipoDocumento','=',$this->tipocomprob);
        }

        if($this->number){
            $first->where('Cuenta.numero','like',$this->number."%");
        }

        if($this->idAsociado){
            $first->where('Cuenta.idAdquiriente','=',$this->idAsociado);
        }

        if($this->debCollector){
            $first->where('Sector.idSector',"=", $this->debCollector);
        }

        if($this->since || $this->until){
            $since=$this->since;
            $until=$this->until;
            $status=$this->status;
            $first->where(function($query) use ($since,$until,$status) {
                if($status==4){    
                    $query->orWhere(function($query2) use ($since,$until) {
                        if($since){
                            $query2->where('Cuenta.fechaEmision','>=',$since);
                        }
                        if($until){
                            $query2->where('Cuenta.fechaEmision','<=',$until);
                        }
                    });
                }else{
                $query->orWhere(function($query2) use ($since,$until) {
                    if($since){
                        $query2->where('Cuenta.fechaEmision','>=',$since);
                    }
                    if($until){
                        $query2->where('Cuenta.fechaEmision','<=',$until);
                    }
                })
                ->orWhere(function($query3) use ($since,$until) {
                    if($since){
                        $query3->where('Cuenta.fechaFinPago','>=',$since);
                    }
                    if($until){
                        $query3->where('Cuenta.fechaFinPago','<=',$until);
                    }
                })
                ->orWhere(function($query3) use ($since,$until,$status) {
                    if($status==3){
                        if($since){
                            $query3->where('Cuenta.fechaAnulacion','>=',$since);
                        }
                        if($until){
                            $query3->where('Cuenta.fechaAnulacion','<=',$until);
                        }
                    }
                });
                }
            });
        }

        return $first->orderBy('idCuenta', 'desc')->get();
    }
}
