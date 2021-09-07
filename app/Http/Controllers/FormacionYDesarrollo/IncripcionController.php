<?php

namespace App\Http\Controllers\FormacionYDesarrollo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Exception;
use App\Http\Resources\FormacionYDesarrollo\Inscripcion as InscripcionResourse;
use App\Models\FormacionYDesarrollo\Inscripcion;
use App\Models\FormacionYDesarrollo\Curso;
use App\Models\FormacionYDesarrollo\Participante;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\InscripcionExport;
use App\Models\Concepto;
use App\Models\Cliente;

use App\Helpers\Helper;

class IncripcionController extends Controller
{

    public function export(Request $request)
    {
        $request->validate([
            'curso' => 'integer',
            'participante' => 'integer',
        ]);   
        return Excel::download(
            new InscripcionExport(
                $request->curso,
                $request->participante
            ), 'Estructura_certificados.xlsx');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list(Request  $request)
    {
        $listInscription = Inscripcion::
        join('Curso', 'Inscripcion.idCurso', '=', 'Curso.idCurso')
        ->join('Participante', 'Inscripcion.idParticipante', '=', 'Participante.idParticipante')
        ->select(
            'Inscripcion.idInscripcion', 
            'Inscripcion.pagado', 
            'Inscripcion.updated_at as fecha',
            'Curso.descripcion as curso',
            'Participante.dni',
            'Participante.celular',
            'Participante.nombres',
            'Participante.apellidoPaterno',
            'Participante.apellidoMaterno')
        ->where('Inscripcion.active',1);

        if($request->curso){
            $listInscription->where('Inscripcion.idCurso',$request->curso);
        }

        if($request->participante){
            $listInscription->where('Inscripcion.idParticipante',$request->participante);
        }

        return InscripcionResourse::collection(
            $listInscription->orderBy('fecha','desc')->paginate(10)
        );
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
                'items' => 'required',
            ]);
            
            \DB::beginTransaction();

