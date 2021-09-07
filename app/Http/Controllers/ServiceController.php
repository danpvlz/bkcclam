<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\Service as ServiceResourse;
use App\Models\Service;
use App\Models\Associated\Associated;

class ServiceController extends Controller
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

    public function list(Request $request)
    {
        $request->validate([
            'since' => 'date',
            'until' => 'date',
            'idAsociado' => 'integer',
            'debCollector' => 'integer',
        ]);

        $first= Associated::
        select(
            'Asociado.idAsociado',
            'Empresa.razonSocial as asociado', 
            'Empresa.ruc as documento',
            'Asociado.tipoAsociado',
            'Asociado.estado',
            'Sector.idSector',
            'Sector.codigo  as sector',
            'Sector.descripcion',
            'Servicio.fecha',
            'Servicio.descripcionServicio',
            'Colaborador.nombres as colaborador'
        )
        ->join('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
        ->join('Servicio', 'Servicio.idAsociado', '=', 'Asociado.idAsociado')
        ->join('users', 'users.idUsuario', '=', 'Servicio.user_create')
        ->join('Colaborador', 'Colaborador.idColaborador', '=', 'users.idColaborador')
        ->join('Sector', 'Sector.idSector', '=', 'Asociado.idSector');

        if($request->since){
            $first->where('fecha','>=',$request->since);
        }

        if($request->until){
            $first->where('fecha','<=',$request->until);
        }

        if($request->idAsociado){
            $first->where('Asociado.idAsociado',$request->idAsociado);
        }

        if($request->debCollector){
            $first->where('Sector.idSector',$request->debCollector);
        }

        $final= Associated::
        select(     
            'Asociado.idAsociado',
            'Persona.nombresCompletos as asociado', 
            'Persona.documento as documento',
            'Asociado.tipoAsociado',
            'Asociado.estado',
            'Sector.idSector',
            'Sector.codigo  as sector',
            'Sector.descripcion',
            'Servicio.fecha',
            'Servicio.descripcionServicio',
            'Colaborador.nombres'
        )
        ->join('Persona', 'Persona.idAsociado', '=', 'Asociado.idAsociado')
        ->join('Servicio', 'Servicio.idAsociado', '=', 'Asociado.idAsociado')
        ->join('users', 'users.idUsuario', '=', 'Servicio.user_create')
        ->join('Colaborador', 'Colaborador.idColaborador', '=', 'users.idColaborador')
        ->join('Sector', 'Sector.idSector', '=', 'Asociado.idSector');

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

        $final
        ->union($first);
        
        return ServiceResourse::collection(
            $final
            ->orderBy('fecha', 'desc')
            ->paginate(10));        
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
                'actividad' => 'string',
                'servicio' => 'string',
                'idAsociado' => 'required',
            ]);
            
            \DB::beginTransaction();
            
            $Service = new Service();
            $Service->fecha =  $request->fecha; 
            $Service->descripcionServicio =  $request->servicio; 
            $Service->idAsociado =  $request->idAsociado; 
            $Service->user_create =  auth()->user()->idUsuario; 
            $Service->save(); 

            \DB::commit();

            return response()->json([
                'message' => 'Servicio registrado.',
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
