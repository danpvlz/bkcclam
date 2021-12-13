<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pedido;
use App\Models\Concepto;
use App\Models\Descuento;
use Validator;
use App\Jobs\SendPedidoJob;
use App\Jobs\PayPedidoJob;

class PedidoController extends Controller
{
    public static function validatePedido($values){
        $validated = Validator::make($values,[
            'tipoDoc' => 'required',
            //SI ES RUC
            'razonsocial' => 'required_if:tipoDoc,==,RUC',
            'ruc' => 'required_if:tipoDoc,==,RUC|min:11|max:11',
            //SI ES RUC
            //SI ES DNI
            'dni' => 'required_if:tipoDoc,==,DNI|min:8|max:8',
            'nombres' => 'required_if:tipoDoc,==,DNI',
            'paterno' => 'required_if:tipoDoc,==,DNI',
            'materno' => 'required_if:tipoDoc,==,DNI',
            //SI ES DNI
            'direccion' => 'required_if:tipoDoc,==,RUC',
            'correo' => 'required|max:50',
            'telefono' => 'required|max:25',

            //INFO FACT
            'dctoid' => 'numeric',
            'servid' => 'numeric',
            //INFO FACT
           ],
            [
             'dctoid.numeric'=> 'El código de descuento es inválido.',
             'servid.numeric'=> 'El código del servicio es inválido.',
             'tipoDoc.required'=> 'El tipo de documento es obligatorio.',
             'ruc.required_if' => 'RUC es obligatorio.',
             'dni.required_if' => 'DNI es obligatorio.',
             'ruc.max'=> 'RUC muy largo',
             'dni.max'=> 'DNI muy largo',
             'ruc.min'=> 'RUC muy corto',
             'dni.min'=> 'DNI muy corto',
             'razonsocial.required_if' => 'El campo razón social es obligatorio cuando el tipo de documento es :value.',
             'direccion.required_if' => 'El campo dirección es obligatoria cuando el tipo de documento es :value.',
             'correo.max'=> 'Correo muy largo',
             'telefono.max'=> 'Teléfono muy largo',
             'required' => 'El campo :attribute es obligatorio',
             'required_if' => 'El campo :attribute es obligatorio cuando :other es :value.'
            ]
         );
         
         //Check the validation
         if ($validated->fails())
         {
            return $validated->errors();
         }
    }

    public static function formatNroPedido($param){
        $finalnum=$param."";
        while(strlen($finalnum)<4){
            $finalnum="0".$finalnum;
        }
        return $finalnum;
    }

    public function savePedidoWeb(Request $request)
    {
        try {
            $errors = self::validatePedido($request->all());
            if($errors){
                return response()->json([
                    'error' => true,
                    'message' =>"Ups... Ocurrió un error.",
                    'errors' => $errors
                ]);
            }

            $descuentoMonto = null;
            $conceptoFound = Concepto::where('idConcepto',$request->servid)->first();
            
            //DESCUENTO
            $descuentoFound = Descuento::where('id',$request->dctoid)->first();
            if($descuentoFound){
                $descuentoMonto = $descuentoFound->monto;
            }
            //DESCUENTO
            
            \DB::beginTransaction();
            $Pedido                   =   new Pedido();
            $Pedido->monto            =   $conceptoFound->valorConIGV;
            $Pedido->idConcepto       =   $conceptoFound->idConcepto;
            $Pedido->tipoDoc          =   $request->tipoDoc;
            $Pedido->documento        =   $request->dni ? $request->dni : $request->ruc;
            $Pedido->adquiriente      =   $request->tipoDoc == "DNI" ? strtoupper($request->nombres.($request->paterno ? " ".$request->paterno: "").($request->materno ? " ".$request->materno: "")) : strtoupper($request->razonsocial);
            $Pedido->direccion        =   $request->direccion ? strtoupper($request->direccion) : null;
            $Pedido->correo           =   $request->correo;
            $Pedido->telefono         =   $request->telefono;
            $Pedido->idDescuento      =   $request->dctoid;
            $Pedido->montoDcto        =   $descuentoMonto;
            $Pedido->total            =   $conceptoFound->valorConIGV+5-($descuentoMonto?$descuentoMonto:0);
            $Pedido->save(); 

            \DB::commit();

            // ENVIO CORREO
            $details = [];
            
            $totaltmp=$Pedido->total;

            $details["nro_pedido"]=self::formatNroPedido($Pedido->id);
            $details["fecha_pedido"]=date('d/m/Y h:i:s A',strtotime($Pedido->created_at));
            $details["documento"]=$Pedido->documento." (".$Pedido->tipoDoc.")";
            $details["tipo_doc"]=$Pedido->tipoDoc;
            $details["cliente"]=$Pedido->adquiriente;
            $details["telefono"]=$Pedido->telefono;
            $details["correo"]=$request->correo;
            $details["direccion"]=$Pedido->direccion ? $Pedido->direccion : "-";
            $details["concepto"]=$conceptoFound->descripcion;
            $details["monto_concepto"]=number_format($Pedido->monto, 2, '.', ',');
            $details["descuento"]=$descuentoFound ? $descuentoFound->motivo : null;
            $details["descuento_monto"]=number_format($Pedido->montoDcto, 2, '.', ',');
            $details["total"]=number_format($totaltmp, 2, '.', ',');

            SendPedidoJob::dispatch($details);

            return response()->json([
                'pedido' => $Pedido,
                'concepto' => $conceptoFound
            ], 200);

        } catch (Exception $e) {
            \DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function payPedido(Request $request){
        $Pedido = Pedido::where('id',$request->pedido_id)->first();
        $Pedido->paid=1;
        $Pedido->izimetada=$request->iziresponse;
        $Pedido->save();

        $finalEstadoPago=$request->iziresponse['orderStatus'];
        $finalEstadoPago=$finalEstadoPago == 'PAID' ? 'PAGADO' : ( $finalEstadoPago == 'UNPAID' ? 'NO PAGADO' : ( $finalEstadoPago == 'RUNNING' ? 'EN PROCESO' : ('PARCIALMENTE PAGADO') ) );
        $details = [];
        $details["idPedido"]=$request->pedido_id;
        $details["estadoPago"]=$finalEstadoPago;
        $details["montoPago"]=$request->iziresponse['orderDetails']['orderEffectiveAmount']/100;
        $details["tarjeta"]=$request->iziresponse['transactions'][0]['transactionDetails']['cardDetails']['effectiveBrand']." (".$request->iziresponse['transactions'][0]['transactionDetails']['cardDetails']['pan'].")";
        PayPedidoJob::dispatch($details);
        
        return response()->json([
            'message' => "Pedido pagado!"
        ], 200);
    }

}
