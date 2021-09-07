<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use App\Http\Resources\PhoneCalls as PhoneCallsResourse;
use App\Models\PhoneCalls;
use App\Models\Associated\Associated;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PhoneCallsExport;

class PhoneCallsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function listPhoneCalls(Request $request)
    {
        $request->validate([
            'since' => 'date',
            'until' => 'date',
            'idAsociado' => 'integer',
            'debCollector' => 'integer',
        ]);

        $final= Associated::
        select(
            'Asociado.idAsociado',
            \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.razonSocial,Persona.nombresCompletos) as asociado'),
            \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.ruc,Persona.documento) as documento'),
            'Asociado.tipoAsociado',
            'Asociado.estado',
            'Sector.idSector',
            'Sector.codigo as sector',
            'Sector.descripcion',
            'Llamada.idLlamada',
            'Llamada.fecha',
            'Llamada.horaInicio',
            'Llamada.horaFin',
            'Llamada.detalle',
            'Colaborador.nombres as colaborador'
        )
        ->leftJoin('Persona', 'Persona.idAsociado', '=', 'Asociado.idAsociado')
        ->leftJoin('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
        ->join('Llamada', 'Llamada.idAsociado', '=', 'Asociado.idAsociado')
        ->join('Sector', 'Sector.idSector', '=', 'Asociado.idSector')
        ->join('users', 'users.idUsuario', '=', 'Llamada.idUsuario')
        ->join('Colaborador', 'Colaborador.idColaborador', '=', 'users.idColaborador');

        if($request->since){
            $final->where('fecha','>=',$request->since);
        }

        if($request->until){
            $final->where('fecha','<=',$request->until);
        }

        if($request->idAsociado){
            $final->where('Asociado.idAsociado',$request->idAsociado);
        }

        if($request->debCollector){
            $final->where('Sector.idSector',$request->debCollector);
        }
        
        return PhoneCallsResourse::collection(
            $final
            ->orderBy('fecha', 'desc')
            ->orderBy('horaInicio', 'desc')
            ->paginate(10));        
    }

    public function export(Request $request)
    {
        $request->validate([
            'since' => 'date',
            'until' => 'date',
            'idAsociado' => 'integer',
            'debCollector' => 'integer',
        ]);

        return Excel::download(
            new PhoneCallsExport(
                $request->since,
                $request->until,
                $request->idAsociado,
                $request->debCollector
            ), 'llamadas.xlsx');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'fecha' => 'required',
                'horaInicio' => 'required',
                'horaFin' => 'required',
                'detalle' => 'string',
                'idAsociado' => 'required',
            ]);
            
            \DB::beginTransaction();
            
            $PhoneCalls = new PhoneCalls();
            $PhoneCalls->fecha =  $request->get('fecha'); 
            $PhoneCalls->horaInicio =  $request->get('horaInicio'); 
            $PhoneCalls->horaFin =  $request->get('horaFin'); 
            $PhoneCalls->detalle =  $request->get('detalle'); 
            $PhoneCalls->idAsociado =  $request->get('idAsociado'); 
            $PhoneCalls->idUsuario =  auth()->user()->idUsuario; 
            $PhoneCalls->save(); 

            \DB::commit();

            return response()->json([
                'message' => 'Llamada registrada',
            ], 200);
        

        } catch (Exception $e) {
            \DB::rollback();
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
