<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Models\Pedido;
use App\Models\Pago;
use App\Models\Concepto;
use App\Models\Descuento;
use App\Http\Controllers\MembresiaController;
use Validator;
use App\Http\Controllers\CajaController;
use App\Jobs\PayPedidoJob;

class IziPayController extends Controller
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

    public function initweb(Request $request){
        try {
            $errors = self::validatePedido($request->all());
            if($errors){
                return response()->json([
                    'error' => true,
                    'message' =>"Ups... Ocurrió un error.",
                    'errors' => $errors
                ]);
            }
            
            $ammountFinal = 0;
            $descuentoMonto = null;
            $conceptoFound = Concepto::where('idConcepto',$request->servid)->first();
            $ammountFinal = $conceptoFound->valorConIGV + 5;
            
            //DESCUENTO
                $descuentoFound = Descuento::where('id',$request->dctoid)->first();
                if($descuentoFound){
                    $descuentoMonto = $descuentoFound->monto;
                    $ammountFinal = $ammountFinal - $descuentoFound->monto;
                }
            //DESCUENTO
            
            //GUARDAR PEDIDO
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
                $Pedido->origen           =   2;
                $Pedido->save(); 
            //GUARDAR PEDIDO

            //PREPARE SHOPPING CART
                $cartItemInfo=[];
                $cartItemInfo[]=[
                    "productRef"            => $conceptoFound->idConcepto,
                    "productLabel"          => $conceptoFound->descripcion,
                    "productType"           => "SERVICE_FOR_BUSINESS",
                    "productAmount"         => $conceptoFound->valorConIGV*100,
                    "productQty"            => 1
                ];
                $cartItemInfo[]=[
                    "productRef"            => 	210, //CAMBIAR POR ID DE TRÁMITE VIRTUAL
                    "productLabel"          => 'TRÁMITE VIRTUAL',
                    "productType"           => "SERVICE_FOR_BUSINESS",
                    "productAmount"         => 500,
                    "productQty"            => 1
                ];
            //PREPARE SHOPPING CART
            
            $ammountFinal = $ammountFinal *100;
            $json_order = [
                "orderId"				            => $Pedido->id,
                "ipnTargetUrl"				        => "https://www.cclam.org.pe/recursos.base/public/api/ipn",
                "metadata"				            => $request->all(),
                "amount"				            => $ammountFinal,
                "currency"				            => "PEN",
                "customer" => [
                        "email"                     => $request->correo,
                        "billingDetails"            => [
                        "firstName"                 => $request->tipoDoc == "DNI" ? $request->nombres : "",
                        "lastName"                  => $request->paterno.' '.$request->materno,
                        "phoneNumber"               => $request->telefono,
                        "address"                   => $request->direccion,
                        "legalName"                 => $request->tipoDoc == "RUC" ? $request->razonsocial : "", //RAZON SOCIAL
                        "identityCode"              => $request->tipoDoc == "DNI" ? $request->dni : $request->ruc, // DNI ¿ruc?
                        "category"                  => $request->tipoDoc == "DNI" ? 'PRIVATE' : 'COMPANY', // PRIVATE - COMPANY
                        "cellPhoneNumber"           => $request->telefono,
                        "language"                  => "ES"
                    ],
                "shoppingCart" => [
                        "cartItemInfo"              => $cartItemInfo
                    ]
                ]
            ];
            
            $client = new \GuzzleHttp\Client();
            $response = $client->request('POST', 'https://api.micuentaweb.pe/api-payment/V4/Charge/CreatePayment', [
                'headers' => [
                    'Content-type' => 'application/json; charset=utf-8',
                    'Authorization'     => 'Basic '.env('IZI_KEY')
                ],
                \GuzzleHttp\RequestOptions::JSON   => $json_order
            ]);
            
            $body = json_decode($response->getBody(), true);

            if($body['status']==='SUCCESS'){
                \DB::commit();
                return $body['answer'];
            }else{
                \DB::rollback();
                return response()->json([
                    'message' => $body['answer']['errorMessage']
                ], 500);
            }
            
        } catch (Exception $e) {
            \DB::rollback();
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function ipn(Request $request){
        $rpta=json_decode($request["kr-answer"],true);
        if($Pedido->paid==0){
            $Pedido->paid=1;
            $Pedido = Pedido::where('id',$rpta['orderDetails']['orderId'])->first();
            $Pedido->izimetada=json_decode($request["kr-answer"],true);
            $Pedido->save();
    
            $finalEstadoPago=$rpta['orderStatus'];
            $finalEstadoPago=$finalEstadoPago == 'PAID' ? 'PAGADO' : ( $finalEstadoPago == 'UNPAID' ? 'NO PAGADO' : ( $finalEstadoPago == 'RUNNING' ? 'EN PROCESO' : ('PARCIALMENTE PAGADO') ) );
            $details = [];
            $details["idPedido"]=$rpta['orderDetails']['orderId'];
            $details["estadoPago"]=$finalEstadoPago;
            $details["montoPago"]=$rpta['transactions'][0]['transactionDetails']['effectiveAmount']/100;
            $details["tarjeta"]=$rpta['transactions'][0]['transactionDetails']['cardDetails']['effectiveBrand']." (".$rpta['transactions'][0]['transactionDetails']['cardDetails']['pan'].")";
            PayPedidoJob::dispatch($details);
            
            return response()->json([
                'message' => "Pedido pagado!"
            ], 200);
        }
        
        return response()->json([
            'message' => "Pedido ya estaba pagado!"
        ], 200);
    }

    public function ipnBK(Request $request){
        $rpta=json_decode($request["kr-answer"],true);
        /*ACTUALIZAR PEDIDO*/
        $Pedido = Pedido::where('id',$rpta['orderDetails']['orderId'])->first();
        $Pedido->paid=1;
        $Pedido->save();
        /*ACTUALIZAR PEDIDO*/
        $dataParaCuenta=new \stdClass();
        $dataParaCuenta->fechaEmision=date('Y-m-d',strtotime($rpta['serverDate']));
        $dataParaCuenta->fechaVencimiento=date('Y-m-d',strtotime($rpta['serverDate']));
        $dataParaCuenta->correo= $rpta['customer']['email'];
        $dataParaCuenta->observacion= "";  //SIN OBSERVACIÓN
        $dataParaCuenta->numoperacion= $rpta['transactions'][0]['uuid'];
        $dataParaCuenta->numsofdoc= null;
        $dataParaCuenta->docModificar= [];
        $dataParaCuenta->docModificar["tipo"]=null;
        $dataParaCuenta->docModificar["serie"]=null;
        $dataParaCuenta->docModificar["numero"]=null;
        $dataParaCuenta->montoPaid= $rpta['transactions'][0]['amount']/100;
        $dataParaCuenta->idCliente= 5;
        $dataParaCuenta->tipo_de_comprobante= $rpta['customer']['billingDetails']['category']==="PRIVATE" ? 2 : 1; //1:Factura 2:Boleta;
        $dataParaCuenta->pagado= 2; //Siempre pagado;
        $dataParaCuenta->opcion= 1; //BANCO COMO ES IZI, VA A BCP;
        $dataParaCuenta->typeChange= 1; //MONEDA;
        $dataParaCuenta->items=[];
        $itemstemp=[];
        //DETALLE
            //CONCEPTO RECIBIDO
                $cardItemInfo=array_filter($rpta['customer']['shoppingCart']['cartItemInfo'],function ($var){
                    return($var['productRef'] != 210);
                });
                foreach ($cardItemInfo as $k => $v) {
                    $itemstemp[$k]['detail']=""; //SIN DETALLE
                    $itemstemp[$k]['price']=$v['productAmount'];
                    $itemstemp[$k]['ammount']=1;//SIEMPRE 1
                    $itemstemp[$k]['subtotal']=$v['productAmount'];
                    $itemstemp[$k]['igv']=1; //GRAVADA
                    $itemstemp[$k]['idConcepto']=$v['productRef'];
                    $itemstemp[$k]['labelConcepto']=mb_strtoupper($v['productLabel']);
                }
            //CONCEPTO RECIBIDO
            //CONCEPTO TRÁMITE VIRTUAL
                $itemstemp[sizeof($cardItemInfo)]['detail']=""; //SIN DETALLE
                $itemstemp[sizeof($cardItemInfo)]['price']=5; //PRECIO 5 DEL CONCEPTO
                $itemstemp[sizeof($cardItemInfo)]['ammount']=1; //UN TRÁMITE VIRTUAL
                $itemstemp[sizeof($cardItemInfo)]['subtotal']=5; //PRECIO 5 DEL CONCEPTO
                $itemstemp[sizeof($cardItemInfo)]['igv']=1; //GRAVADA
                $itemstemp[sizeof($cardItemInfo)]['idConcepto']=210; //CAMBIAR POR ID DE TRÁMITE VIRTUAL
                $itemstemp[sizeof($cardItemInfo)]['labelConcepto']='TRÁMITE VIRTUAL'; //NOMBRE CONCEPTO TRÁMITE VIRTUAL
            //CONCEPTO TRÁMITE VIRTUAL
            $dataParaCuenta->items=$itemstemp;
        //DETALLE

        $helper = new CajaController;
        $rpta = $helper::saveCajaCuenta($dataParaCuenta);
        $rptaJSON = json_decode($rpta);
        if(property_exists($rptaJSON,'error')){
            return response()->json([
                'message' => $rptaJSON->message,
            ],500);
        }
        $Pedido->idCuenta=$rptaJSON->idCuenta;
        $Pedido->save();
        
        return "Pedido guardado";
    }
}
