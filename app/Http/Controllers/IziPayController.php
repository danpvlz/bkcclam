<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Models\Pago;

class IziPayController extends Controller
{

    public function init(Request $request){
        $validateData = $request->validate([
            //'email' => 'required',
            //'nombres' => 'required',
            //'apellidos' => 'required',
            //'telefono' => 'required',
            //'razonSocial' => 'required',
            //'direccion' => 'required',
            //'documento' => 'required',
            //'tipoCliente' => 'required',
            'tokencaptcha' => 'required',
            'items' => 'required'
        ]);
        $helper = new Helper;
        $rcres = $helper::captchaCheck($request->tokencaptcha);
        if($rcres){
            try {
                $amount=0;
                $cartItemInfo=[];
                foreach ($request->items as $key => $item) {
                    $amount+=$item['precio']*$item['cantidad'];
                    $cartItemInfo[]=[
                        "productLabel"          => $item['producto'],
                        "productType"          => "SERVICE_FOR_BUSINESS",
                        "productAmount"          => $item['precio'],
                        "productQty"          => $item['cantidad']
                    ];
                }
    
                $json_order = [
                    "ipnTargetUrl"				            => "https://www.cclam.org.pe/recursos.base/api/ipn",
                    "metadata"				            => $request->all(),
                    "amount"				            => $amount*100,
                    "currency"				            => "PEN",
                    "customer" => [
                        "email"          => $request->correo,
                        "billingDetails"          => [
                            "firstName"          => $request->nombres,
                            "lastName"          => $request->apellidoP.' '.$request->apellidoM,
                            "phoneNumber"          => $request->telefono,
                            "address"          => $request->direccion,
                            "legalName"          => $request->razonSocial, //RAZON SOCIAL
                            "identityCode"          => $request->documento, // DNI ¿ruc?
                            "category"          => $request->tipoDoc === 1 ? 'PRIVATE' : 'COMPANY', // PRIVATE - COMPANY
                            "cellPhoneNumber"          => $request->telefono,
                            "language"          => "ES"
                        ],
                        "shoppingCart"         => [
                            "cartItemInfo" => $cartItemInfo
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
                    return $body['answer'];
                }else{
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
            
        }else{
            return response()->json([
                'message' => 'Captcha inválido',
            ], 500);
        }
    }

    public function ipn(Request $request){
        //return $request["kr-answer"];
        //Pago::query()->update(['metadata' => json_decode($request["kr-answer"],true)]);
        return json_decode($request["kr-answer"],true);
    }

}
