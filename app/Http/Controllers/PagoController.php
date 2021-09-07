<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pago;
use App\Models\Cuenta;
use App\Http\Resources\Pago as PagoResourse;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PagoAllExport;

class PagoController extends Controller
{
    public function list(Request $request)
    {
        $request->validate([
            'serie' => 'integer',
            'operacion' => 'string',
            'sofdoc' => 'string',
            'since' => 'string',
            'until' => 'string',
            'banco' => 'integer',
            'idAsociado' => 'integer|nullable',
            'idCliente' => 'integer|nullable'
        ]);

        $first= Pago::join('Cuenta', 'Pago.idCuenta', '=', 'Cuenta.idCuenta')
        ->leftJoin('Cliente', 'Cuenta.idAdquiriente', '=', 'Cliente.idCliente')
        ->leftJoin('Asociado', 'Cuenta.idAdquiriente', '=', 'Asociado.idAsociado')
        ->leftJoin('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
        ->leftJoin('Persona', 'Persona.idAsociado', '=', 'Asociado.idAsociado')
        ->select(            
            'Pago.updated_at',
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
                            IF(Pago.banco=5, "CRÉDITO", "-")
                        )
                    )
                )
            )  as bancos'),
            \DB::raw('CONCAT(Cuenta.serie,"-",Cuenta.numero) as serieNumero'),
            'Cuenta.total',
            'Pago.metadata'
        );

        if($request->serie){
            $first->where('Cuenta.serie','like','%'.$request->serie);
        }

        if($request->operacion){
            $first->where('Pago.numoperacion','=',$request->operacion);
        }

        if($request->sofdoc){
            $first->where('Pago.numsofdoc','=',$request->sofdoc);
        }

        if($request->since){
            $first->where('Pago.fecha','>=',$request->since);
        }

        if($request->until){
            $first->where('Pago.fecha','<=',$request->until);
        }

        if($request->banco){
            $first->where('Pago.banco','=',$request->banco);
        }

        if($request->idAsociado && $request->idAsociado != null){
            $first->where('Asociado.idAsociado','=',$request->idAsociado);
        }

        if($request->idCliente && $request->idCliente != null){
            $first->where('Cliente.idCliente','=',$request->idCliente);
        }

        return PagoResourse::collection(
            $first->orderBy('Pago.updated_at', 'desc')->paginate(10)
        );
    }

    public function export(Request $request)
    {
        $request->validate([
            'serie' => 'integer',
            'operacion' => 'integer',
            'sofdoc' => 'integer',
            'since' => 'string',
            'until' => 'string',
            'banco' => 'integer',
            'idAsociado' => 'integer|nullable',
            'idCliente' => 'integer|nullable'
        ]);

        return Excel::download(
            new PagoAllExport(
                $request->serie,
                $request->operacion,
                $request->sofdoc,
                $request->since,
                $request->until,
                $request->banco,
                $request->idAsociado,
                $request->idCliente
            ), 'Pagos.xlsx');
    }
}
