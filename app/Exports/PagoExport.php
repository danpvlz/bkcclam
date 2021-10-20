<?php

namespace App\Exports;

use App\Models\Pago;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class PagoExport implements FromCollection, WithHeadings, WithTitle
{
    protected $serie;
    protected $operacion;
    protected $sofdoc;
    protected $since;
    protected $until;
    protected $banco;
    protected $idAsociado;
    protected $idCliente;
    function __construct(
        $serie,
        $operacion,
        $sofdoc,
        $since,
        $until,
        $banco,
        $idAsociado,
        $idCliente
   ) {
           $this->serie = $serie;
           $this->operacion = $operacion;
           $this->sofdoc = $sofdoc;
           $this->since = $since;
           $this->until = $until;
           $this->banco = $banco;
           $this->idAsociado = $idAsociado;
           $this->idCliente = $idCliente;
    }

    public function headings(): array
    {
        return [
            "Fecha", 
            "Asociado/Cliente",
            "Monto operación",
            "Num. Operación",
            "Num. Sofydoc",
            "Banco",
            "Serie-Número",
            "Monto Fact."
        ];
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function title(): string
    {
        switch ($this->banco) {
            case 1:
                return 'BCP';
                break;
            case 2:
                return 'BBVA';
                break;
            case 3:
                return 'BANCOS';
                break;
            case 4:
                return 'CONTADO';
                break;
            case 5:
                return 'CRÉDITO';
                break;
            case 6:
                return 'EFECTIVO';
                break;
            default:
            return 'OTROS';
                break;
        }
    }

    public function collection()
    {
        $first= Pago::join('Cuenta', 'Pago.idCuenta', '=', 'Cuenta.idCuenta')
        ->leftJoin('Cliente', 'Cuenta.idAdquiriente', '=', 'Cliente.idCliente')
        ->leftJoin('Asociado', 'Cuenta.idAdquiriente', '=', 'Asociado.idAsociado')
        ->leftJoin('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
        ->leftJoin('Persona', 'Persona.idAsociado', '=', 'Asociado.idAsociado')
        ->select(
            'Pago.fecha',   
            \DB::raw('IF(Cuenta.serie like "%108%", Cliente.denominacion,  IF(Asociado.tipoAsociado=1, Empresa.razonSocial,Persona.nombresCompletos)) as adquiriente'),
            'Pago.montoPaid', 
            'Pago.numoperacion', 
            'Pago.numsofdoc', 
            \DB::raw('
            IF(
                Pago.banco=1, "BCP",  
                IF(Pago.banco=2, "BBVA",  
                    IF(Pago.banco=3, "BANCOS",  
                        IF(Pago.banco=4, "CONTADO", 
                            IF(Pago.banco=5, "CRÉDITO", IF(Pago.banco=6, "EFECTIVO", "-"))
                        )
                    )
                )
            )  as bancos'),
            \DB::raw('CONCAT(Cuenta.serie,"-",Cuenta.numero) as serieNumero'),
            'Cuenta.total'
        );

        if($this->serie){
            $first->where('Cuenta.serie','like','%'.$this->serie);
        }

        if($this->operacion){
            $first->where('Pago.numoperacion','=',$this->operacion);
        }

        if($this->sofdoc){
            $first->where('Pago.numsofdoc','=',$this->sofdoc);
        }

        if($this->since){
            $first->where('Pago.fecha','>=',$this->since);
        }

        if($this->until){
            $first->where('Pago.fecha','<=',$this->until);
        }

        if($this->banco){
            $first->where('Pago.banco','=',$this->banco);
        }

        if($this->idAsociado && $this->idAsociado != null){
            $first->where('Asociado.idAsociado','=',$this->idAsociado);
        }

        if($this->idCliente && $this->idCliente != null){
            $first->where('Cliente.idCliente','=',$this->idCliente);
        }
        
        $first->where('Pago.estado','=',1);
        
        return $first->orderBy('Pago.updated_at', 'desc')->get();
    }
}
