<?php

namespace App\Helpers;
use App\Models\Colaborador;
use App\Models\Cuenta;
use App\Models\Pago;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

class Helper {

    public static function firebaseSender($title,$tipo,$message,$detail,$receiver,$numoperacion = NULL,$numsofdoc=NULL,$color='danger'){
        if(auth()->user()){
            $ColaboradorAction=Colaborador::find(auth()->user()->idColaborador);
            $message=$ColaboradorAction->nombres.' '.$ColaboradorAction->apellidoPaterno.$message;
        }
        $factory = (new Factory)->withServiceAccount(__DIR__.'/firebase.json');
        $database = $factory->createDatabase();
        $ref = $database->getReference('notifications/'.$receiver);
        $key = $ref->push()->getKey();
        $ref->getChild($key)->set([
            'title' => $title,
            'tipo' => $tipo,
            'description' => $message,
            'seen' => 0,
            'clicked' => false,
            'detail' => $detail,
            'operacion' => $numoperacion,
            'numsofdoc' => $numsofdoc,
            'color' => $color,
            'timestamp' => date('Y-m-d G:i:s')
        ]);
    }

    public static function listRepeated($num,$type)
    {
        $CuentasList= Cuenta::join('Asociado', 'Asociado.idAsociado', '=', 'Cuenta.idAdquiriente')
        ->join('Sector', 'Sector.idSector', '=', 'Asociado.idSector')
        ->join('Pago', 'Pago.idCuenta', '=', 'Cuenta.idCuenta')
        ->leftJoin('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
        ->leftJoin('Persona', 'Persona.idAsociado', '=', 'Asociado.idAsociado')
        ->select(
            'Cuenta.idCuenta', 
            'Cuenta.fechaEmision', 
            \DB::raw('IF(Cuenta.tipoDocumento=1, "F",  IF(Cuenta.tipoDocumento=2, "B",  "NC")) as tipo'),
            \DB::raw('CONCAT(Cuenta.serie,"-",Cuenta.numero) as serieNumero'),
            \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.razonSocial,Persona.nombresCompletos) as asociado'),
            'Cuenta.total', 
            'Cuenta.estado',
            'Sector.descripcion',
            'Cuenta.fechaAnulacion',
            'Cuenta.fechaFinPago',
            \DB::raw(
                "CONCAT(
                    '[',
                    GROUP_CONCAT(
                            JSON_OBJECT(
                                'monto',Pago.monto,
                                'fecha',Pago.fecha,
                                'banco',Pago.banco,
                                'numoperacion',Pago.numoperacion,
                                'numsofdoc',Pago.numsofdoc,
                                'montoPaid',Pago.montoPaid
                            )
                            order by Pago.fecha desc
                        )
                        ,
                        ']'
                    )
                as pagos")
        )
        ->groupBy('Cuenta.idCuenta')
        ->groupBy('Cuenta.fechaEmision')
        ->groupBy('tipo')
        ->groupBy('Cuenta.serie')
        ->groupBy('Cuenta.numero')
        ->groupBy('asociado')
        ->groupBy('Cuenta.total')
        ->groupBy('Cuenta.estado')
        ->groupBy('Sector.descripcion')
        ->groupBy('Cuenta.fechaAnulacion')
        ->groupBy('Cuenta.fechaFinPago')
        ->orderBy('Pago.idPago');
        
        if($type==1){
            $CuentasList->where('Pago.numoperacion',$num);
        }
        
        if($type==2){
            $CuentasList->where('Pago.numsofdoc',$num);
        }

        return $CuentasList->orderBy('fechaEmision', 'desc')->get();
    }

    public static function checkPayInfo($numoperacion,$numsofdoc){
        if($numoperacion){
            $indicadoresOp = Pago::select(
                \DB::raw('SUM(monto) as total'),
                'montoPaid'
            )
            ->where('numoperacion',$numoperacion)
            ->where('estado', 1)
            ->groupBy('montoPaid')
            ->get();
            
            if(sizeof($indicadoresOp)>1){ // Montos de pago distintos
                self::firebaseSender(
                    'Actividad irregular',
                    1,
                    ' ha registrado montos distintos para una misma operación.',
                    self::listRepeated($numoperacion,1),
                    20,
                    null,
                    $numoperacion
                );
            }else{
                if($indicadoresOp[0]->total > $indicadoresOp[0]->montoPaid){ //"Montos mayor al de la operacion"
                    self::firebaseSender(
                        'Actividad irregular',
                        1,
                        ' ha registrado montos mayores (S/.'.$indicadoresOp[0]->total.') al total de una operación (S/.'.$indicadoresOp[0]->montoPaid.').',
                        self::listRepeated($numoperacion,1),
                        20,
                        null,
                        $numoperacion
                    );
                }

            }
        }
    }

    public static function captchaCheck($tokencaptcha){
        
        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
            'form_params' => [
                'secret' =>  env('CAPTCHA_SECRET'),
                'response' => $tokencaptcha
            ]
        ]);
        
        $body = json_decode($response->getBody(), true);
        return $body["success"];
    }

    public static function searchPremium($type,$doc){
        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', 'https://ruc.com.pe/api/v1/consultas', [
            'headers' => [
                'Content-type' => 'application/json; charset=utf-8',
            ],
            \GuzzleHttp\RequestOptions::JSON   => [
                "token" => 'c5ea8f77-e1ab-4626-8674-8e1f77e064cd-b12e1862-d8c0-45fe-9806-a0aa1de01a77',
                "$type" => $doc
            ]
        ]);
        $rpta= $response->getBody()->getContents();
        return json_decode($rpta);
    }

}