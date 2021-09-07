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
    
     function __construct(
        $since,
        $until,
        $status,
        $number,
        $idCliente
    ) {
            $this->since = $since;
            $this->until = $until;
            $this->status = $status;
            $this->number = $number;
            $this->idCliente = $idCliente;
     }

    public function headings(): array
    {
        return ["Emision",  "Tipo","Serie-Numero","DNI/RUC", "Cliente", "Total", "Estado", "FechaPago","Anulacion","Observaciones"];
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $first= Caja::join('Cliente', 'Cliente.idCliente', '=', 'Cuenta.idAdquiriente')
        ->select(
            'Cuenta.fechaEmision', 
            \DB::raw('IF(Cuenta.tipoDocumento=1, "F",  IF(Cuenta.tipoDocumento=2, "B",  "NC")) as tipo'),
            \DB::raw('CONCAT(Cuenta.serie,"-",Cuenta.numero) as serieNumero'),
            'Cliente.documento', 
            'Cliente.denominacion', 
            'Cuenta.total', 
            'Cuenta.estado',
            'Cuenta.fechaFinPago',
            'Cuenta.fechaAnulacion',
            'Cuenta.observaciones'
        )->where('Cuenta.serie','like','%108');

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
        
        return $first->orderBy('idCuenta', 'desc')->get();
    }
}

