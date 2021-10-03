<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Models\Pago;
use App\Http\Controllers\MembresiaController;


class IziPayController extends Controller
{

    public function init(Request $request){
        $validateData = $request->validate([
            'tokencaptcha' => 'required',
            'items' => 'required'
        ]);
        $items=[];
        //GET ITEMS BY TYPE
            if($request->typeclient==0){
                $items=self::AssociatedCheckIn($request->documento,$request->membresias);
            }
        //GET ITEMS BY TYPE
        $amount=0;
        //PREPARE SHOPPING CART
        $cartItemInfo=[];
        foreach ($items as $key => $item) {
            $amount+=$item['precio']*$item['cantidad'];
            $cartItemInfo[]=[
                "productLabel"          => $item['producto'],
                "productType"          => "SERVICE_FOR_BUSINESS",
                "productAmount"          => $item['precio'],
                "productQty"          => $item['cantidad']
            ];
        }
        $cartItemInfo[]=[
            "productLabel"          => 'TRÁMITE VIRTUAL',
            "productType"          => "SERVICE_FOR_BUSINESS",
            "productAmount"          => 5,
            "productQty"          => 1
        ];
        //PREPARE SHOPPING CART
        //PREPARE METADATA//
            $metadata=$request->all();
            unset($metadata['tokencaptcha']);
            $metadata['itemsfinal']=$items;
        //PREPARE METADATA//
        return response()->json([
            'rpta'=>$cartItemInfo
        ], 200);
    }

    public static function AssociatedCheckIn($document,$sentItems){
        $helper = new MembresiaController;
        $rpta = $helper::justMemberships($document);
        $membershipscur=json_decode($rpta)->membresias;

        $realItems=[];
        foreach ($membershipscur as $key => $item) {
            $found = array_search($item->id, array_column($sentItems, 'id'));
            if($found!==false && array_key_exists("selected",$sentItems[$found]) && $sentItems[$found]['selected']){
                $realItems[]=[
                    "producto"          => mb_strtoupper('Membresía '.$item->description),
                    "precio"          => $item->pending,
                    "cantidad"          => 1
                ];
                
            }
        }
        return $realItems;
    }

    public function ipn(Request $request){
        return self::AssociatedCheckIn();
        //return $request["kr-answer"];
        //Pago::query()->update(['metadata' => json_decode($request["kr-answer"],true)]);
        return json_decode($request["kr-answer"],true);
    }

}
