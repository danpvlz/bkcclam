<?php

namespace App\Http\Controllers\FormacionYDesarrollo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Exception;
use App\Http\Resources\FormacionYDesarrollo\Participante as ParticipanteResourse;
use App\Models\FormacionYDesarrollo\Participante;
use App\Models\Cliente;


class ParticipanteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request)
    {
        $ParticipanteList = Participante::where('active',1)->orderBy('Participante.idParticipante','desc');
        
        if($request->searchParticipante){
            $ParticipanteList->where('nombres','like','%'.$request->searchParticipante.'%');
        }

        return ParticipanteResourse::collection($ParticipanteList->paginate(10));
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
            $validateData = $request->validate([
                'dni' => 'required',
                'nombres' => 'required',
                'paterno' => 'required',
                'materno' => 'required'
            ]);

            $existParticipante = Participante::where('active',1)->where('dni', $request->dni)->first();
            if($existParticipante != null)
                return response()->json([
                    'message' => 'Ya existe un participante con este DNI',
                ], 400);
            
            \DB::beginTransaction();

            $Participante = new Participante();
            $Participante->dni = $request->dni;
            $Participante->nombres = $request->nombres;
            $Participante->apellidoPaterno = $request->paterno;
            $Participante->apellidoMaterno = $request->materno;
            $Participante->celular = $request->celular;
            $Participante->correo = $request->correo;
            $Participante->ruc = $request->ruc;
            $Participante->empresa = $request->empresa;
            $Participante->cargo = $request->cargo;
            
                //CLIENTE
                $clientSearched = Cliente::where('documento','=',$request->dni)->first();
                if($clientSearched){
                    $Participante->idCliente = $clientSearched->idCliente;
                }else{
                    $newClient = new Cliente();
                    $newClient->tipoDocumento =  1;
                    $newClient->documento =  $request->dni;
                    $newClient->denominacion =  mb_strtoupper($request->nombres.' '.$request->paterno.' '.$request->materno, 'UTF-8');
                    $newClient->direccion =  $request->direccion ? $request->direccion : '---';
                    $newClient->email =  $request->correo;
                    $newClient->telefono =  $request->celular;
                    $newClient->user_create = 0;
                    $newClient->user_update = 0;
                    $newClient->save(); 
                    $Participante->idCliente = $newClient->idCliente;
                }
                //CLIENTE

            $Participante->user_create = Auth::user()->idUsuario;
            $Participante->user_update = Auth::user()->idUsuario;
            $Participante->save();

            \DB::commit();
            return response()->json([
                'message' => 'Participante registrado',
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
        if(!$id)
            return response()->json([
                'message' => 'ID de participante inválido',
            ], 400);

        $Participante = Participante::find($id);
    
        if(is_null($Participante) || $Participante->active==0)
            return response()->json([
                'message' => 'Participante no encontrado',
            ], 400);

        return $Participante;
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
        try {

            if(!$id)
                return response()->json([
                    'message' => 'ID de participante inválido',
                ], 400);

            $validateData = $request->validate([
                'dni' => 'required|min:8|max:8',
                'nombres' => 'required',
                'paterno' => 'required',
                'materno' => 'required',
                'celular' => 'required|numeric',
                'correo' => 'email',
                'ruc' => 'required|min:11|max:11',
                'empresa' => 'max:100',
                'cargo' => 'max:50',
            ]);

            $Participante = Participante::find($id);
    
            if(is_null($Participante) || $Participante->active==0)
            return response()->json([
                'message' => 'Participante no encontrado',
            ], 400);
            
            \DB::beginTransaction();

            $Participante->dni = $request->dni;
            $Participante->nombres = $request->nombres;
            $Participante->apellidoPaterno = $request->paterno;
            $Participante->apellidoMaterno = $request->materno;
            $Participante->celular = $request->celular;
            $Participante->correo = $request->correo;
            $Participante->ruc = $request->ruc;
            $Participante->empresa = $request->empresa;
            $Participante->cargo = $request->cargo;
            $Participante->user_update = Auth::user()->idUsuario;
            $Participante->save();

            \DB::commit();
            return response()->json([
                'message' => 'Participante actualizado',
            ], 200);

        } catch (Exception $e) {
            \DB::rollback();
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(!$id)
            return response()->json([
                'message' => 'ID de participante inválido',
            ], 400);

        $Participante=Participante::find($id);
    
        if(is_null($Participante) || $Participante->active==0)
            return response()->json([
                'message' => 'Participante no encontrado',
            ], 400);

        $Participante->active=0;
        $Participante->save();
        return response()->json([
            'message' => 'Participante eliminado',
        ], 200);
    }
    
    public function filterData(Request $request)
    {
         
        $participantFilter = Participante::
            selectRaw('idParticipante as value, CONCAT(nombres, " ",apellidoPaterno, " ",apellidoMaterno, " [",dni,"]") as label, dni');

        if($request->isId){
            $participantFilter->where('idParticipante',$request->search);
        }else{
            $participantFilter
            ->where('dni','like', '%'.$request->search."%")
            ->orWhere('nombres','like', '%'.$request->search."%")
            ->orWhere('apellidoPaterno','like', '%'.$request->search."%")
            ->orWhere('apellidoMaterno','like', '%'.$request->search."%");
        }

        return $participantFilter->get();
    }
}