            $newCourses=[];
            foreach ($request->items as $key => $item) {

                $curso = $item['curso'];
                $participant = $item['participant'];

                $Inscripcion = new Inscripcion();
                $Inscripcion->idParticipante = $participant['value'];
                if( isset($curso['new']) && $curso['new'] ){
                    if(!in_array($curso['value'], $newCourses)){
                        $Course = new Curso();
                        $Course->descripcion = $curso['label'];
                        $Course->user_create = Auth::user()->idUsuario;
                        $Course->user_update = Auth::user()->idUsuario;
                
                        $Concepto                   =   new Concepto();
                        $Concepto->codigo           =   Concepto::max('idConcepto')+1;
                        $Concepto->descripcion      =   $curso['label']; 
                        $Concepto->tipoConcepto	    =   1; 
                        $Concepto->tipoIGV          =   1; 
                        $Concepto->valorConIGV      =   $request->valor; 
                        $Concepto->priceInmutable   =   0; 
                        $Concepto->categoriaCuenta  =   2; 
            
                        $Concepto->user_create = auth()->user()->idUsuario;
                        $Concepto->user_update = auth()->user()->idUsuario;
                        $Concepto->save(); 
                        $Course->idConcepto = $Concepto->idConcepto;
                        $Course->save();
                        $newCourses[$Course->idCurso]=$curso['value'];

                        $Inscripcion->idCurso = $Course->idCurso;
                    }else{
                        $idCurso = array_search($curso['value'], $newCourses);
                        $Inscripcion->idCurso = $idCurso;
                    }
                }else{
                    $existInscripcion = Inscripcion::where('idParticipante', $participant['value'])->where('idCurso', $curso['value'])->first();
                    if($existInscripcion)
                        return response()->json([
                            'message' => 'Ya existe esta inscripción',
                        ], 400);

                    $Inscripcion->idCurso = $curso['value'];
                }
                $Inscripcion->user_create = Auth::user()->idUsuario;
                $Inscripcion->user_update = Auth::user()->idUsuario;
                $Inscripcion->save();    
            }
            \DB::commit();
            return response()->json([
                'message' => 'Inscripcion registrada',
            ], 200);

        } catch (Exception $e) {
            \DB::rollback();
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function externalInscription(Request $request)
    {
        try {
            
            \DB::beginTransaction();

            $Inscripcion = new Inscripcion();

            if(is_numeric($request->idCurso)){
                $idCurso = $request->idCurso;
            }else{
                $idCurso = base64_decode($request->idCurso);
            }

            $participantSearched = Participante::where('active',1)->where('dni','=',$request->dni)->first();
            $nombresParticipante= "";
            if($participantSearched){
                $existInscripcion = Inscripcion::where('idParticipante', $participantSearched->idParticipante)->where('idCurso', $idCurso)->first();
                if($existInscripcion){
                    return response()->json([
                        'message' => 'Ya existe esta inscripción',
                    ], 400);
                }

                $Inscripcion->idParticipante = $participantSearched->idParticipante;
                $nombresParticipante = $participantSearched->nombres.' '.$participantSearched->apellidoPaterno.' '.$participantSearched->apellidoMaterno;
            }else{
                $newParticipant = new Participante();
                $newParticipant->nombres = $request->nombres;
                $newParticipant->apellidoPaterno = $request->paterno;
                $newParticipant->apellidoMaterno = $request->materno;
                $newParticipant->dni = $request->dni;
                $newParticipant->celular = $request->celular;
                $newParticipant->correo = $request->correo;
                $newParticipant->ruc = $request->ruc;
                $newParticipant->empresa = $request->empresa;
                $newParticipant->cargo = $request->cargo;

                //CLIENTE
                $clientSearched = Cliente::where('documento','=',$request->dni)->first();
                if($clientSearched){
                    $newParticipant->idCliente = $clientSearched->idCliente;
                }else{
                    $newClient = new Cliente();
                    $newClient->tipoDocumento =  strlen($request->dni)>8 ? 4 : 1;
                    $newClient->documento =  $request->dni;
                    $newClient->denominacion =  mb_strtoupper($request->nombres.' '.$request->paterno.' '.$request->materno, 'UTF-8');
                    $newClient->direccion =  $request->direccion ? $request->direccion : '---';
                    $newClient->email =  $request->correo;
                    $newClient->telefono =  $request->celular;
                    $newClient->user_create = 0;
                    $newClient->user_update = 0;
                    $newClient->save(); 
                    $newParticipant->idCliente = $newClient->idCliente;
                }
                //CLIENTE

                $newParticipant->user_create = 0;
                $newParticipant->user_update = 0;
                $newParticipant->save();

                $Inscripcion->idParticipante = $newParticipant->idParticipante;
                $nombresParticipante = $newParticipant->nombres.' '.$newParticipant->apellidoPaterno.' '.$newParticipant->apellidoMaterno;
            }

            $Inscripcion->idCurso = $idCurso;
            $Inscripcion->user_create = 0;
            $Inscripcion->user_update = 0;
            $Inscripcion->save();    
            \DB::commit();
            
            $cursoIns = Curso::find($idCurso);
            $helper = new Helper;
            $helper::firebaseSender(
                'Nueva inscripción',
                4, //tipo
                $nombresParticipante.' se ha registrado en el curso '.$cursoIns->descripcion,
                null,
                5, //receiver
                null,
                null,
                'success'
            );
            
            return response()->json([
                'message' => 'Inscripcion registrada',
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
                'message' => 'ID de inscripción inválido',
            ], 400);

        $Inscripcion = Inscripcion::find($id);
    
        if(is_null($Inscripcion) || $Inscripcion->active==0)
            return response()->json([
                'message' => 'Inscripción no encontrada',
            ], 400);
        
        $query= DB::table('Inscripcion')
        ->join('Curso', 'Inscripcion.idCurso', '=', 'Curso.idCurso')
        ->join('Participante', 'Inscripcion.idParticipante', '=', 'Participante.idParticipante')
        ->select(
            'Inscripcion.idInscripcion', 
            'Inscripcion.idParticipante', 
            'Inscripcion.idCurso',
            'Inscripcion.pagado'
            )
        ->where('Inscripcion.active',1)
        ->where('Inscripcion.idInscripcion','=',$id);
            
        return $query->get();
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
                
            $validateData = $request->validate([
                'idParticipante' => 'required',
                'idCurso' => 'required',
            ]);

            $Inscripcion = Inscripcion::find($id);
        
            if(is_null($Inscripcion) || $Inscripcion->active==0)
                return response()->json([
                    'message' => 'Inscripción no encontrada',
                ], 400);

            $existInscripcion = Inscripcion::where('idParticipante', $request->idParticipante)->where('idCurso', $request->idCurso)->where('idInscripcion', '!=' ,$id)->first();
            if($existInscripcion)
                return response()->json([
                    'message' => 'Ya existe esta inscripción',
                ], 400);
            
            \DB::beginTransaction();
            
            $Inscripcion->idParticipante = $request->idParticipante;
            $Inscripcion->idCurso = $request->idCurso;
            $Inscripcion->pagado = $request->pagado;
            $Inscripcion->user_update = Auth::user()->idUsuario;
            $Inscripcion->save();

            \DB::commit();
            return response()->json([
                'message' => 'Inscripcion actualizada',
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
                'message' => 'ID de inscripción inválido',
            ], 400);

        $Inscripcion=Inscripcion::find($id);
    
        if(is_null($Inscripcion) || $Inscripcion->active==0)
            return response()->json([
                'message' => 'Inscripcion no encontrada',
            ], 400);

        $Inscripcion->delete();
        return response()->json([
            'message' => 'Inscripcion eliminada',
        ], 200);
    }

    public function notify($id,$description,$color='danger',$detail=null,$type=2,$receiver){
        if(!is_null($id)){
            $detail = Associated::
            select(
                'Asociado.idAsociado', 
                \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.ruc,Persona.documento) as documento'),
                \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.razonSocial,Persona.nombresCompletos) as asociado'),
                'Asociado.tipoAsociado',
                'Asociado.estado',
                'Empresa.actividad',
                'Empresa.actividadSecundaria',
                'ComiteGremial.nombre as comitegremial',
                'Asociado.importeMensual',
                'Sector.descripcion as cobrador',
                'Asociado.direccionSocial',
                'Asociado.fechaIngreso',
                'Promotor.nombresCompletos as promotor',
                'Asociado.codigo',
                \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.fundacion,Persona.fechaNacimiento) as onomastico'),
                \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.correos,Persona.correos) as correos'),
                \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.telefonos,Persona.telefonos) as telefonos')
            )
            ->join('Sector', 'Sector.idSector', '=', 'Asociado.idSector')
            ->join('ComiteGremial', 'ComiteGremial.idComite', '=', 'Asociado.idComiteGremial')
            ->join('Promotor', 'Promotor.idPromotor', '=', 'Asociado.idPromotor')
            ->leftJoin('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
            ->leftJoin('Persona', 'Persona.idAsociado', '=', 'Asociado.idAsociado')
            ->where('Asociado.idAsociado', '=', $id)
            ->first();
        }
    
        $helper = new Helper;
        $helper::firebaseSender(
            'Actividad en asociados',
            $type,
            $description,
            $detail,
            $receiver,
            null,
            null,
            $color
        );
    }
}
