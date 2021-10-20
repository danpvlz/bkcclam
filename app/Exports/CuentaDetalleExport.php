<?php

namespace App\Exports;

use App\Models\Cuenta;
use App\Models\CuentaDetalle;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CuentaDetalleExport implements FromCollection, WithHeadings
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
        return [
            "Emision", 
            "Serie-Numero",
            "RUC/DNI",
            "Asociado",
            "CodigoConcepto",
            "Concepto",
            "Mes",
            "Cantidad",
            "TotalCobrado",
            "EstadoCuenta",
            "TotalPagado",
            "FechaPago",
            "Anulacion",
            "Banco",
            "Num. Operación",
            "Num. Sofydoc",
            "Monto operación"
        ];
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $first= Cuenta::join('Asociado', 'Asociado.idAsociado', '=', 'Cuenta.idAdquiriente')
        ->join('Sector', 'Sector.idSector', '=', 'Asociado.idSector')
        ->join('CuentaDetalle', 'Cuenta.idCuenta', '=', 'CuentaDetalle.idCuenta')
        ->join('Concepto', 'Concepto.idConcepto', '=', 'CuentaDetalle.idConcepto')
        ->leftJoin('Pago', 'Pago.idCuenta', '=', 'Cuenta.idCuenta')
        ->leftJoin('Membresia', 'Membresia.idCuenta', '=', 'CuentaDetalle.idCuenta')
        ->leftJoin('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
        ->leftJoin('Persona', 'Persona.idAsociado', '=', 'Asociado.idAsociado')
        ->select(
            'Cuenta.fechaEmision', 
            \DB::raw('CONCAT(Cuenta.serie,"-",Cuenta.numero) as serieNumero'),
            \DB::raw('IFNULL(Empresa.ruc,Persona.documento) as documento'),
            \DB::raw('IFNULL(Empresa.razonSocial,Persona.nombresCompletos) as asociado'),
            'Concepto.idConcepto', 
            'Concepto.descripcion',
            \DB::raw('
            IFNULL(
            GROUP_CONCAT(
            IF(Membresia.mes=1, "Enero",  
            IF(Membresia.mes=2, "Febrero",  
            IF(Membresia.mes=3, "Marzo",  
            IF(Membresia.mes=4, "Abril",  
            IF(Membresia.mes=5, "Mayo",  
            IF(Membresia.mes=6, "Junio",  
            IF(Membresia.mes=7, "Julio",  
            IF(Membresia.mes=8, "Agosto",  
            IF(Membresia.mes=9, "Setiembre",  
            IF(Membresia.mes=10, "Octubre",  
            IF(Membresia.mes=11, "Noviembre",  
            IF(Membresia.mes=12, "Diciembre",  Membresia.masdeuno))))))))))))
            ORDER BY Membresia.mes ASC SEPARATOR ",") 
            ,"-")
            as mes'),
            'CuentaDetalle.cantidad', 
            'CuentaDetalle.total', 
            \DB::raw('IF(Cuenta.estado=1, "Por Cancelar",  IF(Cuenta.estado=2, "Cancelada",  "Anulada")) as estado'),
            \DB::raw('SUM(Pago.monto) as pagado'),
            \DB::raw('
            GROUP_CONCAT(DISTINCT Pago.fecha
            ORDER BY Pago.idPago ASC SEPARATOR ",") as fechas'),
            \DB::raw('Cuenta.fechaAnulacion'),
            \DB::raw('
            GROUP_CONCAT(DISTINCT IF(Pago.banco=1, "BCP",  IF(Pago.banco=2, "BBVA",  IF(Pago.banco=6, "EFECTIVO",  "-")) ) 
            ORDER BY Pago.idPago ASC SEPARATOR ",") as bancos'),
            \DB::raw('Pago.numoperacion'),
            \DB::raw('Pago.numsofdoc'),
            \DB::raw('Pago.montoPaid')
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

        $first
        ->groupBy('Cuenta.fechaEmision')
        ->groupBy('Cuenta.serie')
        ->groupBy('Cuenta.numero')
        ->groupBy('Empresa.ruc')
        ->groupBy('Persona.documento')
        ->groupBy('Empresa.razonSocial')
        ->groupBy('Persona.nombresCompletos')
        ->groupBy('Concepto.idConcepto')
        ->groupBy('Concepto.descripcion')
        ->groupBy('CuentaDetalle.cantidad')
        ->groupBy('CuentaDetalle.total')
        ->groupBy('Cuenta.estado')
        ->groupBy('Cuenta.fechaAnulacion')
        ->groupBy('Pago.numoperacion')
        ->groupBy('Pago.numsofdoc')
        ->groupBy('Pago.montoPaid');

        return $first->orderBy('Cuenta.idCuenta', 'desc')->get();
    }
}
