<?php

namespace App\Exports;

use App\Models\Caja;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CajaDetalleExport implements FromCollection, WithHeadings
{
    protected $since;
    protected $until;
    protected $status;
    protected $number;
    protected $idCliente;
    protected $idArea;
    protected $tipocomprob;
    
     function __construct(
        $since,
        $until,
        $status,
        $number,
        $idCliente,
        $idArea,
        $tipocomprob
    ) {
            $this->since = $since;
            $this->until = $until;
            $this->status = $status;
            $this->number = $number;
            $this->idCliente = $idCliente;
            $this->idArea = $idArea;
            $this->tipocomprob = $tipocomprob;
     }

    public function headings(): array
    {
        return 
        [
            "Emision","Serie-Numero","RUC/DNI","Asociado","CodigoConcepto","Area","Concepto","Cantidad","TotalCobrado","EstadoCuenta","TotalPagado","FechaPago","Anulacion","Banco",
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
        $first= Caja::leftJoin('Pago', 'Pago.idCuenta', '=', 'Cuenta.idCuenta')
        ->join('Cliente', 'Cliente.idCliente', '=', 'Cuenta.idAdquiriente')
        ->join('CuentaDetalle', 'Cuenta.idCuenta', '=', 'CuentaDetalle.idCuenta')
        ->join('Concepto', 'Concepto.idConcepto', '=', 'CuentaDetalle.idConcepto')
        ->join('CategoriaCuenta', 'CategoriaCuenta.idCategoria', 'Concepto.categoriaCuenta')
        ->join('Area', 'Area.idArea', 'CategoriaCuenta.idArea')
        ->select(
            'Cuenta.fechaEmision', 
            \DB::raw('CONCAT(Cuenta.serie,"-",Cuenta.numero) as serieNumero'),
            'Cliente.documento', 
            'Cliente.denominacion', 
            'Concepto.idConcepto', 
            'Area.nombre',
            'Concepto.descripcion',
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
        )->where('Cuenta.serie','like','%108');

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

        if($this->idCliente){
            $first->where('Cuenta.idAdquiriente','=',$this->idCliente);
        }

        if($this->idArea){
            $first->where('CategoriaCuenta.idArea','=',$this->idArea);
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
        ->groupBy('Cliente.documento')
        ->groupBy('Cliente.denominacion')
        ->groupBy('Concepto.idConcepto')
        ->groupBy('Area.nombre')
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
