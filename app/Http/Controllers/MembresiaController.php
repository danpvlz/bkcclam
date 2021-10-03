<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use App\Http\Resources\Membresia as MembresiaResourse;
use App\Models\Membresia;
use App\Models\Associated\Associated;

class MembresiaController extends Controller
{

    public static function justMemberships($search){
        $asociado=Associated::
        select(
            \DB::raw('Asociado.idAsociado as id'),
            \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.ruc,Persona.documento) as documento'),
            \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.razonSocial,Persona.nombresCompletos) as razonSocial'),
            \DB::raw('IF(Asociado.tipoAsociado=1, 2,IF(Persona.tipoDocumento=1,1,2)) as tipoDoc'),
            \DB::raw('IF(Asociado.tipoAsociado=1,  Empresa.direccion,Persona.direccion) as direccion'),
            \DB::raw('IF(Asociado.tipoAsociado=1,  Empresa.telefonos,Persona.telefonos) as telefono'),
            \DB::raw('IF(Asociado.tipoAsociado=1,  Empresa.correos,Persona.correos) as correo'),
            'Asociado.estado'
        )
        ->leftJoin('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
        ->leftJoin('Persona', 'Persona.idAsociado', '=', 'Asociado.idAsociado')
        ->where('Asociado.documento','=', $search)
        ->orWhere('Empresa.ruc','=', $search)
        ->orWhere('Persona.documento','=', $search)
        ->first();

        if($asociado){
            if($asociado->estado!=0){
                $membresias=Membresia::
                select(
                    \DB::raw('idMembresia as id'),
                    \DB::raw("
                    CASE
                        WHEN mes = 1 THEN CONCAT('enero',IFNULL(CONCAT(' ',year),''))
                        WHEN mes = 2 THEN CONCAT('febrero',IFNULL(CONCAT(' ',year),''))
                        WHEN mes = 3 THEN CONCAT('marzo',IFNULL(CONCAT(' ',year),''))
                        WHEN mes = 4 THEN CONCAT('abril',IFNULL(CONCAT(' ',year),''))
                        WHEN mes = 5 THEN CONCAT('mayo',IFNULL(CONCAT(' ',year),''))
                        WHEN mes = 6 THEN CONCAT('junio',IFNULL(CONCAT(' ',year),''))
                        WHEN mes = 7 THEN CONCAT('julio',IFNULL(CONCAT(' ',year),''))
                        WHEN mes = 8 THEN CONCAT('agosto',IFNULL(CONCAT(' ',year),''))
                        WHEN mes = 9 THEN CONCAT('setiembre',IFNULL(CONCAT(' ',year),''))
                        WHEN mes = 10 THEN CONCAT('octubre',IFNULL(CONCAT(' ',year),''))
                        WHEN mes = 11 THEN CONCAT('noviembre',IFNULL(CONCAT(' ',year),''))
                        WHEN mes = 12 THEN CONCAT('diciembre',IFNULL(CONCAT(' ',year),''))
                        ELSE masdeuno
                    END as description
                    "),
                    \DB::raw('cobrado - pagado as pending')
                )
                ->where('estado','=', "1")
                ->where('idAsociado','like', $asociado->id)
                ->get();
                $rpta = new \stdClass();
                $rpta->asociado=$asociado;
                $rpta->membresias=$membresias;
                return json_encode($rpta);
            }else{
                throw new Exception('Asociado en retiro.');
            }
        }else{
            throw new Exception('No se encontró asociado.');
        }
    }

    public function membresiasPendientes(Request $request)
    {
        $request->validate([
            'search' => 'required',
        ]);

        try {
            $rpta=self::justMemberships($request->search);
            return response()->json(
                json_decode($rpta)
            , 200);
        }catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
}
