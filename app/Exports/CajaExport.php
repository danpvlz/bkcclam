<?php

namespace App\Exports;

use App\Models\Caja;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CajaExport implements FromCollection, WithHeadings
{
    protected $since;
    protected $until;
    protected $status;
    protected $number;
    protected $idCliente;
    protected $idArea;
    
     function __construct(
        $since,
        $until,
        $status,
        $number,
        $idCliente,
        $idArea
    ) {
            $this->since = $since;
            $this->until = $until;
            $this->status = $status;
            $this->number = $number;
            $this->idCliente = $idCliente;
            $this->idArea = $idArea;
     }

    public function headings(): array
    {
        return ["Emision",  "Tipo","Serie-Numero","DNI/RUC", "Cliente", "Total", "Estado", "FechaPago","Anulacion","Observaciones","Area"];
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $first= Caja::
        select(
            'Cuenta.fechaEmision', 
            \DB::raw('IF(Cuenta.tipoDocumento=1, "F",  IF(Cuenta.tipoDocumento=2, "B",  "NC")) as tipo'),
            \DB::raw('CONCAT(Cuenta.serie,"-",Cuenta.numero) as serieNumero'),
            'Cliente.documento', 
            'Cliente.denominacion', 
            'Cuenta.total', 
            'Cuenta.estado',
            'Cuenta.fechaFinPago',
            'Cuenta.fechaAnulacion',
            'Cuenta.observaciones',
            \DB::raw('
            GROUP_CONCAT(DISTINCT Area.nombre
            ORDER BY Area.nombre DESC SEPARATOR ", ")
            as areas')
        )
        ->join('Cliente', 'Cliente.idCliente', '=', 'Cuenta.idAdquiriente')
        ->join('CuentaDetalle', 'CuentaDetalle.idCuenta', '=', 'Cuenta.idCuenta')
        ->join('Concepto', 'Concepto.idConcepto', '=', 'CuentaDetalle.idConcepto')
        ->join('CategoriaCuenta', 'CategoriaCuenta.idCategoria', '=', 'Concepto.categoriaCuenta')
        ->join('Area', 'Area.idArea', '=', 'CategoriaCuenta.idArea')
        ->groupBy('Cuenta.fechaEmision')
        ->groupBy('Cuenta.tipoDocumento')
        ->groupBy('Cuenta.serie')
        ->groupBy('Cuenta.numero')
        ->groupBy('Cliente.documento')
        ->groupBy('Cliente.denominacion')
        ->groupBy('Cuenta.total')
        ->groupBy('Cuenta.estado')
        ->groupBy('Cuenta.fechaFinPago')
        ->groupBy('Cuenta.fechaAnulacion')
        ->groupBy('Cuenta.observaciones')
        ->where('Cuenta.serie','like','%108');

        if($this->status && $this->status<4){
            $first->where('Cuenta.estado','=',$this->status);
            if($this->status==2){
                $first->where('Cuenta.tipoDocumento','<',3);
            }
        }

        if($this->number){
            $first->where('Cuenta.numero','like',$this->number);
        }

        if($this->idCliente){
            $first->where('Cuenta.idAdquiriente','=',$this->idCliente);
        }

        if($this->idArea){
            $first->where('CategoriaCuenta.idArea','=',$this->idArea);
        }

        if($this->since || $this->until){
            $since = $this->since;
            $until = $this->until;
            $status = $this->status;

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
                    })->orWhere(function($query3) use ($since,$until) {
                        if($since){
                            $query3->where('Cuenta.fechaFinPago','>=',$since);
                        }
                        if($until){
                            $query3->where('Cuenta.fechaFinPago','<=',$until);
                        }
                    })->orWhere(function($query3) use ($since,$until,$status) {
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
        
        return $first->orderBy('Cuenta.idCuenta', 'desc')->get();
    }
}

